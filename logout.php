<?php ;

// $Id: logout.php,v 1.3 2006/05/19 17:42:40 aicmltec Exp $

/**
 * \file
 *
 * \brief Allows a user to log out of the system.
 */

ini_set("include_path", ini_get("include_path") . ":.:./includes:./HTML");

require_once 'functions.php';
require_once 'check_login.php';

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