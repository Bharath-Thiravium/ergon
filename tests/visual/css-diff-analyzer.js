const fs = require('fs');
const path = require('path');

// CSS Diff Analyzer - Detects specific changes that could affect visuals
class CSSAnalyzer {
  constructor() {
    this.criticalProperties = [
      'background', 'background-color', 'background-image',
      'color', 'border', 'border-radius', 'box-shadow',
      'padding', 'margin', 'width', 'height', 'display',
      'position', 'top', 'left', 'right', 'bottom',
      'transform', 'opacity', 'z-index', 'font-size',
      'font-weight', 'line-height', 'text-align'
    ];
  }

  analyzeCSSFiles(oldFile, newFile) {
    try {
      const oldCSS = fs.readFileSync(oldFile, 'utf-8');
      const newCSS = fs.readFileSync(newFile, 'utf-8');
      
      const oldRules = this.parseCSS(oldCSS);
      const newRules = this.parseCSS(newCSS);
      
      return this.compareRules(oldRules, newRules);
    } catch (error) {
      console.error('Error analyzing CSS files:', error.message);
      return null;
    }
  }

  parseCSS(css) {
    const rules = {};
    
    // Remove comments and normalize whitespace
    css = css.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s+/g, ' ');
    
    // Extract CSS rules
    const ruleMatches = css.match(/([^{}]+)\{([^{}]*)\}/g) || [];
    
    ruleMatches.forEach(rule => {
      const [, selector, declarations] = rule.match(/([^{}]+)\{([^{}]*)\}/) || [];
      if (selector && declarations) {
        const cleanSelector = selector.trim();
        const props = {};
        
        declarations.split(';').forEach(decl => {
          const [prop, value] = decl.split(':').map(s => s.trim());
          if (prop && value) {
            props[prop] = value;
          }
        });
        
        rules[cleanSelector] = props;
      }
    });
    
    return rules;
  }

  compareRules(oldRules, newRules) {
    const changes = {
      removed: [],
      added: [],
      modified: [],
      criticalChanges: []
    };

    // Check for removed selectors
    Object.keys(oldRules).forEach(selector => {
      if (!newRules[selector]) {
        changes.removed.push(selector);
      }
    });

    // Check for added selectors
    Object.keys(newRules).forEach(selector => {
      if (!oldRules[selector]) {
        changes.added.push(selector);
      }
    });

    // Check for modified selectors
    Object.keys(oldRules).forEach(selector => {
      if (newRules[selector]) {
        const oldProps = oldRules[selector];
        const newProps = newRules[selector];
        const modifications = this.compareProperties(oldProps, newProps);
        
        if (modifications.length > 0) {
          changes.modified.push({
            selector,
            changes: modifications
          });

          // Check if changes affect critical visual properties
          const criticalMods = modifications.filter(mod => 
            this.criticalProperties.some(prop => 
              mod.property.includes(prop) || prop.includes(mod.property)
            )
          );
          
          if (criticalMods.length > 0) {
            changes.criticalChanges.push({
              selector,
              criticalChanges: criticalMods
            });
          }
        }
      }
    });

    return changes;
  }

  compareProperties(oldProps, newProps) {
    const changes = [];

    // Check for removed properties
    Object.keys(oldProps).forEach(prop => {
      if (!newProps[prop]) {
        changes.push({
          type: 'removed',
          property: prop,
          oldValue: oldProps[prop]
        });
      }
    });

    // Check for added properties
    Object.keys(newProps).forEach(prop => {
      if (!oldProps[prop]) {
        changes.push({
          type: 'added',
          property: prop,
          newValue: newProps[prop]
        });
      }
    });

    // Check for modified properties
    Object.keys(oldProps).forEach(prop => {
      if (newProps[prop] && oldProps[prop] !== newProps[prop]) {
        changes.push({
          type: 'modified',
          property: prop,
          oldValue: oldProps[prop],
          newValue: newProps[prop]
        });
      }
    });

    return changes;
  }

  generateReport(changes) {
    console.log('\n🔍 CSS ANALYSIS REPORT');
    console.log('='.repeat(50));
    
    if (changes.criticalChanges.length > 0) {
      console.log('\n⚠️  CRITICAL VISUAL CHANGES DETECTED:');
      changes.criticalChanges.forEach(change => {
        console.log(`\n📍 Selector: ${change.selector}`);
        change.criticalChanges.forEach(mod => {
          console.log(`   ${mod.type.toUpperCase()}: ${mod.property}`);
          if (mod.oldValue) console.log(`   Old: ${mod.oldValue}`);
          if (mod.newValue) console.log(`   New: ${mod.newValue}`);
        });
      });
    }

    if (changes.removed.length > 0) {
      console.log(`\n❌ REMOVED SELECTORS (${changes.removed.length}):`);
      changes.removed.slice(0, 10).forEach(selector => {
        console.log(`   - ${selector}`);
      });
      if (changes.removed.length > 10) {
        console.log(`   ... and ${changes.removed.length - 10} more`);
      }
    }

    if (changes.added.length > 0) {
      console.log(`\n✅ ADDED SELECTORS (${changes.added.length}):`);
      changes.added.slice(0, 10).forEach(selector => {
        console.log(`   + ${selector}`);
      });
      if (changes.added.length > 10) {
        console.log(`   ... and ${changes.added.length - 10} more`);
      }
    }

    console.log(`\n📊 SUMMARY:`);
    console.log(`   Removed: ${changes.removed.length} selectors`);
    console.log(`   Added: ${changes.added.length} selectors`);
    console.log(`   Modified: ${changes.modified.length} selectors`);
    console.log(`   Critical Changes: ${changes.criticalChanges.length} selectors`);

    return changes.criticalChanges.length === 0 ? 'SAFE' : 'REVIEW_NEEDED';
  }
}

// Run analysis
const analyzer = new CSSAnalyzer();
const oldFile = './assets/css/ergon.css.bak_20241218_143000';
const newFile = './assets/css/ergon.css';

if (fs.existsSync(oldFile) && fs.existsSync(newFile)) {
  const changes = analyzer.analyzeCSSFiles(oldFile, newFile);
  if (changes) {
    const status = analyzer.generateReport(changes);
    
    // Save detailed report
    fs.writeFileSync('./tests/visual/css-analysis-report.json', JSON.stringify(changes, null, 2));
    console.log('\n📄 Detailed report saved to: tests/visual/css-analysis-report.json');
    
    process.exit(status === 'SAFE' ? 0 : 1);
  }
} else {
  console.error('❌ CSS files not found. Please ensure both old and new CSS files exist.');
  process.exit(1);
}