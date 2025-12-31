<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/ProofHelper.php';

class ProofHelperTest extends TestCase {
    public function testImagePreviewProducesImgTag() {
        $html = proof_preview_html('/ergon/storage/proofs/test-image.jpg', 'Test');
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('onclick', $html);
    }

    public function testPdfPreviewProducesViewLink() {
        $html = proof_preview_html('/ergon/storage/proofs/test-file.pdf', 'Test PDF');
        $this->assertStringContainsString('View Test PDF', $html);
        $this->assertStringContainsString('href', $html);
    }
}
