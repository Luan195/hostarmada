<?php
// Proper session initialization
require_once 'includes/session.php';
require_once 'includes/functions.php';

logoutUser();

redirect('login.php');
?>
