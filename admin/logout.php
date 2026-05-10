<?php
// Proper session initialization
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Logout admin
logoutAdmin();

// Redirect to admin login
redirect('login.php');
?>
