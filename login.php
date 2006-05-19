<?php ;

// $Id: login.php,v 1.3 2006/05/19 15:55:55 aicmltec Exp $

/**
 * \file
 *
 * \brief Allows a user to log into the system.
 */

ini_set("include_path", ini_get("include_path") . ":.:./includes:./HTML");

require_once('functions.php');
require_once('check_login.php');
require_once('pdUser.php');
require_once('HTML/Table.php');

session_start();

$passwd_hash = "aicml";

if ($logged_in == 1) {
	die('You are already logged in, '.$_SESSION['user']->login . '.');
}

if (isset($_POST['login'])) {
    // if form has been submitted
    //
	// check they filled in what they were supposed to and authenticate
	if(!$_POST['loginid'] | !$_POST['passwd']) {
		die('You did not fill in a required field.');
	}

	// authenticate.

	if (!get_magic_quotes_gpc()) {
		$_POST['loginid'] = addslashes($_POST['loginid']);
	}
    $db =& dbCreate();
    $user = new pdUser();
    $user->dbLoad(stripslashes($_POST['loginid']), $db);

	// check passwords match

	$_POST['passwd'] = stripslashes($passwd_hash . $_POST['passwd']);
	$q->password = stripslashes($passwd_hash . $q->password);
	$_POST['passwd'] = md5($_POST['passwd']);

	if ($_POST['passwd'] != $user->password) {
		die('Incorrect password, please try again.');
	}

	// if we get here username and password are correct,
	//register session variables and set last login time.

	$_POST['loginid'] = stripslashes($_POST['loginid']);
	$_SESSION['user'] = $user;
    $db->close();

    $logged_in = 1;

    htmlHeader('PapersDB Login', 'index.php');
    print "<body>\n";
    pageHeader();
    navigationMenu();

    print "<div id='content'>"
        . "<h2>Logged in</h1>"
        . "You have succesfully logged in as "
        . $_SESSION['user']->login . ".\n"
        . "<p/>Return to <a href='index.php'>main page</a>."
        . "<br/><br/><br/><br/><br/><br/>"
        . "</div>";

    pageFooter();
}
else if (isset($_POST['newaccount'])) {
    // if form has been submitted
    //
    // check they filled in what they supposed to, passwords matched, username
    // isn't already taken, etc.

	if (!$_POST['loginid'] | !$_POST['passwd'] | !$_POST['passwd_again']
        | !$_POST['email'] |  !$_POST['realname']) {
		die('You did not fill in a required field.');
	}

	// check if username exists in database.
	if (!get_magic_quotes_gpc()) {
		$_POST['loginid'] = addslashes($_POST['loginid']);
	}

    $db =& dbCreate();
    $user = new pdUser();
    $user->dbLoad(stripslashes($_POST['loginid']), $db);

	if (isset($user->login)) {
		die('Sorry, the username <strong>'. $_POST['loginid']
            . '</strong> is already taken, please pick another one.');
	}

	// check passwords match
	if ($_POST['passwd'] != $_POST['passwd_again']) {
		die('Passwords did not match.');
	}

	// check e-mail format
	if (!preg_match("/.*@.*..*/", $_POST['email'])
        | preg_match("/(<|>)/", $_POST['email'])) {
		die('Invalid e-mail address.');
	}

	// no HTML tags in username, website, location, password

	$_POST['loginid'] = strip_tags($_POST['loginid']);
	$_POST['passwd'] = strip_tags($passwd_hash . $_POST['passwd']);

	// now we can add them to the database.
	// encrypt password

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

    htmlHeader('PapersDB Login', 'index.php');
    print "<body>\n";
    pageHeader();
    navigationMenu();

    print "<div id='content'>"
        . "<h2>Login created</h1>"
        . "You have succesfully created your new login "
        . $_SESSION['user']->login . " and are now logged in.\n"
        . "<p/>Return to <a href='index.php'>main page</a>."
        . "<br/><br/><br/><br/><br/><br/>"
        . "</div>";

    pageFooter();
    $db->close();
}
else {
	// if form hasn't been submitted
    htmlHeader('PapersDB Login');

    print "<body>\n";

    pageHeader();
    navigationMenu();

    print "<div id=\"content\">\n"
        . "<h2>Create new account or log in</h2>\n"
        . "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>\n";

    $tableAttrs = array('width' => '600',
                        'border' => '0',
                        'cellpadding' => '6',
                        'cellspacing' => '0');
    $table = new HTML_Table($tableAttrs);
    $table->setAutoGrow(true);

    $form = new HTML_QuickForm('quickPubForm', 'post', $_SERVER['PHP_SELF']);

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

    $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
    $form->accept($renderer);

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

    print $renderer->toHtml($table->toHtml()) . "</div>";

    pageFooter();
}
?>
</body>
</html>