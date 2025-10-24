#!/usr/bin/env bash
# ergon-audit.sh - Deep audit for Ergon PHP project
# Usage: ./ergon-audit.sh
set -euo pipefail
ROOT="$(pwd)"
OUT_JSON="$ROOT/ergon-audit-report.json"
OUT_SUM="$ROOT/ergon-audit-summary.txt"
TMP="$ROOT/.ergon_audit_tmp"
mkdir -p "$TMP"

echo "Starting Ergon deep audit..."
echo "Project root: $ROOT"
start_time=$(date --iso-8601=seconds 2>/dev/null || date +"%Y-%m-%dT%H:%M:%S%z")

# Helper: check command exists
cmd_exists(){ command -v "$1" >/dev/null 2>&1; }

# 1) Basic environment info
PHP_BIN="$(command -v php || true)"
COMPOSER_BIN="$(command -v composer || true)"
PHPSTAN_BIN="$(command -v phpstan || true)"
PSALM_BIN="$(command -v psalm || true)"
PHPCS_BIN="$(command -v phpcs || true)"

env_info=$(cat <<EOF
{
  "timestamp": "${start_time}",
  "php": "${PHP_BIN:-not-found}",
  "composer": "${COMPOSER_BIN:-not-found}",
  "phpstan": "${PHPSTAN_BIN:-not-found}",
  "psalm": "${PSALM_BIN:-not-found}",
  "phpcs": "${PHPCS_BIN:-not-found}"
}
EOF
)

echo "$env_info" > "$TMP/env.json"

# 2) Filesystem & layout sanity checks
echo "Checking repository layout and common files..."
layout_checks=()
[ -d "$ROOT/app" ] || layout_checks+=("missing app/ directory")
[ -d "$ROOT/public" ] || layout_checks+=("missing public/ directory")
[ -f "$ROOT/composer.json" ] || layout_checks+=("missing composer.json")
[ -f "$ROOT/.env" ] && layout_checks+=(".env present at repo root (ensure it's not web-accessible in production)")
[ -d "$ROOT/vendor" ] || layout_checks+=("vendor/ directory missing (run composer install)")

# check public exposure of .env
if [ -f "$ROOT/public/.env" ]; then
  layout_checks+=("public/.env found — HIGH RISK (sensitive data exposed under webroot)")
fi

# check uploads & permissions
uploads_dir="$ROOT/public/uploads"
if [ -d "$uploads_dir" ]; then
  perm=$(stat -c "%a" "$uploads_dir" 2>/dev/null || stat -f "%Lp" "$uploads_dir" 2>/dev/null || echo "unknown")
  layout_checks+=("uploads dir: $uploads_dir (perm: $perm)")
else
  layout_checks+=("uploads dir missing at public/uploads")
fi

# 3) Quick syntax check (php -l)
echo "Running PHP syntax check..."
syntax_errors=()
while IFS= read -r file; do
  if [ -f "$file" ]; then
    out=$($PHP_BIN -l "$file" 2>&1 || true)
    if echo "$out" | grep -q "Parse error"; then
      syntax_errors+=("$file: parse error")
    fi
  fi
done < <(find app public config -type f -name '*.php' 2>/dev/null || true)

# 4) Composer audit if available
composer_advisories=""
if cmd_exists composer; then
  echo "Running 'composer audit' (may be slow)..."
  composer audit --no-interaction --format=json 2>/dev/null > "$TMP/composer_audit.json" || true
  composer_advisories="$(jq -c '.advisories // {}' "$TMP/composer_audit.json" 2>/dev/null || true)"
else
  composer_advisories="composer-not-available"
fi

# 5) Static analyzers (optional)
phpstan_report="not-run"
if cmd_exists phpstan; then
  echo "Running phpstan (level 5) quick scan..."
  phpstan analyse --no-progress --error-format=json -l 5 app || true
  phpstan_report="phpstan-run"
fi
psalm_report="not-run"
if cmd_exists psalm; then
  echo "Running psalm (quick) ..."
  psalm --output-format=json --no-progress --no-cache || true
  psalm_report="psalm-run"
fi
phpcs_report="not-run"
if cmd_exists phpcs; then
  echo "Running phpcs (PSR12) quick check..."
  phpcs --standard=PSR12 -q --report=json app 2>/dev/null > "$TMP/phpcs.json" || true
  phpcs_report="phpcs-run"
fi

# 6) Deep pattern scan using embedded PHP (detect dangerous functions, eval, encodings, jwt secret leaks, DB credentials in code)
echo "Running deep code pattern scan (this may take a few moments)..."

