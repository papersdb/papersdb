<?php

include_once('functions.php');
include_once('check_login.php');

if ($logged_in == 0) {
    print_r($_SESSION);
	die('You are not logged in so you cannot log out.');
}

unset($_SESSION['user']);

// kill session variables
$_SESSION = array(); // reset session array
session_destroy();   // destroy session.
header('Location: index.php');

// redirect them to anywhere you like.

?>