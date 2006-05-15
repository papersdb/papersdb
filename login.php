<?php

include_once('functions.php');
include_once('check_login.php');
require_once('includes/pdUser.php');

session_start();

$passwd_hash = "aicml";

if ($logged_in == 1) {
	die('You are already logged in, '.$_SESSION['user']->login . '.');
}

print "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' "
. "lang='en'>\n"
. "<head>\n"
. "<title>" . $pub->title . "</title>\n"
. "<meta http-equiv='Content-Type' content='text/html; "
. "charset=iso-8859-1' />\n"
. "<link rel='stylesheet' type='text/css' href='style.css' />\n"
. "</head>\n"
. "<body>\n";

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

    pageHeader();
    navigationMenu();

    print "<div id='content'>"
        . "<h2>Logged in</h1>"
        . "You have succesfully logged in as "
        . $_SESSION['user']->login . ".\n"
        . "<p/>Return to <a href='index.php'>main page</a>."
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

    $db->close();
}
else {
	// if form hasn't been submitted
    pageHeader();
    navigationMenu();

    print "<div id=\"content\">\n"
        . "<h2>Create new account or log in</h2>\n"
        . "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>\n";
    echo <<<END
        <table border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td id="emph">Login:</td>
            <td><input type="text" name="loginid" maxlength="40"/></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td id="emph">Password:</td>
            <td><input type="password" name="passwd" maxlength="50"/></td>
            <td><input type="submit" name="login" value="Login"/></td>
          </tr>
          <tr>
            <td colspan="3">&nbsp;</td>
          </tr>
          <tr>
            <td id="emph">Confirm Password:</td>
            <td><input type="password" name="passwd_again" maxlength="50"/></td>
            <td>(new users only)</td>
          </tr>
          <tr>
            <td id="emph">E-Mail*:</td>
            <td><input type="text" name="email" maxlength="100"/></td>
                                        <td>&nbsp;</td>
          </tr>
          <tr>
            <td id="emph">Real name*:</td>
            <td><input type="text" name="realname" maxlength="100"/></td>
            <td><input type="submit" name="newaccount"
                  value="Create new account"/></td>
          </tr>
        </table>
      </form>
    </div>

END;

    pageFooter();
}
?>
</body>
</html>