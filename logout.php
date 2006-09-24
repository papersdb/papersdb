<?php ;

// $Id: logout.php,v 1.8 2006/09/24 21:21:42 aicmltec Exp $

/**
 * Allows a user to log out of the system.
 *
 * @package PapersDB
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/functions.php';
require_once 'includes/check_login.php';

session_start();
$access_level = check_login();

if ($access_level <= 0) {
	die('You are not logged in so you cannot log out.');
}

unset($_SESSION['user']);
searchSessionInit();

// kill session variables
$_SESSION = array(); // reset session array
session_destroy();   // destroy session.
header('Location: index.php');

// redirect them to anywhere you like.

?>