<?php ;

// $Id: login.php,v 1.12 2006/06/09 22:08:58 aicmltec Exp $

/**
 * \file
 *
 * \brief Allows a user to log into the system.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 */
class login extends pdHtmlPage {
    var $passwd_hash;

    function login() {
        global $logged_in;

        parent::pdHtmlPage('login');
        $this->passwd_hash = "aicml";

        if ($logged_in == 1) {
            $this->contentPre = 'You are already logged in as '
                . $_SESSION['user']->login . '.';
            $this->pageError = true;
            return;
        }

        if (isset($_POST['login'])) {
            // if form has been submitted
            //
            // check they filled in what they were supposed to and authenticate
            if(!$_POST['loginid'] | !$_POST['passwd']) {
                $this->contentPre = 'You did not fill in a required field.';
                $this->pageError = true;
                return;
            }

            // authenticate.
            if (!get_magic_quotes_gpc()) {
                $_POST['loginid'] = addslashes($_POST['loginid']);
            }
            $db =& dbCreate();
            $user = new pdUser();
            $user->dbLoad($db, stripslashes($_POST['loginid']));

            // check passwords match
            $_POST['passwd'] = md5(stripslashes($this->passwd_hash
                                                . $_POST['passwd']));

            if ($_POST['passwd'] != $user->password) {
                $this->contentPre ='Incorrect password, please try again.';
                $this->pageError = true;
                return;
            }

            // if we get here username and password are correct,
            //register session variables and set last login time.
            $_POST['loginid'] = stripslashes($_POST['loginid']);
            $_SESSION['user'] = $user;
            $db->close();

            $logged_in = 1;

            $this->redirectUrl = 'http://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF']) . '/index.php';

            $this->contentPre = '<h2>Logged in</h1>'
                . 'You have succesfully logged in as '
                . $_SESSION['user']->login
                . '<p/>Return to <a href="index.php">main page</a>.'
                . '<br/><br/><br/><br/><br/><br/>'
                . '</div>';

        }
        else if (isset($_POST['newaccount'])) {
            // if form has been submitted
            //
            // check they filled in what they supposed to, passwords matched,
            // username isn't already taken, etc.
            if (!$_POST['loginid'] || !$_POST['passwd']
                || !$_POST['passwd_again']
                || !$_POST['email'] |  !$_POST['realname']) {
                $this->contentPre = 'You did not fill in a required field.';
                $this->pageError = true;
                return;
            }

            // check if username exists in database.
            if (!get_magic_quotes_gpc()) {
                $_POST['loginid'] = addslashes($_POST['loginid']);
            }

            $db =& dbCreate();
            $user = new pdUser();
            $user->dbLoad($db, stripslashes($_POST['loginid']));

            if (isset($user->login)) {
                die('Sorry, the username <strong>'. $_POST['loginid']
                    . '</strong> is already taken, please pick another one.');
            }

            // check passwords match
            if ($_POST['passwd'] != $_POST['passwd_again']) {
                $this->contentPre = 'Passwords did not match.';
                $this->pageError = true;
                return;
            }

            // check e-mail format
            if (!preg_match("/.*@.*..*/", $_POST['email'])
                | preg_match("/(<|>)/", $_POST['email'])) {
                $this->contentPre = 'Invalid e-mail address.';
                $this->pageError = true;
                return;
            }

            // no HTML tags in username, website, location, password
            $_POST['loginid'] = strip_tags($_POST['loginid']);
            $_POST['passwd']
                = strip_tags($this->passwd_hash . $_POST['passwd']);

            // now we can add them to the database.  encrypt password
            $_POST['passwd'] = md5($_POST['passwd']);

            if (!get_magic_quotes_gpc()) {
                $_POST['passwd'] = addslashes($_POST['passwd']);
                $_POST['email'] = addslashes($_POST['email']);
            }

            $db->query("INSERT INTO user (login, password, email, name)"
                       . "VALUES ("
                       . "'" . $_POST['loginid'] ."', "
                       . "'" . $_POST['passwd'] ."', "
                       . "'" . $_POST['email'] ."', "
                       . "'" . $_POST['realname'] ."');");

            $logged_in = 1;
            $_SESSION['user'] = $user;

            $this->contentPre = '<h2>Login created</h1>'
                . 'You have succesfully created your new login '
                . $_SESSION['user']->login . ' and are now logged in.'
                . '<p/>Return to <a href="index.php">main page</a>.';

            $this->redirectUrl = 'http://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF']) . '/index.php';

            $db->close();
        }
        else {
            // if form hasn't been submitted
            $this->contentPre = '<h2>Create new account or log in</h2>';

            $this->form = new HTML_QuickForm('quickPubForm', 'post',
                                       $_SERVER['PHP_SELF']);
            $form =& $this->form;

            $form->addElement('text', 'loginid', null,
                              array('size' => 25, 'maxlength' => 40));
            $form->addElement('password', 'passwd', null,
                              array('size' => 25, 'maxlength' => 40));
            $form->addElement('submit', 'login', 'Login');
            $form->addElement('password', 'passwd_again', null,
                              array('size' => 25, 'maxlength' => 40));
            $form->addElement('text', 'email', null,
                              array('size' => 25, 'maxlength' => 80));
            $form->addElement('text', 'realname', null,
                              array('size' => 25, 'maxlength' => 80));
            $form->addElement('submit', 'newaccount', 'Create new account');

            $this->renderer = new HTML_QuickForm_Renderer_QuickHtml();
            $renderer =& $this->renderer;
            $form->accept($renderer);

            $this->table = new HTML_Table(array('width' => '600',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));
            $table =& $this->table;
            $table->setAutoGrow(true);

            $table->addRow(array('Login:', $renderer->elementToHtml('loginid')));
            $table->addRow(array('Password:', $renderer->elementToHtml('passwd'),
                                 $renderer->elementToHtml('login')));
            $table->addRow();
            $table->addRow(array('Confirm Password:',
                                 $renderer->elementToHtml('passwd_again'),
                                 '(new users only)'));
            $table->addRow(array('email:', $renderer->elementToHtml('email')));
            $table->addRow(array('Real Name:', $renderer->elementToHtml('realname'),
                                 $renderer->elementToHtml('newaccount')));

            $table->updateColAttributes(0, array('id' => 'emph'));
        }
    }
}

$page = new login();
echo $page->toHtml();

?>