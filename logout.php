<?php ;

// $Id: logout.php,v 1.6 2006/08/04 18:00:33 aicmltec Exp $

/**
 * \file
 *
 * \brief Allows a user to log out of the system.
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';

session_start();
$access_level = check_login();

if ($access_level <= 0) {
	die('You are not logged in so you cannot log out.');
}

unset($_SESSION['user']);

// kill session variables
$_SESSION = array(); // reset session array
session_destroy();   // destroy session.
header('Location: index.php');

// redirect them to anywhere you like.

?>