<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /ergon/login');
    exit;
}
?>
