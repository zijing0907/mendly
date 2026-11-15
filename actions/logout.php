<?php
require_once __DIR__ . '/../includes/helpers.php';   // starts the session, url(), e(), requireRole()
session_unset();
session_destroy();

header('Location: ' . url('pages/login.php'));
exit;
?>
