<?php
session_start();

// Check who is logging out based on the URL parameter
$type = isset($_GET['type']) ? $_GET['type'] : 'player'; 

// Destroy all session data
session_unset();
session_destroy();

// Redirect based on type
if ($type == 'admin') {
    // If Admin, go back to Admin Portal
    header("Location: admin.php");
} else {
    // If Player (or default), go back to Player Login
    header("Location: login.php");
}
exit;
?>