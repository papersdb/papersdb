<?php ;

/**
 * This script determines if the user has logged in.
 *
 * @package PapersDB
 */

/** Requries the class to access the user data from the database. */
require_once 'includes/pdUser.php';

function check_login() {
    $passwd_hash = "aicml";
    $access_level = 0;

    if (!isset($_SESSION['user'])) {
        return $access_level;
    }

    // remember, $_SESSION['password'] will be encrypted.
    if(!get_magic_quotes_gpc()) {
        $_SESSION['user']->login = addslashes($_SESSION['user']->login);
    }

    // addslashes to session login before using in a query.
    $db = dbCreate();
    $q = $db->selectRow('user', 'password',
                        array('login' => $_SESSION['user']->login),
                        "Admin/check_login.php");

    $db->close();

    // now we have encrypted pass from DB in $q->password,
    // stripslashes() just incase:

    $q->password = stripslashes($q->password);

    //compare:
    if ($q->password == $_SESSION['user']->password) {
        // valid password for login
        // they have correct info in session variables.

        if ($_SESSION['user']->verified == 1) {
            // user is valid
            $access_level = $_SESSION['user']->access_level;
        }
    }
    else {
        unset($_SESSION['user']); // kill incorrect session variables.
    }

    return $access_level;
}

?>
