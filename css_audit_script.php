<?php
/**
 * CSS Audit Script - Comprehensive Analysis
 * Identifies unused CSS, inline styles, and optimization opportunities
 */

class CSSAuditor {
    private $cssFiles = [];
    private $viewFiles = [];
    private $inlineStyles = [];
    private $unusedSelectors = [];
    private $duplicateRules = [];
    
    public function __construct() {
        $this->scanCSSFiles();
        $this->scanViewFiles();
    }
    
    private function scanCSSFiles() {
        $cssDir = __DIR__ . '/assets/css/';
        $files = glob($cssDir . '*.css');
        
        foreach ($files as $file) {
            $this->cssFiles[basename($file)] = file_get_contents($file);
        }
    }
    
    private function scanViewFiles() {
        $viewDirs = [
            __DIR__ . '/views/',
            __DIR__ . '/ergon/views/'
        ];
        
        foreach ($viewDirs as $dir) {
            if (is_dir($dir)) {
                $this->scanDirectory($dir);
            }
        }
    }
    
    private function scanDirectory($dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $this->viewFiles[$file->getPathname()] = $content;
                $this->extractInlineStyles($file->getPathname(), $content);
            }
        }
    }
    
    private function extractInlineStyles($file, $content) {
        // Extract inline styles
        preg_match_all('/style\s*=\s*["\']([^"\']+)["\']/', $content, $matches);
        
        foreach ($matches[1] as $style) {
            $this->inlineStyles[] = [
                'file' => $file,
                'style' => $style
            ];
        }
    }
    
    public function findUnusedCSS() {
        $allViewContent = implode(' ', $this->viewFiles);
        
        foreach ($this->cssFiles as $filename => $content) {
            // Extract CSS selectors
            preg_match_all('/([.#]?[a-zA-Z0-9_-]+(?:[.#][a-zA-Z0-9_-]+)*)\s*{/', $content, $matches);
            
            foreach ($matches[1] as $selector) {
                $cleanSelector = trim($selector);
                
                // Skip pseudo-selectors and complex selectors for now
                if (strpos($cleanSelector, ':') !== false || 
                    strpos($cleanSelector, ' ') !== false ||
                    strpos($cleanSelector, '>') !== false) {
                    continue;
                }
                
                // Check if selector is used in views
                $selectorPattern = str_replace(['.', '#'], ['\.', '\#'], $cleanSelector);
                
                if (!preg_match('/class\s*=\s*["\'][^"\']*' . preg_quote(ltrim($cleanSelector, '.'), '/') . '[^"\']*["\']/', $allViewContent) &&
                    !preg_match('/id\s*=\s*["\']' . preg_quote(ltrim($cleanSelector, '#'), '/') . '["\']/', $allViewContent)) {
                    
                    $this->unusedSelectors[] = [
                        'file' => $filename,
                        'selector' => $cleanSelector
                    ];
                }
            }
        }
    }
    
    public function findDuplicateRules() {
        $allRules = [];
        
        foreach ($this->cssFiles as $filename => $content) {
            // Extract CSS rules
            preg_match_all('/([^{}]+)\s*{\s*([^}]+)\s*}/', $content, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $selector = trim($match[1]);
                $rules = trim($match[2]);
                
                $key = md5($selector . $rules);
                
                if (isset($allRules[$key])) {
                    $this->duplicateRules[] = [
                        'selector' => $selector,
                        'rules' => $rules,
                        'files' => [$allRules[$key], $filename]
                    ];
                } else {
                    $allRules[$key] = $filename;
                }
            }
        }
    }
    
    public function generateReport() {
        $this->findUnusedCSS();
        $this->findDuplicateRules();
        
        echo "<h1>🔍 CSS Audit Report</h1>";
        
        // Inline Styles Report
        echo "<h2>📝 Inline Styles Found (" . count($this->inlineStyles) . ")</h2>";
        if (!empty($this->inlineStyles)) {
            echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";
            foreach (array_slice($this->inlineStyles, 0, 50) as $inline) {
                $file = str_replace(__DIR__, '', $inline['file']);
                echo "<div style='margin-bottom: 10px; padding: 8px; background: #f9f9f9; border-left: 3px solid #ff6b6b;'>";
                echo "<strong>File:</strong> {$file}<br>";
                echo "<strong>Style:</strong> <code>{$inline['style']}</code>";
                echo "</div>";
            }
            if (count($this->inlineStyles) > 50) {
                echo "<p><em>... and " . (count($this->inlineStyles) - 50) . " more</em></p>";
            }
            echo "</div>";
        }
        
        // Unused CSS Report
        echo "<h2>🗑️ Potentially Unused CSS (" . count($this->unusedSelectors) . ")</h2>";
        if (!empty($this->unusedSelectors)) {
            echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";
            foreach (array_slice($this->unusedSelectors, 0, 30) as $unused) {
                echo "<div style='margin-bottom: 5px; padding: 5px; background: #fff3cd; border-left: 3px solid #ffc107;'>";
                echo "<strong>{$unused['file']}:</strong> <code>{$unused['selector']}</code>";
                echo "</div>";
            }
            if (count($this->unusedSelectors) > 30) {
                echo "<p><em>... and " . (count($this->unusedSelectors) - 30) . " more</em></p>";
            }
            echo "</div>";
        }
        
        // Duplicate Rules Report
        echo "<h2>🔄 Duplicate CSS Rules (" . count($this->duplicateRules) . ")</h2>";
        if (!empty($this->duplicateRules)) {
            echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;'>";
            foreach (array_slice($this->duplicateRules, 0, 20) as $duplicate) {
                echo "<div style='margin-bottom: 10px; padding: 8px; background: #e7f3ff; border-left: 3px solid #007bff;'>";
                echo "<strong>Selector:</strong> <code>{$duplicate['selector']}</code><br>";
                echo "<strong>Files:</strong> " . implode(', ', $duplicate['files']);
                echo "</div>";
            }
            echo "</div>";
        }
        
        // CSS Files Overview
        echo "<h2>📁 CSS Files Overview</h2>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #f8f9fa;'><th style='padding: 10px; border: 1px solid #ddd;'>File</th><th style='padding: 10px; border: 1px solid #ddd;'>Size (KB)</th><th style='padding: 10px; border: 1px solid #ddd;'>Lines</th></tr>";
        
        foreach ($this->cssFiles as $filename => $content) {
            $size = round(strlen($content) / 1024, 2);
            $lines = substr_count($content, "\n") + 1;
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$filename}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$size}</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$lines}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Recommendations
        echo "<h2>💡 Optimization Recommendations</h2>";
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
        echo "<h3>Priority Actions:</h3>";
        echo "<ol>";
        echo "<li><strong>Migrate Inline Styles:</strong> " . count($this->inlineStyles) . " inline styles should be moved to CSS classes</li>";
        echo "<li><strong>Remove Unused CSS:</strong> " . count($this->unusedSelectors) . " selectors appear unused</li>";
        echo "<li><strong>Consolidate Duplicates:</strong> " . count($this->duplicateRules) . " duplicate rules found</li>";
        echo "<li><strong>File Consolidation:</strong> Consider merging smaller CSS files</li>";
        echo "</ol>";
        echo "</div>";
    }
}

// Run the audit
$auditor = new CSSAuditor();
$auditor->generateReport();
?>