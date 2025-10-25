<?php
class StaticController extends Controller {
    public function favicon() {
        header('Content-Type: image/x-icon');
        header('Cache-Control: public, max-age=86400');
        
        // Send minimal favicon data
        $favicon = base64_decode('AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A');
        echo $favicon;
        exit;
    }
}
?>