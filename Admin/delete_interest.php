<?php

/**
 * Deletes author interests from the database.
 *
 * This page won't be used often, but is necessary in order to remove any
 * author interests that were added by mistake or aren't being used at all. It
 * is just a simple form that selects the interest you would like to delete,
 * and then removes it from the database.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once('HTML/QuickForm/Renderer/QuickHtml.php');

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_interest extends pdHtmlPage {
    public function __construct() {
        parent::__construct('delete_interest', 'Delete Interest',
                           'Admin/delete_interest.php');

        if ($this->loginError) return;

        $form = new HTML_QuickForm('deleter');
        $interest_list = new pdAuthInterests($this->db);
        $form->addElement('select', 'interests',
                          'Select interest(s) to delete:', $interest_list->list,
                          array('multiple' => 'multiple', 'size' => 15));
        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'button', 'cancel', 'Cancel',
                    array('onclick' => 'history.back()')),
                HTML_QuickForm::createElement(
                    'submit', 'submit', 'Delete')
                ),
            null, null, '&nbsp;', false);

        if ($form->validate()) {
            $values = $form->exportValues();

            foreach ($values['interests'] as $interest_id)
                $names[] = $interest_list->list[$interest_id];

            $interest_list->dbDelete($this->db, $values['interests']);

            echo 'You have successfully removed the following interest from the ',
            	'database: <br/><b>', implode(', ', $names), '</b></p>', 
            	'<br><a href="', $_SERVER['PHP_SELF'], '">Delete another interest</a>';
        }
        else {
            $renderer =& $form->defaultRenderer();
            $form->accept($renderer);
            $this->form =& $form;
            $this->renderer =& $renderer;

            echo '<h3>Delete Interest </h3>';
        }
    }
}

$page = new delete_interest();
echo $page->toHtml();

?>