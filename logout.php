<?php
require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/core/Session.php';

Session::destroy();

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Location: /ergon/login');
exit;
