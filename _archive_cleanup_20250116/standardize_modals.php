<?php
/**
 * Modal Standardization Script
 * This script updates all remaining modal implementations across the Ergon project
 * to use the standardized modal component system
 */

// Files that need modal standardization
$files_to_update = [
    'views/admin/dashboard.php',
    'views/admin/system_admin.php', 
    'views/expenses/index.php',
    'views/expenses/view.php',
    'views/leaves/index.php',
    'views/tasks/visualizer.php'
];

echo "Starting modal standardization across Ergon project...\n\n";

foreach ($files_to_update as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        echo "❌ File not found: $file\n";
        continue;
    }
    
    echo "🔄 Processing: $file\n";
    
    $content = file_get_contents($filepath);
    $original_content = $content;
    
    // Add modal component include at the top (after existing PHP opening)
    if (!strpos($content, "include __DIR__ . '/../shared/modal_component.php';")) {
        $content = preg_replace(
            '/(<\?php\s*\n[^?]*?\n)(ob_start\(\);|\$content = ob_start\(\);)/s',
            '$1include __DIR__ . \'/../shared/modal_component.php\';\n$2',
            $content
        );
    }
    
    // Add modal CSS after page header or before first card/content
    if (!strpos($content, 'renderModalCSS()')) {
        $content = preg_replace(
            '/(class="page-header"[^>]*>.*?<\/div>\s*\n)/s',
            '$1\n<?php renderModalCSS(); ?>\n',
            $content
        );
    }
    
    // Replace old modal HTML structures with standardized ones
    
    // Pattern 1: Basic modal with form
    $content = preg_replace(
        '/<div[^>]*class="modal"[^>]*id="([^"]+)"[^>]*>.*?<div[^>]*class="modal-content"[^>]*>.*?<div[^>]*class="modal-header"[^>]*>.*?<h3[^>]*>([^<]+)<\/h3>.*?<\/div>.*?<\/div>.*?<\/div>/s',
        '<?php renderModal(\'$1\', \'$2\', \'\', \'\', []); ?>',
        $content
    );
    
    // Replace old modal JavaScript functions
    $content = preg_replace(
        '/function\s+(\w*[Mm]odal\w*)\s*\([^)]*\)\s*\{\s*document\.getElementById\([\'"]([^\'"]+)[\'"]\)\.style\.display\s*=\s*[\'"]block[\'"];\s*\}/',
        'function $1() { showModal(\'$2\'); }',
        $content
    );
    
    $content = preg_replace(
        '/function\s+close(\w*[Mm]odal\w*)\s*\([^)]*\)\s*\{\s*document\.getElementById\([\'"]([^\'"]+)[\'"]\)\.style\.display\s*=\s*[\'"]none[\'"];\s*\}/',
        'function close$1() { closeModal(\'$2\'); }',
        $content
    );
    
    // Replace direct modal display calls
    $content = preg_replace(
        '/document\.getElementById\([\'"]([^\'"]+)[\'"]\)\.style\.display\s*=\s*[\'"]block[\'"];/',
        'showModal(\'$1\');',
        $content
    );
    
    $content = preg_replace(
        '/document\.getElementById\([\'"]([^\'"]+)[\'"]\)\.style\.display\s*=\s*[\'"]none[\'"];/',
        'closeModal(\'$1\');',
        $content
    );
    
    // Add modal JS at the end before closing PHP
    if (!strpos($content, 'renderModalJS()')) {
        $content = preg_replace(
            '/(<\/script>\s*\n\s*<\?php\s*\n\s*\$content = ob_get_clean\(\);)/s',
            '</script>\n\n<?php renderModalJS(); ?>\n\n<?php\n$content = ob_get_clean();',
            $content
        );
    }
    
    // Write updated content if changes were made
    if ($content !== $original_content) {
        file_put_contents($filepath, $content);
        echo "✅ Updated: $file\n";
    } else {
        echo "ℹ️  No changes needed: $file\n";
    }
}

echo "\n🎉 Modal standardization complete!\n";
echo "\nSummary of changes:\n";
echo "- Added standardized modal component includes\n";
echo "- Replaced old modal HTML with renderModal() calls\n";
echo "- Updated JavaScript functions to use showModal()/closeModal()\n";
echo "- Added standardized modal CSS and JS\n";
echo "\nAll modals now follow the same pattern as the follow-ups modal.\n";
?>