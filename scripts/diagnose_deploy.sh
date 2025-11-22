#!/usr/bin/env bash
# diagnose_deploy.sh
# Lightweight diagnostic script to run on the web server hosting the app.
# It prints git state, file timestamps and key file contents, and attempts local HTTP requests.
# Usage: ssh user@server 'bash -s' < diagnose_deploy.sh

set -euo pipefail

echo "=== Diagnose deploy: $(date -u) ==="

echo "\n-- Current user --"
whoami || true
id || true

echo "\n-- Current directory --"
pwd

echo "\n-- Git branch & latest commit --"
if [ -d .git ]; then
  echo "Branch: $(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo 'N/A')"
  echo "Commit: $(git rev-parse HEAD 2>/dev/null || echo 'N/A')"
  echo "Recent commits:"
  git log -n 5 --oneline || true
else
  echo ".git not found in this directory"
fi

echo "\n-- File timestamps (full) for important files --"
FILES=("views/layouts/dashboard.php" "views/finance/dashboard.php" "assets/css/ergon.css" "assets/css/ergon.min.css" "assets/css/ergon-overrides.css" "app/controllers/FinanceController.php")
for f in "${FILES[@]}"; do
  if [ -e "$f" ]; then
    echo "\n$f"
    ls -l --full-time "$f" || ls -l "$f"
    stat --format='  Modified: %y\n  Size: %s bytes\n  Mode: %A' "$f" 2>/dev/null || true
  else
    echo "\n$f - MISSING"
  fi
done

echo "\n-- Head (first 60 lines) of key files --"
for f in "${FILES[@]}"; do
  if [ -e "$f" ]; then
    echo "\n<<<< $f >>>>"
    sed -n '1,60p' "$f" || true
  fi
done

echo "\n-- Check web-accessible CSS and dashboard (local HTTP requests) --"
URLS=("http://127.0.0.1/ergon/finance" "http://127.0.0.1/ergon/assets/css/ergon.css" "http://127.0.0.1/ergon/assets/css/ergon-overrides.css")
for u in "${URLS[@]}"; do
  echo "\n> HEAD $u"
  curl -sS -I "$u" || echo "  (curl failed)"
  echo "\n> GET (first 40 lines) $u"
  curl -sS "$u" | sed -n '1,40p' || echo "  (curl failed)"
done

echo "\n-- Check PHP and webserver processes (if available) --"
if command -v php >/dev/null 2>&1; then
  echo "php version: $(php -v | head -n1)"
fi

if command -v systemctl >/dev/null 2>&1; then
  for svc in php-fpm php7.4-fpm php8.0-fpm php8.1-fpm httpd apache2 nginx; do
    if systemctl list-units --type=service --all | grep -q "$svc"; then
      echo "\nService: $svc"
      systemctl status "$svc" --no-pager || true
    fi
  done
else
  echo "systemctl not available; listing processes for php/apache/nginx"
  ps aux | egrep 'php-fpm|apache|httpd|nginx' | sed -n '1,40p' || true
fi

echo "\n-- Networking: port 80/443 listening --"
ss -tunlp 2>/dev/null | egrep ':80|:443' || netstat -tunlp 2>/dev/null | egrep ':80|:443' || true

echo "\n-- End of diagnostic report: $(date -u) ==="