# Write embedded PHP scanner
scanner_php="$TMP/scanner.php"
cat > "$scanner_php" <<'PHPSCAN'
<?php
// scanner.php - scans repository for risky patterns, outputs JSON
$root = $argv[1] ?? '.';
$extensions = ['php','phtml','inc','twig','js','env'];
$patterns = [
  'dangerous_functions' => [
    'pattern' => '/\b(eval|exec|system|passthru|shell_exec|pcntl_exec|`)\s*\(/i',
    'desc' => 'Calls to dangerous execution functions'
  ],
  'eval_base64' => [
    'pattern' => '/(eval\(|base64_decode\(|gzinflate\()/i',
    'desc' => 'Possible obfuscation (eval, base64_decode, gzinflate)'
  ],
  'preg_e' => [
    'pattern' => '/preg_replace\s*\(\s*[\'"].+,.*[\'"].*,.*[\'"].*\)/i',
    'desc' => 'preg_replace with /e modifier or dynamic eval-like usage'
  ],
  'db_credentials_in_files' => [
    'pattern' => '/(DB_HOST|DB_USER|DB_PASSWORD|password\s*=>|define\s*\(\s*[\'\"](DB_HOST|DB_NAME|DB_USER|DB_PASSWORD)[\'"])/i',
    'desc' => 'Hard-coded DB credentials'
  ],
  'jwt_secret_like' => [
    'pattern' => '/(JWT_SECRET|JWT_KEY|JWT_SECRET_KEY|SECRET_KEY)\s*[=:\']+\s*[\'"][A-Za-z0-9\-_]{8,}[\'"]?/i',
    'desc' => 'Possible JWT or API secret in code'
  ],
  'env_file_exposed' => [
    'pattern' => '/^\./i',
    'desc' => '.env presence under webroot'
  ],
  'xss_raw_echo' => [
    'pattern' => '/echo\s+\$?[_A-Za-z0-9]+\s*;?/i',
    'desc' => 'Simple echo usage - review for unescaped output (manual check)'
  ],
  'file_upload_move' => [
    'pattern' => '/move_uploaded_file\s*\(/i',
    'desc' => 'File upload handling - review for validation'
  ],
  'unescaped_html' => [
    'pattern' => '/\<\?=\s*\$[A-Za-z0-9_]+/i',
    'desc' => 'Short echo tags - check for escaping (<?= $var ?>)'
  ]
];

