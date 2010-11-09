<?php

require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/AccessLevel.php';

class auth_success extends pdHtmlPage {

    public function __construct() {
        parent::__construct('auth_success', 'Authorization Success',
          'Admin/auth_success.php');

        if ($this->loginError) return;
        
        echo "<h2>Authorization Successful</h2>"
          . "\n<p>The following users have been granted access.</p>";
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Access Level', 'Login', 'Name', 'Conf. Email'));
        $table->setRowType(0, 'th');
        foreach ($_SESSION['auth_success'] as $auth) {
            $table->addRow(array(
                AccessLevel::getAccessLevelStr($auth['user']->access_level), 
                $auth['user']->login, $auth['user']->name, $auth['email']),
                array('class' => 'stats_odd'));
        }
        echo $table->toHtml();  
    }

}

$page = new auth_success();
echo $page->toHtml();

?>
