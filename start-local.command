#!/bin/zsh
set -e

GOALI_PROJECT_DIR="/Users/evotekcomputers/Documents/Codex/2026-08-14/th/work/goali-tour-management"

if ! lsof -nP -iTCP:3306 -sTCP:LISTEN >/dev/null 2>&1; then
  echo "Starting XAMPP MariaDB..."
  sudo /Applications/XAMPP/xamppfiles/xampp startmysql
fi

echo "Starting Goali Tours at http://127.0.0.1:8088/login.php"
cd "$GOALI_PROJECT_DIR"
exec php -S 127.0.0.1:8088 -t "$GOALI_PROJECT_DIR"
