const fs = require('fs');
const path = require('path');

console.log('🧹 ERGON CSS CLEANUP GUIDE');
console.log('='.repeat(50));

// Files that should exist after optimization
const requiredFiles = [
  'assets/css/ergon.css',
  'assets/css/ergon.min.css',
  'assets/css/utilities-new.css',
  'assets/css/theme-enhanced.css'
];

// Files that can be safely deleted after QA
const safeToDelete = [
  'assets/css/components.css',
  'assets/css/action-button-clean.css', 
  'assets/css/task-components.css',
  'assets/css/utilities.css',
  'assets/css/ergon-backup.css',
  'assets/css/ergon-consolidated.css'
];

// Files to keep conditionally
const conditionalFiles = [
  { file: 'assets/css/critical.css', condition: 'Only if referenced in index.php' },
  { file: 'assets/css/daily-planner.css', condition: 'Only if planner pages exist' },
  { file: 'assets/css/global-tooltips.css', condition: 'Only if tooltips break without it' },
  { file: 'assets/css/instant-theme.css', condition: 'Only if instant theme switching is used' }
];

console.log('\n✅ REQUIRED FILES (must exist):');
requiredFiles.forEach(file => {
  const exists = fs.existsSync(file);
  console.log(`   ${exists ? '✓' : '✗'} ${file} ${exists ? '' : '(MISSING!)'}`);
});

console.log('\n🗑️  SAFE TO DELETE (after QA passes):');
safeToDelete.forEach(file => {
  const exists = fs.existsSync(file);
  console.log(`   ${exists ? '📁' : '✓'} ${file} ${exists ? '(can delete)' : '(already removed)'}`);
});

console.log('\n⚠️  CONDITIONAL FILES (check usage):');
conditionalFiles.forEach(item => {
  const exists = fs.existsSync(item.file);
  console.log(`   ${exists ? '📋' : '✗'} ${item.file}`);
  console.log(`      ${item.condition}`);
});

// Check current CSS directory
console.log('\n📂 CURRENT CSS DIRECTORY:');
const cssDir = 'assets/css';
if (fs.existsSync(cssDir)) {
  const files = fs.readdirSync(cssDir).filter(f => f.endsWith('.css'));
  files.forEach(file => {
    const filePath = path.join(cssDir, file);
    const stats = fs.statSync(filePath);
    const size = (stats.size / 1024).toFixed(1);
    console.log(`   📄 ${file} (${size}KB)`);
  });
}

// Generate cleanup commands
console.log('\n🔧 CLEANUP COMMANDS (run after QA approval):');
console.log('\n# Windows (cmd):');
safeToDelete.forEach(file => {
  if (fs.existsSync(file)) {
    console.log(`del "${file}"`);
  }
});

console.log('\n# Or move to archive:');
safeToDelete.forEach(file => {
  if (fs.existsSync(file)) {
    console.log(`move "${file}" "assets\\css\\archived_20241218_143000\\"`);
  }
});

// File size analysis
console.log('\n📊 FILE SIZE ANALYSIS:');
let totalBefore = 0;
let totalAfter = 0;

// Calculate before size (including files to be deleted)
[...requiredFiles, ...safeToDelete].forEach(file => {
  if (fs.existsSync(file)) {
    const size = fs.statSync(file).size;
    totalBefore += size;
    if (requiredFiles.includes(file)) {
      totalAfter += size;
    }
  }
});

console.log(`   Before cleanup: ${(totalBefore / 1024).toFixed(1)}KB`);
console.log(`   After cleanup: ${(totalAfter / 1024).toFixed(1)}KB`);
console.log(`   Space saved: ${((totalBefore - totalAfter) / 1024).toFixed(1)}KB (${(((totalBefore - totalAfter) / totalBefore) * 100).toFixed(1)}%)`);

console.log('\n🎯 NEXT STEPS:');
console.log('   1. Run: npm run qa:full');
console.log('   2. Review visual test results');
console.log('   3. If tests pass, run cleanup commands');
console.log('   4. Test critical user flows manually');
console.log('   5. Deploy to production with ergon.min.css');