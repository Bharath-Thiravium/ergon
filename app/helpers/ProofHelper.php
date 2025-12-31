<?php
function proof_preview_html(string $url, string $label = 'Proof') : string {
    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    $escaped = htmlspecialchars($url);
    if (in_array($ext, ['jpg','jpeg','png','gif'])) {
        return '<div class="receipt-container">'
            . '<img src="' . $escaped . '" alt="' . htmlspecialchars($label) . '" class="receipt-image" onclick="openReceiptModal(\'' . $escaped . '\')">'
            . '<a href="' . $escaped . '" target="_blank" class="receipt-link">View Full Size</a>'
            . '</div>';
    }
    // default: show a link for PDFs/other files
    return '<div class="receipt-container">'
        . '<div><a href="' . $escaped . '" target="_blank">View ' . htmlspecialchars($label) . ' (' . htmlspecialchars(basename($url)) . ')</a></div>'
        . '<a href="' . $escaped . '" target="_blank" class="receipt-link">View File</a>'
        . '</div>';
}

?>
