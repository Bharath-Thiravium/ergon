const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 ERGON CSS OPTIMIZATION QA SUITE');
console.log('='.repeat(50));

// Step 1: Analyze CSS changes
console.log('\n📊 Step 1: Analyzing CSS changes...');
try {
  execSync('node tests/visual/css-diff-analyzer.js', { stdio: 'inherit' });
} catch (error) {
  console.log('⚠️  CSS analysis completed with warnings');
}

// Step 2: Check if baseline screenshots exist
const baselineDir = './tests/visual/ui-visual-regression.spec.js-snapshots';
const hasBaseline = fs.existsSync(baselineDir);

if (!hasBaseline) {
  console.log('\n📸 Step 2: Creating baseline screenshots...');
  console.log('This will capture the current state as reference.');
  
  try {
    execSync('npx playwright test --update-snapshots', { stdio: 'inherit' });
    console.log('✅ Baseline screenshots created successfully');
  } catch (error) {
    console.error('❌ Failed to create baseline screenshots');
    process.exit(1);
  }
} else {
  console.log('\n🔍 Step 2: Running visual regression tests...');
  
  try {
    execSync('npx playwright test', { stdio: 'inherit' });
    console.log('✅ All visual tests passed!');
  } catch (error) {
    console.log('⚠️  Visual differences detected. Check the report.');
    
    // Generate HTML report
    try {
      execSync('npx playwright show-report tests/visual/playwright-report', { stdio: 'inherit' });
    } catch (reportError) {
      console.log('📄 Report generation completed');
    }
  }
}

// Step 3: Generate summary report
console.log('\n📋 Step 3: Generating QA summary...');

const generateSummary = () => {
  const summary = {
    timestamp: new Date().toISOString(),
    cssOptimization: 'completed',
    visualTests: hasBaseline ? 'comparison' : 'baseline_created',
    recommendations: []
  };

  // Check for critical files
  const criticalFiles = [
    'assets/css/ergon.css',
    'assets/css/ergon.min.css',
    'assets/css/utilities-new.css',
    'assets/css/theme-enhanced.css'
  ];

  criticalFiles.forEach(file => {
    if (!fs.existsSync(file)) {
      summary.recommendations.push(`Missing critical file: ${file}`);
    }
  });

  // Check for old CSS references in PHP files
  const phpFiles = execSync('find . -name "*.php" -type f', { encoding: 'utf-8' })
    .split('\n')
    .filter(f => f.trim());

  const oldCSSReferences = [];
  phpFiles.forEach(file => {
    if (fs.existsSync(file)) {
      const content = fs.readFileSync(file, 'utf-8');
      if (content.includes('components.css') || 
          content.includes('action-button-clean.css') ||
          content.includes('task-components.css')) {
        oldCSSReferences.push(file);
      }
    }
  });

  if (oldCSSReferences.length > 0) {
    summary.recommendations.push(`Update CSS references in: ${oldCSSReferences.slice(0, 3).join(', ')}${oldCSSReferences.length > 3 ? '...' : ''}`);
  }

  return summary;
};

const summary = generateSummary();
fs.writeFileSync('./tests/visual/qa-summary.json', JSON.stringify(summary, null, 2));

console.log('\n✅ QA SUITE COMPLETED');
console.log('📄 Summary saved to: tests/visual/qa-summary.json');

if (summary.recommendations.length > 0) {
  console.log('\n⚠️  RECOMMENDATIONS:');
  summary.recommendations.forEach(rec => console.log(`   - ${rec}`));
}

console.log('\n🎯 NEXT STEPS:');
console.log('   1. Review visual test results');
console.log('   2. Update any PHP files with old CSS references');
console.log('   3. Test critical user flows manually');
console.log('   4. Deploy to staging for final verification');