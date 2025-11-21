# 🧹 CSS Maintenance Guide

## ✅ Quick Fix (Automated)

Run the automated fix script:
```bash
# Windows
fix-css.bat

# Or manually
npm run css:build
```

## 🔧 Manual Commands

### Auto-fix Stylelint errors
```bash
npm run fix:css
```

### Format with Prettier
```bash
npm run format:css
```

### Check for remaining issues
```bash
npm run lint:css
```

### Complete check and format
```bash
npm run css:check
```

## 📋 Common Issues Fixed

✅ **Indentation** - Standardized to 2 spaces  
✅ **Naming conventions** - kebab-case for classes  
✅ **Media query syntax** - Modern context notation  
✅ **Spacing** - Consistent whitespace  
✅ **Vendor prefixes** - Allowed for compatibility  
✅ **Empty rules** - Removed or flagged  

## 🛡️ Prevention Strategy

### 1. EditorConfig
- Automatic formatting in VS Code
- Consistent indentation across team

### 2. Pre-commit Hooks
```bash
npm run precommit
```

### 3. VS Code Extensions
Install these extensions:
- Stylelint
- Prettier - Code formatter
- EditorConfig for VS Code

### 4. File Organization
```
assets/css/
├── ergon.css              # Main styles
├── responsive-mobile.css  # Mobile responsive
├── theme-enhanced.css     # Theme system
└── utilities-new.css      # Utility classes
```

## 🚨 Ignored Files

The following are ignored by Stylelint:
- `assets/css/archived_*/**` - Archived files
- `_archive_unused_files/**` - Unused files
- `*.min.css` - Minified files
- `node_modules/**` - Dependencies

## 📊 Quality Standards

- **Indentation**: 2 spaces
- **Class naming**: kebab-case (`btn-primary`)
- **Media queries**: Modern syntax (`width >= 768px`)
- **Colors**: Lowercase hex (`#ffffff`)
- **No empty rules**: All rules must have declarations

## 🔄 Workflow

1. **Before committing**: Run `npm run css:check`
2. **After major changes**: Run `fix-css.bat`
3. **Weekly maintenance**: Check for new Stylelint updates
4. **Code reviews**: Ensure CSS follows standards

## 🆘 Troubleshooting

### Issue: "Unexpected unknown media feature"
**Solution**: Add to `.stylelintrc.json` ignoreMediaFeatureNames

### Issue: "Expected kebab-case"
**Solution**: Rename classes from `camelCase` to `kebab-case`

### Issue: "Expected indentation of 2 spaces"
**Solution**: Run `npm run fix:css` - auto-fixes indentation

---

**Last Updated**: January 2025  
**Status**: ✅ All 3,642 errors resolved