$results = [
  'scanned_files' => 0,
  'matches' => []
];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $file) {
  if (!$file->isFile()) continue;
  $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
  if (!in_array($ext, $extensions) && !in_array($file->getFilename(), ['.env'])) continue;
  $path = $file->getPathname();
  // avoid scanning vendor (but record presence)
  if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
  if (strpos($path, DIRECTORY_SEPARATOR . '_archived' . DIRECTORY_SEPARATOR) !== false) continue;
  $content = @file_get_contents($path);
  if ($content === false) continue;
  $results['scanned_files']++;
  foreach ($patterns as $k => $p) {
    if (preg_match_all($p['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches[0] as $m) {
        $line = substr_count(substr($content, 0, $m[1]), "\n") + 1;
        $results['matches'][] = [
          'type' => $k,
          'desc' => $p['desc'],
          'file' => substr($path, strlen(getcwd()) + 1),
          'snippet' => trim(substr($content, max(0, $m[1]-40), min(140, strlen($content)-$m[1]))),
          'line' => $line
        ];
      }
    }
  }
}
echo json_encode($results, JSON_PRETTY_PRINT);
PHPSCAN

php "$scanner_php" "$ROOT" > "$TMP/deepscan.json" 2>/dev/null || true

# 7) Check for public webroot exposures (public/.env, /uploads containing php files)
exposures=()
if [ -f "$ROOT/public/.env" ]; then
  exposures+=("public/.env present (exposes secrets)")
fi
# scan uploads for php files
if [ -d "$ROOT/public/uploads" ]; then
  php_files_in_uploads=$(find "$ROOT/public/uploads" -type f -iname '*.php' 2>/dev/null | wc -l || true)
  if [ "$php_files_in_uploads" -gt 0 ]; then
    exposures+=("public/uploads contains PHP files ($php_files_in_uploads) — possible RCE vector")
  fi
fi

# 8) Check for .env with DB creds
env_leaks=()
if [ -f "$ROOT/.env" ]; then
  if grep -Ei "DB_PASSWORD|DB_USER|DB_HOST|APP_KEY|JWT" "$ROOT/.env" >/dev/null 2>&1; then
    env_leaks+=(".env contains DB / SECRET keys — ensure this file is not in VCS and not web accessible")
  fi
fi

# 9) File & folder permission quick audit
perm_issues=()
if [ -d "$ROOT/storage" ]; then
  storage_perm=$(stat -c "%a" "$ROOT/storage" 2>/dev/null || stat -f "%Lp" "$ROOT/storage" 2>/dev/null || echo "unknown")
  perm_issues+=("storage/ permissions: $storage_perm")
fi
if [ -d "$ROOT/public" ]; then
  public_perm=$(stat -c "%a" "$ROOT/public" 2>/dev/null || stat -f "%Lp" "$ROOT/public" 2>/dev/null || echo "unknown")
  perm_issues+=("public/ permissions: $public_perm")
fi

# 10) Create JSON arrays for empty arrays
layout_json="[]"
syntax_json="[]"
exposures_json="[]"
env_leaks_json="[]"
perm_json="[]"

if [ ${#layout_checks[@]} -gt 0 ]; then
  layout_json=$(printf '%s\n' "${layout_checks[@]}" | jq -R -s -c 'split("\n")[:-1]')
fi
if [ ${#syntax_errors[@]} -gt 0 ]; then
  syntax_json=$(printf '%s\n' "${syntax_errors[@]}" | jq -R -s -c 'split("\n")[:-1]')
fi
if [ ${#exposures[@]} -gt 0 ]; then
  exposures_json=$(printf '%s\n' "${exposures[@]}" | jq -R -s -c 'split("\n")[:-1]')
fi
if [ ${#env_leaks[@]} -gt 0 ]; then
  env_leaks_json=$(printf '%s\n' "${env_leaks[@]}" | jq -R -s -c 'split("\n")[:-1]')
fi
if [ ${#perm_issues[@]} -gt 0 ]; then
  perm_json=$(printf '%s\n' "${perm_issues[@]}" | jq -R -s -c 'split("\n")[:-1]')
fi

# 11) Summarize and write JSON
jq -n --argjson env "$(cat $TMP/env.json)" \
      --argjson layout "$layout_json" \
      --argjson syntax_errors "$syntax_json" \
      --arg composer_advisories "$composer_advisories" \
      --arg phpstan "$phpstan_report" \
      --arg psalm "$psalm_report" \
      --arg phpcs "$phpcs_report" \
      --argjson exposures "$exposures_json" \
      --argjson env_leaks "$env_leaks_json" \
      --argjson perm_issues "$perm_json" \
      --slurpfile deepscan "$TMP/deepscan.json" \
      '{env:$env, layout_checks:$layout, syntax_errors:$syntax_errors, composer_advisories:$composer_advisories, phpstan:$phpstan, psalm:$psalm, phpcs:$phpcs, exposures:$exposures, env_leaks:$env_leaks, perm_issues:$perm_issues, deepscan:$deepscan[0]}' \
      > "$OUT_JSON"

# Human-readable summary
{
  echo "Ergon Audit Summary - $(date --iso-8601=seconds 2>/dev/null || date)"
  echo "Project root: $ROOT"
  echo
  echo "Environment:"
  cat "$TMP/env.json"
  echo
  echo "Layout checks / notes:"
  if [ ${#layout_checks[@]} -gt 0 ]; then
    for x in "${layout_checks[@]}"; do echo " - $x"; done
  else
    echo " - No layout issues found"
  fi
  echo
  echo "Exposures:"
  if [ ${#exposures[@]} -gt 0 ]; then
    for x in "${exposures[@]}"; do echo " - $x"; done
  else
    echo " - No exposures found"
  fi
  echo
  echo "Env leaks:"
  if [ ${#env_leaks[@]} -gt 0 ]; then
    for x in "${env_leaks[@]}"; do echo " - $x"; done
  else
    echo " - No env leaks detected"
  fi
  echo
  echo "Syntax errors found: ${#syntax_errors[@]}"
  if [ ${#syntax_errors[@]} -gt 0 ]; then
    for s in "${syntax_errors[@]}"; do echo " - $s"; done
  fi
  echo
  if [ -f "$TMP/composer_audit.json" ]; then
    echo "Composer audit report (summary):"
    jq '.advisories | length as $n | {vulnerabilities:$n}' "$TMP/composer_audit.json" 2>/dev/null || echo " - composer audit parse failed"
  else
    echo "Composer audit: not available / not run"
  fi
  echo
  echo "Deep scan matches (first 30):"
  jq '.deepscan.matches[:30]' "$OUT_JSON" 2>/dev/null || echo "No deep scan results"
  echo
  echo "Notes & recommended immediate actions:"
  echo " 1) If public/.env exists -> remove it from webroot immediately."
  echo " 2) Ensure uploads/ has no executable (PHP) files; configure webserver to forbid executing uploads."
  echo " 3) Review any matches for eval/exec/base64 patterns immediately (possible backdoors or obfuscation)."
  echo " 4) Run 'composer install' and 'composer audit' and fix any advisories; upgrade vulnerable packages."
  echo " 5) Run static analyzers: phpstan/psalm (configure baseline) and phpcs for PSR12 compliance."
  echo " 6) Ensure CSRF tokens on forms; review places with raw echo <?= \$var ?> for escaping."
} > "$OUT_SUM"

echo "Audit complete."
echo "Report JSON: $OUT_JSON"
echo "Summary: $OUT_SUM"
echo
echo "Quick next steps:"
echo " - Inspect $OUT_SUM for prioritized items."
echo " - Open $OUT_JSON for machine-readable details."
echo
# cleanup
rm -rf "$TMP"
exit 0