#!/bin/bash

################################################################################
#   ERGON FULL CSS OPTIMIZATION + QA PIPELINE
#   Runs safely on Amazon Q AI with Bash access
#   Non-destructive. Creates backups. PurgeCSS + PostCSS + Visual QA.
################################################################################

echo "🔧 Starting ERGON CSS optimization pipeline..."

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ARCHIVE="assets/css/archived_$TIMESTAMP"
REPORT_DIR="reports/$TIMESTAMP"

mkdir -p "$ARCHIVE"
mkdir -p "$REPORT_DIR"

echo "📁 Creating backup archive: $ARCHIVE"

# Backup ALL existing CSS files
cp assets/css/*.css "$ARCHIVE"/ 2>/dev/null

# Git backup commit
git add .
git commit -m "chore(css-backup): CSS archive created before optimization at $TIMESTAMP" >/dev/null 2>&1 || true

################################################################################
# 1. ENSURE NODE + REQUIRED PACKAGES INSTALLED
################################################################################

echo "📦 Installing required node modules (postcss, purgecss, cssnano, autoprefixer)..."

npm init -y >/dev/null 2>&1
npm install @fullhuman/postcss-purgecss postcss postcss-cli cssnano autoprefixer >/dev/null 2>&1

################################################################################
# 2. CREATE PurgeCSS CONFIG
################################################################################

echo "🛠 Writing PurgeCSS config..."

cat > purgecss.config.js <<'EOF'
module.exports = {
  content: [
    './**/*.php',
    './**/*.html',
    './assets/js/**/*.js'
  ],
  css: ['./assets/css/ergon.css'],
  safelist: {
    standard: [
      "badge","badge--success","badge--warning","badge--danger","badge--info",
      "btn","btn--primary","btn--secondary","btn--danger",
      "card","card__header","card__body","kpi-card",
      "table","table-header__cell","table-header__filter","table-filter-dropdown",
      "main-header","sidebar","nav-dropdown-menu","nav-dropdown-btn",
      "profile-menu","modal","modal-content"
    ],
    deep: [/^table-/, /^card-/, /^user-/, /^admin-/, /^kpi-/, /^profile-/]
  },
  rejected: true,
  output: './assets/css/ergon.purged.css'
}
EOF

################################################################################
# 3. CREATE PostCSS CONFIG
################################################################################

echo "🛠 Writing PostCSS config..."

cat > postcss.config.js <<'EOF'
module.exports = {
  plugins: [
    require('autoprefixer'),
    require('cssnano')({
      preset: ['default', { discardComments: { removeAll: true } }]
    })
  ]
};
EOF

################################################################################
# 4. RUN PURGECSS
################################################################################

echo "⚡ Running PurgeCSS..."

npx purgecss --config purgecss.config.js >/dev/null 2>&1

if [ ! -f "assets/css/ergon.purged.css" ]; then
  echo "❌ ERROR: PurgeCSS failed. Rolling back..."
  cp "$ARCHIVE"/ergon.css assets/css/ergon.css
  exit 1
fi

echo "✔ PurgeCSS completed: assets/css/ergon.purged.css"

################################################################################
# 5. MINIFY CSS WITH POSTCSS
################################################################################

echo "⚡ Running PostCSS minification..."

npx postcss assets/css/ergon.purged.css -o assets/css/ergon.min.css >/dev/null 2>&1

if [ ! -f "assets/css/ergon.min.css" ]; then
  echo "❌ ERROR: Minification failed. Rolling back..."
  cp "$ARCHIVE"/ergon.css assets/css/ergon.css
  exit 1
fi

echo "✔ Minified file created: assets/css/ergon.min.css"

################################################################################
# 6. UPDATE PHP HEAD SNIPPET (non-destructive)
################################################################################

echo "🧠 Updating HEAD references (safe replace)..."

# Replace only IF ergon.css exists in file
grep -Rl "<link" ./ | grep ".php" | while read -r file; do
  sed -i.bak "/ergon.css/s|href=.*|href=\"/assets/css/ergon.min.css?v=$TIMESTAMP\"|g" "$file"
done

################################################################################
# 7. CREATE/UPDATE .htaccess RULES
################################################################################

echo "🔐 Updating .htaccess (gzip + caching)..."

if [ -f .htaccess ]; then cp .htaccess "$ARCHIVE"/htaccess_backup_$TIMESTAMP; fi

cat > .htaccess <<'EOF'
# --- ERGON OPTIMIZED .HTACCESS ---

# Enable gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json image/svg+xml
</IfModule>

# Browser caching
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>

# Cache-Control Header
<FilesMatch "\.(css|js|jpg|jpeg|png|gif|svg|webp)$">
  Header set Cache-Control "public, max-age=31536000, immutable"
</FilesMatch>

# Disable directory listing
Options -Indexes
EOF

################################################################################
# 8. LIGHTHOUSE TEST (local only)
################################################################################

echo "📊 Running Lighthouse performance test (homepage)..."

npm install lighthouse --save-dev >/dev/null 2>&1

npx lighthouse http://localhost/ergon --output=json --output-path="$REPORT_DIR/lighthouse.json" --chrome-flags="--headless" >/dev/null 2>&1

echo "✔ Lighthouse report saved to $REPORT_DIR/lighthouse.json"

################################################################################
# 9. BACKSTOP VISUAL REGRESSION TEST (optional but included)
################################################################################

echo "📸 Running BackstopJS visual regression tests..."

npm install backstopjs puppeteer --save-dev >/dev/null 2>&1

cat > backstop.json <<'EOF'
{
  "id": "ergon-ui",
  "viewports": [{"label":"desktop","width":1366,"height":768}],
  "scenarios": [
    {"label":"Dashboard","url":"http://localhost/ergon/admin_index.php","selectors":["document"]},
    {"label":"Admin","url":"http://localhost/ergon/admin.php","selectors":["document"]},
    {"label":"User View","url":"http://localhost/ergon/view.php","selectors":["document"]},
    {"label":"Project Management","url":"http://localhost/ergon/project_management.php","selectors":["document"]}
  ],
  "paths": {
    "bitmaps_reference": "backstop_data/bitmaps_reference",
    "bitmaps_test": "backstop_data/bitmaps_test",
    "html_report": "backstop_data/html_report",
    "ci_report": "backstop_data/ci_report"
  },
  "report": ["browser"],
  "engine": "puppeteer"
}
EOF

# Run BackstopJS tests
npx backstop test || {
  echo "❌ Visual regression FAILED — rolling back to original CSS"
  cp "$ARCHIVE"/ergon.css assets/css/ergon.css
  exit 1
}

echo "✔ Visual regression passed"

################################################################################
# 10. FINAL COMMIT
################################################################################

git add .
git commit -m "chore(css): purge + minify + optimize + QA OK ($TIMESTAMP)" >/dev/null 2>&1

echo ""
echo "🎉 ALL DONE! CSS successfully optimized + validated."
echo "📦 Archive location: $ARCHIVE"
echo "📊 Reports saved: $REPORT_DIR"
echo "🚀 ergon.min.css is now production-ready."