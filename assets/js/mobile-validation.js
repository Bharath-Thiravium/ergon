/**
 * Mobile Responsiveness Validation Suite
 * Automated tests for critical mobile functionality
 */

(function() {
    'use strict';
    
    const MobileValidator = {
        results: [],
        
        // Run all validation tests
        runTests() {
            console.log('🧪 Running Mobile Validation Tests...');
            
            this.testViewport();
            this.testTouchTargets();
            this.testNavigation();
            this.testTables();
            this.testModals();
            this.testPerformance();
            
            this.generateReport();
        },
        
        // Test viewport and layout
        testViewport() {
            const tests = [
                {
                    name: 'Viewport Meta Tag',
                    test: () => {
                        const meta = document.querySelector('meta[name="viewport"]');
                        return meta && meta.content.includes('width=device-width');
                    }
                },
                {
                    name: 'No Horizontal Overflow',
                    test: () => document.body.scrollWidth <= window.innerWidth
                },
                {
                    name: 'Main Content Responsive',
                    test: () => {
                        const main = document.querySelector('.main-content');
                        return main && main.offsetWidth <= window.innerWidth;
                    }
                }
            ];
            
            tests.forEach(test => this.runTest('Viewport', test));
        },
        
        // Test touch targets
        testTouchTargets() {
            const selectors = ['.btn', '.ab-btn', '.control-btn', '.nav-dropdown-btn'];
            const elements = document.querySelectorAll(selectors.join(','));
            
            let passCount = 0;
            elements.forEach(el => {
                const rect = el.getBoundingClientRect();
                if (rect.width >= 44 && rect.height >= 44) passCount++;
            });
            
            this.results.push({
                category: 'Touch Targets',
                name: 'Minimum 44px Size',
                passed: passCount === elements.length,
                details: `${passCount}/${elements.length} elements compliant`
            });
        },
        
        // Test navigation
        testNavigation() {
            const tests = [
                {
                    name: 'Mobile Menu Toggle Exists',
                    test: () => !!document.querySelector('.mobile-menu-toggle')
                },
                {
                    name: 'Mobile Overlay Exists',
                    test: () => !!document.getElementById('mobileOverlay')
                },
                {
                    name: 'Sidebar Has Mobile Class Support',
                    test: () => {
                        const sidebar = document.querySelector('.sidebar');
                        return sidebar && sidebar.classList.contains !== undefined;
                    }
                }
            ];
            
            tests.forEach(test => this.runTest('Navigation', test));
        },
        
        // Test table responsiveness
        testTables() {
            const tables = document.querySelectorAll('.table-responsive');
            
            this.results.push({
                category: 'Tables',
                name: 'Responsive Tables Present',
                passed: tables.length > 0,
                details: `${tables.length} responsive tables found`
            });
            
            if (tables.length > 0) {
                const hasOverflow = Array.from(tables).every(table => 
                    getComputedStyle(table).overflowX === 'auto'
                );
                
                this.results.push({
                    category: 'Tables',
                    name: 'Horizontal Scroll Enabled',
                    passed: hasOverflow,
                    details: 'overflow-x: auto applied'
                });
            }
        },
        
        // Test modal functionality
        testModals() {
            const modals = document.querySelectorAll('.modal');
            
            this.results.push({
                category: 'Modals',
                name: 'Modals Present',
                passed: modals.length > 0,
                details: `${modals.length} modals found`
            });
            
            if (modals.length > 0) {
                const hasAriaModal = Array.from(modals).some(modal => 
                    modal.getAttribute('aria-modal') === 'true'
                );
                
                this.results.push({
                    category: 'Modals',
                    name: 'ARIA Modal Attributes',
                    passed: hasAriaModal,
                    details: 'aria-modal attributes present'
                });
            }
        },
        
        // Test performance metrics
        testPerformance() {
            if ('performance' in window) {
                const navigation = performance.getEntriesByType('navigation')[0];
                if (navigation) {
                    const fcp = performance.getEntriesByName('first-contentful-paint')[0];
                    
                    this.results.push({
                        category: 'Performance',
                        name: 'Page Load Time',
                        passed: navigation.loadEventEnd < 3000,
                        details: `${Math.round(navigation.loadEventEnd)}ms`
                    });
                    
                    if (fcp) {
                        this.results.push({
                            category: 'Performance',
                            name: 'First Contentful Paint',
                            passed: fcp.startTime < 2000,
                            details: `${Math.round(fcp.startTime)}ms`
                        });
                    }
                }
            }
        },
        
        // Helper to run individual tests
        runTest(category, test) {
            try {
                const passed = test.test();
                this.results.push({
                    category,
                    name: test.name,
                    passed,
                    details: passed ? 'PASS' : 'FAIL'
                });
            } catch (error) {
                this.results.push({
                    category,
                    name: test.name,
                    passed: false,
                    details: `ERROR: ${error.message}`
                });
            }
        },
        
        // Generate test report
        generateReport() {
            const passed = this.results.filter(r => r.passed).length;
            const total = this.results.length;
            const score = Math.round((passed / total) * 100);
            
            console.log(`\n📊 Mobile Validation Report`);
            console.log(`Score: ${score}% (${passed}/${total} tests passed)\n`);
            
            const categories = [...new Set(this.results.map(r => r.category))];
            categories.forEach(category => {
                console.log(`${category}:`);
                this.results
                    .filter(r => r.category === category)
                    .forEach(result => {
                        const icon = result.passed ? '✅' : '❌';
                        console.log(`  ${icon} ${result.name}: ${result.details}`);
                    });
                console.log('');
            });
            
            // Store results globally for debugging
            window.mobileValidationResults = {
                score,
                passed,
                total,
                results: this.results
            };
        }
    };
    
    // Auto-run tests when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => MobileValidator.runTests(), 1000);
        });
    } else {
        setTimeout(() => MobileValidator.runTests(), 1000);
    }
    
    // Export for manual testing
    window.MobileValidator = MobileValidator;
    
})();