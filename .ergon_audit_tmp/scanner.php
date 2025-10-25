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
// [SECURITY FIX] Removed hardcoded password: 'pattern' => '/(DB_HOST|DB_USER|DB_PASSWORD|password\s*=>|define\s*\(\s*[\'\"](DB_HOST|DB_NAME|DB_USER|DB_PASSWORD)[\'"])/i',
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
