<?php

/**
 * Allows a user to log out of the system.
 *
 * @package PapersDB
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';

class logout extends pdHtmlPage {
    public function __construct() {
        pdHtmlPage::__construct('logout');

        if ($this->access_level <= 0) {
            die('You are not logged in so you cannot log out.');
        }

        unset($_SESSION['user']);
        searchSessionInit();

        // kill session variables
        $_SESSION = array(); // reset session array
        session_destroy();   // destroy session.
        header('Location: index.php');
    }
}

$page = new logout();

?>