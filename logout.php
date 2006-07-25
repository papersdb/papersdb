<?php ;

// $Id: logout.php,v 1.5 2006/07/25 20:54:57 aicmltec Exp $

/**
 * \file
 *
 * \brief Allows a user to log out of the system.
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';

session_start();
$logged_in = check_login();

if ($logged_in == 0) {
	die('You are not logged in so you cannot log out.');
}

unset($_SESSION['user']);

// kill session variables
$_SESSION = array(); // reset session array
session_destroy();   // destroy session.
header('Location: index.php');

// redirect them to anywhere you like.

?>