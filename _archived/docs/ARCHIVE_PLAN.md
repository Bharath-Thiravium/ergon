# 🗂️ ERGON ARCHIVE PLAN

## ⚠️ SAFETY FIRST - DOUBLE CHECKED

**Files identified for archiving are:**
- ✅ Test files (test_*.php, test_*.html)
- ✅ Demo/example files (sidebar-example.html)
- ✅ Audit/debug scripts (audit*.php, gap-check.php, etc.)
- ✅ Legacy fix scripts (fix_*.php, run_*.php)
- ✅ SQL migration files (*.sql)
- ✅ Documentation files (*.md except README.md)
- ✅ Temporary cache files

## 🚫 NEVER ARCHIVE (CRITICAL FILES)

**Core Application Files:**
- ❌ app/ directory (controllers, models, views, core)
- ❌ config/ directory (database, routes, constants)
- ❌ public/assets/ directory (CSS, JS)
- ❌ public/index.php, public/.htaccess
- ❌ vendor/ directory (dependencies)
- ❌ .env, composer.json, .gitignore
- ❌ README.md (main documentation)

## 📦 SAFE TO ARCHIVE

### Test & Debug Files:
- ✅ test_admin_routes.php
- ✅ test_buttons.html
- ✅ test_fixes.php
- ✅ validation_test.php
- ✅ final_test.php
- ✅ check_tables.php

### Audit & Analysis Files:
- ✅ comprehensive_audit.php
- ✅ deploy_critical_files.php
- ✅ quick_audit_check.php
- ✅ hostinger_audit.php
- ✅ hostinger_audit_fixed.php
- ✅ public/audit.php
- ✅ public/audit-gaps.php
- ✅ public/gap-check.php
- ✅ public/force-update.php

### Legacy Fix Scripts:
- ✅ apply_final_optimization.php
- ✅ apply_system_admin_updates.php
- ✅ fix_server_errors.php
- ✅ run_fixes.php
- ✅ run_performance_optimization.php
- ✅ run_remaining_fixes.php

### SQL Migration Files:
- ✅ create_admin_positions_only.sql
- ✅ database_updates_system_admin.sql
- ✅ fix_admin_tables.sql
- ✅ fix_all_errors.sql
- ✅ fix_leaves_table.sql
- ✅ fix_remaining_errors.sql
- ✅ fix_tables_structure.sql
- ✅ fix_tasks_table.sql
- ✅ optimize_performance.sql
- ✅ reset_database.sql

### Demo/Example Files:
- ✅ sidebar-example.html
- ✅ api_attendance.php (if not used by mobile app)

### Documentation Files:
- ✅ ADMIN_WORKFLOW.md
- ✅ AUDIT-CHECKLIST.md
- ✅ CRITICAL_FIXES_APPLIED.md
- ✅ DEPLOYMENT_STATUS.md
- ✅ EXPENSE_CLAIMS_FIX.md
- ✅ FINAL_AUDIT_SUMMARY.md
- ✅ FIXES_APPLIED.md
- ✅ PERFORMANCE_GUIDE.md

### Utility Scripts:
- ✅ clear-cache.php (root level, not public/)
- ✅ create_owner.php (if owner already exists)

### Cache Files:
- ✅ storage/cache/*.cache (temporary files)

## 🎯 ARCHIVE STRUCTURE

Create: `_archived/` directory with subdirectories:
- `_archived/tests/`
- `_archived/audits/`
- `_archived/fixes/`
- `_archived/sql/`
- `_archived/docs/`
- `_archived/utils/`
- `_archived/cache/`

## ⚡ EXECUTION PLAN

1. **Create archive directory structure**
2. **Move files in batches by category**
3. **Test functionality after each batch**
4. **Commit changes to Git**
5. **Verify no broken links/includes**

---

**CRITICAL SAFETY CHECK:**
- ✅ No core application files in archive list
- ✅ No active CSS/JS files in archive list
- ✅ No database config files in archive list
- ✅ No routing files in archive list
- ✅ All archived files are development/maintenance tools only