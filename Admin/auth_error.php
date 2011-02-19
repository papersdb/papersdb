<?php

require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/AccessLevel.php';

class auth_error extends pdHtmlPage {

	public function __construct() {
		parent::__construct('auth_error', 'Authorization Error',
		  'Admin/auth_error.php');

		if ($this->loginError) return;
		
		echo "<h2>Invalid Access Level</h2>"
		  . "\n<p>The following users have incorrect access level.</p>";
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Access Level', 'Login', 'Name'));
        $table->setRowType(0, 'th');
		foreach ($_SESSION['auth_errors'] as $auth_err) {
            $table->addRow(array(
                AccessLevel::getAccessLevelStr($auth_err['access']), 
                $auth_err['user']->login, $auth_err['user']->name),
                array('class' => 'stats_odd'));
		}
        echo $table->toHtml();	
        echo '<p><a href="authorize_new_users.php">Authorize new users</a></p>';
	}

}

$page = new auth_error();
echo $page->toHtml();

?>