<?php
/**
 * Public Assets Handler
 * Serves static files from the public directory
 */

// This file should only handle static asset requests
// All application routing is handled by the main index.php

http_response_code(404);
echo "Asset not found";
?>