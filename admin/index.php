<?php
// Admin directory index - redirect to login if not logged in, dashboard if logged in
require_once '../config/config.php';

if (is_admin_logged_in()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>
