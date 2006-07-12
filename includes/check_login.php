<?php ;

/* check login script */

require_once 'includes/pdUser.php';

function check_login() {
    $passwd_hash = "aicml";

    if (!isset($_SESSION['user'])) {
        $logged_in = 0;
        return $logged_in;
    }

    // remember, $_SESSION['password'] will be encrypted.
    if(!get_magic_quotes_gpc()) {
        $_SESSION['user']->login = addslashes($_SESSION['user']->login);
    }

    // addslashes to session login before using in a query.
    $db =& dbCreate();
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
        $logged_in = 1; // they have correct info in session variables.
    }
    else {
        $logged_in = 0;
        unset($_SESSION['user']); // kill incorrect session variables.
    }

    return $logged_in;
}

?>
