<?php ;

// $Id: login.php,v 1.28 2007/03/12 05:25:45 loyola Exp $

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
    var $passwd_hash;

    function login() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('login');
        $this->passwd_hash = "aicml";

        if ($this->access_level > 0) {
            $this->contentPre .= 'You are already logged in as '
                . $_SESSION['user']->login . '.';
            $this->pageError = true;
            return;
        }

        if (isset($_GET['redirect']) && ($_GET['redirect'] != ''))
            $redirect = $_GET['redirect'];
        else
            $redirect = '';

        if (strpos($redirect, 'login.php')) {
            // never redirect to the login page
            $redirect = 'index.php';
        }

        $form = new HTML_QuickForm('login');

        $form->addElement('header', 'login_header', 'Login');

        $form->addElement('text', 'loginid', 'Login:',
                          array('size' => 25, 'maxlength' => 40));
        $form->addRule('loginid', 'login cannot be empty', 'required',
                       null, 'client');
        $form->addElement('password', 'passwd', 'Password:',
                          array('size' => 25, 'maxlength' => 40));
        $form->addRule('passwd', 'password cannot be empty', 'required',
                       null, 'client');
        $form->addElement('submit', 'submit_login', 'Login');

        $form->addElement('header', 'new_users', 'New Users Only');

        $form->addElement('password', 'passwd_again', 'Confirm Password:',
                          array('size' => 25, 'maxlength' => 40));
        $form->addElement('text', 'email', 'email:',
                          array('size' => 25, 'maxlength' => 80));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'realname', 'Real Name:',
                          array('size' => 25, 'maxlength' => 80));
        $form->addElement('submit', 'newaccount', 'Create new account');

        $form->addElement('hidden', 'redirect', $redirect);

        $this->form =& $form;

        if ($form->validate()) {
            $this->processForm();
            return;
        }

        // only get here if form hasn't been submitted
        $this->contentPre = '<h2>Log In or Create a New Account</h2>';

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

    function processForm() {
        $user = new pdUser();

        $values = $this->form->exportValues();

        if (!get_magic_quotes_gpc()) {
            $values['loginid'] = addslashes($values['loginid']);
        }
        $user->dbLoad($this->db, $values['loginid']);

        if (isset($values['submit_login'])) {
          // check passwords match
          $values['passwd'] = md5(stripslashes($this->passwd_hash
                                               . $values['passwd']));

          if ($values['passwd'] != $user->password) {
            $this->contentPre
              .='Incorrect password, please try again.';
            $this->pageError = true;
            return;
          }

          // if we get here username and password are correct,
          //register session variables and set last login time.
          $values['loginid'] = stripslashes($values['loginid']);
          $_SESSION['user'] = $user;

          // reset search results
          searchSessionInit();

          $this->access_level = $_SESSION['user']->access_level;

          if ($this->access_level == 0) {
            $this->contentPre .= 'Your login request has not been '
              . 'processed yet.';
            return;
          }

          if (isset( $values['redirect'])) {
            $this->redirectUrl = $values['redirect'];
            $this->redirectTimeout = 0;
          }
          else {
            $this->contentPre .= '<h2>Logged in</h1>'
              . 'You have succesfully logged in as '
              . $_SESSION['user']->login
              . '<p/>Return to <a href="index.php">main page</a>.'
              . '<br/><br/><br/><br/><br/><br/>'
              . '</div>';
          }
        }
        else if (isset($values['newaccount'])) {
            // check if username exists in database.
            if (isset($user->login)) {
                $this->contentPre .= 'Sorry, the username <strong>'
                    . $values['loginid'] . '</strong> is already taken, '
                    . 'please pick another one.';
                $this->pageError = true;
                $this->db->close();
                return;
            }

            // check passwords match
            if ($values['passwd'] != $values['passwd_again']) {
                $this->contentPre .= 'Passwords did not match.';
                $this->pageError = true;
                $this->db->close();
                return;
            }

            // no HTML tags in username, website, location, password
            $values['loginid'] = strip_tags($values['loginid']);
            $values['passwd']
                = strip_tags($this->passwd_hash . $values['passwd']);

            // now we can add them to the database.  encrypt password
            $values['passwd'] = md5($values['passwd']);

            if (!get_magic_quotes_gpc()) {
                $values['passwd'] = addslashes($values['passwd']);
                $values['email'] = addslashes($values['email']);
            }

            $this->db->insert('user', array('login'    => $values['loginid'],
                                      'password' => $values['passwd'],
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
                     . 'login: ' . $values['loginid'] . "\n"
                     . 'email: '. $values['email']);
            }

            $this->contentPre = '<h2>Login Request Submitted</h1>'
                . 'A request to create your login <b>'
                . $values['loginid'] . '</b> has been submitted. '
                . 'A confirmation email will be sent to <code>'
                . $values['email']
                . '</code> when your account is ready. '
                . '<p/>Return to <a href="index.php">main page</a>.';
        }
        else {
          $this->contentPost
            .= 'Could not process form<br/>'
            . '<pre>' . print_r($values, true) . '</pre>';
        }
        $this->db->close();
    }
}

$page = new login();
echo $page->toHtml();

?>
