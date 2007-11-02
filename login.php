<?php ;

// $Id: login.php,v 1.37 2007/11/02 16:36:28 loyola Exp $

/**
 * Allows a user to log into the system.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class login extends pdHtmlPage {
    public $redirect;
    public $password_hash;

    public function __construct() {
        parent::__construct('login');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        $this->password_hash = "aicml";

        if ($this->access_level > 0) {
            echo 'You are already logged in as ', $_SESSION['user']->login, '.';
            $this->pageError = true;
            return;
        }

        if (strpos($this->redirect, 'login.php') !== false) {
            // never redirect to the login page
            $this->redirect = 'index.php';
        }

        $form = new HTML_QuickForm('login');

        $form->addElement('header', 'login_header', 'Login');

        $form->addElement('text', 'username', 'Login:',
                          array('size' => 25, 'maxlength' => 40));
        $form->addRule('username', 'login cannot be empty', 'required',
                       null, 'client');
        $form->addElement('password', 'password', 'Password:',
                          array('size' => 25, 'maxlength' => 40));
        $form->addRule('password', 'password cannot be empty', 'required',
                       null, 'client');
        $form->addElement('submit', 'submit_username', 'Login');

        $form->addElement('header', 'new_users', 'New Users Only');

        $form->addElement('password', 'password_again', 'Confirm Password:',
                          array('size' => 25, 'maxlength' => 40));
        $form->addElement('text', 'email', 'email:',
                          array('size' => 25, 'maxlength' => 80));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'realname', 'Real Name:',
                          array('size' => 25, 'maxlength' => 80));
        $form->addElement('submit', 'newaccount', 'Create new account');

        $form->addElement('hidden', 'redirect', $this->redirect);

        $this->form =& $form;

        if ($form->validate()) {
            $this->processForm();
            return;
        }

        // only get here if form hasn't been submitted
        echo '<h2>Log In or Create a New Account</h2>';

        $this->renderer =& $this->form->defaultRenderer();

        $this->renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" '
            . 'cellspacing="2" bgcolor="#CCCC99">'
            . '<form{attributes}>{content}</form></table>');
        $this->renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $this->form->accept($this->renderer);
    }

    public function processForm() {
        $user = new pdUser();

        $values = $this->form->exportValues();

        if (!get_magic_quotes_gpc()) {
            $values['username'] = addslashes($values['username']);
        }
        $user->dbLoad($this->db, $values['username']);

        if (isset($values['submit_username'])) {
          // check passwords match
          $values['password'] = md5(stripslashes($this->password_hash
                                               . $values['password']));

          if ($values['password'] != $user->password) {
            echo'Incorrect password, please try again.';
            $this->pageError = true;
            return;
          }

          // if we get here username and password are correct,
          //register session variables and set last login time.
          $values['username'] = stripslashes($values['username']);
          $_SESSION['user'] = $user;

          // reset search results
          searchSessionInit();

          $this->access_level = $_SESSION['user']->access_level;

          if ($this->access_level == 0) {
            echo 'Your login request has not been processed yet.';
            return;
          }

          if (isset( $values['redirect'])) {
            $this->redirectUrl = $values['redirect'];
            $this->redirectTimeout = 0;
          }
          else {
            echo '<h2>Logged in</h1>', 
            	'You have succesfully logged in as ', $_SESSION['user']->login, 
            	'<p/>Return to <a href="index.php">main page</a>.', 
            	'</div>';
          }
        }
        else if (isset($values['newaccount'])) {
            // check if username exists in database.
            if (isset($user->login)) {
                echo 'Sorry, the username <strong>', $values['username'], 
                	'</strong> is already taken, please pick another one.';
                $this->pageError = true;
                return;
            }

            // check passwords match
            if ($values['password'] != $values['password_again']) {
                echo 'Passwords did not match.';
                $this->pageError = true;
                return;
            }

            // no HTML tags in username, website, location, password
            $values['username'] = strip_tags($values['username']);
            $values['password']
                = strip_tags($this->password_hash . $values['password']);

            // now we can add them to the database.  encrypt password
            $values['password'] = md5($values['password']);

            if (!get_magic_quotes_gpc()) {
                $values['password'] = addslashes($values['password']);
                $values['email'] = addslashes($values['email']);
            }

            $this->db->insert('user', array('login'    => $values['username'],
                                      'password' => $values['password'],
                                      'email'    => $values['email'],
                                      'name'     => $values['realname']),
                        'login.php');

            $this->access_level = 0;

            // only send email if running the real papersdb
            if (strpos($_SERVER['PHP_SELF'], '~papersdb')) {
                mail(DB_ADMIN, 'PapersDB: Login Request',
                     'The following user has requested editor access '
                     . 'level for PapersDB.' . "\n\n"
                     . 'name: ' . $values['realname'] . "\n"
                     . 'login: ' . $values['username'] . "\n"
                     . 'email: '. $values['email']);
            }

            echo '<h2>Login Request Submitted</h1>', 
            	'A request to create your login <b>', $values['username'], 
            	'</b> has been submitted. A confirmation email will be sent to <code>', 
            	$values['email'], '</code> when your account is ready. ', 
            	'<p/>Return to <a href="index.php">main page</a>.';
        }
        else {
          echo 'Could not process form<br/><pre>', print_r($values, true), '</pre>';
        }
    }
}

$page = new login();
echo $page->toHtml();

?>
