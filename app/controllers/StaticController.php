<?php

class StaticController extends Controller {
    
    public function favicon() {
        $faviconPath = __DIR__ . '/../../public/favicon.ico';
        if (file_exists($faviconPath)) {
            header('Content-Type: image/x-icon');
            header('Cache-Control: public, max-age=86400');
            readfile($faviconPath);
        } else {
            http_response_code(404);
        }
        exit;
    }
}
