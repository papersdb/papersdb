<?php ;

// $Id: delete_interest.php,v 1.13 2007/03/12 23:05:43 aicmltec Exp $

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

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once('HTML/QuickForm/Renderer/QuickHtml.php');

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_interest extends pdHtmlPage {
    function delete_interest() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('delete_interest');

        if ($this->loginError) return;

        $form =& $this->confirmForm('deleter');
        $interest_list = new pdAuthInterests($this->db);
        $form->addElement('select', 'interests', null, $interest_list->list,
                          array('multiple' => 'multiple', 'size' => 15));


        if ($form->validate()) {
            $values = $form->exportValues();

            foreach ($values['interests'] as $interest_id)
                $names[] = $interest_list->list[$interest_id];

            $interest_list->dbDelete($this->db, $values['interests']);

            $this->contentPre .= 'You have successfully removed the '
                . 'following interest from the database: <br/><b>'
                . implode(', ', $names) . '</b></p>'
                . '<br><a href="' . $_SERVER['PHP_SELF']
                . '">Delete another interest</a>';
        }
        else {
            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $this->contentPre .= '<h3>Delete Interest </h3><br/>';

            $table = new HTML_Table(array('width' => '100%',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));

            $table->addRow(array('Select interest(s) to delete:',
                                 $renderer->elementToHtml('interests')));
            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '25%'));
            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
        }

        $this->db->close();
    }
}

$page = new delete_interest();
echo $page->toHtml();

?>