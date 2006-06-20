<?php ;

// $Id: login.php,v 1.14 2006/06/20 14:21:58 aicmltec Exp $

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
            $this->contentPre .= 'You are already logged in as '
                . $_SESSION['user']->login . '.';
            $this->pageError = true;
            return;
        }

        if (isset($_GET['redirect']) && ($_GET['redirect'] != ''))
            $redirect = $_GET['redirect'];
        else
            $redirect = '';

        $form = new HTML_QuickForm('quickPubForm');

        $form->addElement('text', 'loginid', null,
                          array('size' => 25, 'maxlength' => 40));
        $form->addRule('loginid', 'login cannot be empty', 'required',
                       null, 'client');
        $form->addElement('password', 'passwd', null,
                          array('size' => 25, 'maxlength' => 40));
        $form->addRule('passwd', 'password cannot be empty', 'required',
                       null, 'client');
        $form->addElement('submit', 'login', 'Login');
        $form->addElement('password', 'passwd_again', null,
                          array('size' => 25, 'maxlength' => 40));
        $form->addElement('text', 'email', null,
                          array('size' => 25, 'maxlength' => 80));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'realname', null,
                          array('size' => 25, 'maxlength' => 80));
        $form->addElement('submit', 'newaccount', 'Create new account');

        $form->addElement('hidden', 'redirect', $redirect);

        if ($form->validate()) {
            $values = $form->exportValues();

            if (isset($values['login'])) {
                // authenticate.
                if (!get_magic_quotes_gpc()) {
                    $values['loginid'] = addslashes($values['loginid']);
                }
                $db =& dbCreate();
                $user = new pdUser();
                $user->dbLoad($db, stripslashes($values['loginid']));

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
                $db->close();

                $logged_in = 1;

                if (isset( $values['redirect'])) {
                    $this->redirectUrl = $values['redirect'];

                        //'http://' . $_SERVER['HTTP_HOST']
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
                // if form has been submitted

                // check if username exists in database.
                if (!get_magic_quotes_gpc()) {
                    $values['loginid'] = addslashes($values['loginid']);
                }

                $db =& dbCreate();
                $user = new pdUser();
                $user->dbLoad($db, stripslashes($values['loginid']));

                if (isset($user->login)) {
                    $this->contentPre .= 'Sorry, the username <strong>'
                        . $values['loginid'] . '</strong> is already taken, '
                        . 'please pick another one.';
                    $this->pageError = true;
                    $db->close();
                    return;
                }

                // check passwords match
                if ($values['passwd'] != $values['passwd_again']) {
                    $this->contentPre .= 'Passwords did not match.';
                    $this->pageError = true;
                    $db->close();
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

                $db->insert('user', array('login'    => $values['loginid'],
                                          'password' => $values['passwd'],
                                          'email'    => $values['email'],
                                          'name'     => $values['realname']),
                            'login.php');

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
        }
        else {
            // if form hasn't been submitted
            $this->contentPre = '<h2>Create new account or log in</h2>';

            $renderer = new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table = new HTML_Table(array('width' => '600',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));
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

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
        }
    }
}

$page = new login();
echo $page->toHtml();

?>