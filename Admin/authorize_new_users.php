<?php

require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/AuthRenderer.php';
require_once 'includes/AccessLevel.php';

class authorize_new_users extends pdHtmlPage {

	private $users;

	public function __construct() {
		parent::__construct('authorize_new_users');

		if ($this->loginError) return;

		$this->loadHttpVars(true, true);
		$this->users = pdUserList::getNotVerified($this->db);
		echo '<h2>Users Requiring Authentication</h2>';

		if (($this->users == null) || (count($this->users) == 0)) {
			echo 'All users authorized.';
			return;
		}

		$form = new HTML_QuickForm('authorizeUsers', 'post');

		foreach ($this->users as $user) {
			$form->addGroup(
			array(
			     HTML_QuickForm::createElement(
			         'advcheckbox', "submit[auth][{$user->login}]",
			         null, null, null, array('no', 'yes')),
			     HTML_QuickForm::createElement(
			         'select', "submit[access][{$user->login}]", null,
			         AccessLevel::getAccessLevels()),
			     HTML_QuickForm::createElement(
                    'static', null, null, $user->login),
			     HTML_QuickForm::createElement(
                    'static', null, null, $user->name),
         		 HTML_QuickForm::createElement(
                    'static', null, null, $user->email)
			     ),
			'all', null, '</td><td class="stats_odd">', false
			);
		}
		$form->addElement('submit', null, 'Submit');
		$this->form = & $form;

		if ($form->validate())
		$this->processForm();
		else
		$this->renderForm();
	}

	public function renderForm() {
		$form =& $this->form;
		$this->renderer = new AuthRenderer($form);
	}

	public function processForm() {
		$form =& $this->form;
		$values = $form->exportValues();

		// check for errors
		$auth_errors = array();
		foreach ($this->users as $user) {
			if ($values['submit']['auth'][$user->login] == 'no') continue;
			if (intval($values['submit']['access'][$user->login]) == 0) {
				$auth_errors[] = array(
				    'user' => $user,
				    'access' => $values['submit']['access'][$user->login]
				);
			}
		}

		if (count($auth_errors) != 0) {
			$_SESSION['auth_errors'] = $auth_errors;
			header('Location: auth_error.php');
			return;
		}

		$auth_success = array();
        foreach ($this->users as $user) {
            if ($values['submit']['auth'][$user->login] == 'no') continue;
            $user->verified = 1;
            $user->access_level = $values['submit']['access'][$user->login];
            $user->dbSave($this->db);
            // only send email if running the real papersdb
            if (strpos($_SERVER['PHP_SELF'], '~papersdb')) {
                $recipients = "{$user->email},papersdb@cs.ualberta.ca";
            } else {
            	$recipients = 'loyola@ualberta.ca';
            }
            $result = mail($recipients,
                'Re: PapersDB: Login',
                "Your account has been created. You can now log into PapersDB at:\n"
                . "http://www.cs.ualberta.ca/~papersdb/");
            $auth_success[] = array('user' => $user,
                'email' => $result ? 'Sent' : 'Error');
        }

        $_SESSION['auth_success'] = $auth_success;
        header('Location: auth_success.php');
	}
}

$page = new authorize_new_users();
echo $page->toHtml();

?>