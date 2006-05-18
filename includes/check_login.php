<?php

  /* check login script */

session_start();
$passwd_hash = "aicml";
$db =& dbCreate();

//print_r($_SESSION);

if (!isset($_SESSION['user'])) {
	$logged_in = 0;
	return;
}
else {

	// remember, $_SESSION['password'] will be encrypted.
	if(!get_magic_quotes_gpc()) {
		$_SESSION['user']->login = addslashes($_SESSION['user']->login);
	}

	// addslashes to session login before using in a query.
    $q = $db->selectRow('user', 'password',
                        array('login' => $_SESSION['user']->login),
                        "Admin/check_login.php");

	// now we have encrypted pass from DB in
	//$q->password, stripslashes() just incase:

	$q->password = stripslashes($q->password);

	//compare:
	if ($q->password == $_SESSION['user']->password) {
		// valid password for login
		$logged_in = 1; // they have correct info
        // in session variables.
	} else {
		$logged_in = 0;
		unset($_SESSION['user']);
		// kill incorrect session variables.
	}
}

$db->close();

?>
