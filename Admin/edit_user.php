<?php ;

// $Id: edit_user.php,v 1.23 2007/03/14 02:58:47 loyola Exp $

/**
 * This page displays/edits the users information.
 *
 * This includes fields like name, email and favorite collaborators. These
 * authors will be people the user will most likely be using a lot.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class edit_user extends pdHtmlPage {
    var $status;

    function edit_user() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('edit_user');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (isset($this->status) && ($this->status == 'edit'))
            $this->editUser();
        else
            $this->showUser();

    }

    function editUser() {
        $user =& $_SESSION['user'];
        $user->collaboratorsDbLoad($this->db);

        $form = new HTML_QuickForm('pubForm');

        $form->addElement('static', 'login', 'Login:', $user->login);
        $form->addElement('hidden', 'status', 'edit');
        $form->addElement('text', 'name', 'Name:',
                          array('size' => 50, 'maxlength' => 100));
        $form->addElement('text', 'email', 'E-mail:',
                          array('size' => 50, 'maxlength' => 100));

        $auth_list = new pdAuthorList($this->db);
        assert('is_array($auth_list->list)');

        $authSelect =& $form->addElement('advmultiselect', 'authors', null,
                                         $auth_list->list,
                                         array('class' => 'pool',
                                               'style' => 'width:150px;'),
                                         SORT_ASC);
        $authSelect->setLabel(array('Favourite Authors:', 'Selected',
                                    'Available'));

        $authSelect->setButtonAttributes('add',
                                         array('value' => 'Add',
                                               'class' => 'inputCommand'));
        $authSelect->setButtonAttributes('remove',
                                         array('value' => 'Remove',
                                               'class' => 'inputCommand'));
        $authSelect->setButtonAttributes('moveup',
                                         array('class' => 'inputCommand'));
        $authSelect->setButtonAttributes('movedown',
                                         array('class' => 'inputCommand'));

        // template for a dual multi-select element shape
        $template = <<<END
{javascript}
<table{class}>
<tr>
  <th>&nbsp;</th>
  <!-- BEGIN label_2 --><th>{label_2}</th><!-- END label_2 -->
  <th>&nbsp;</th>
  <!-- BEGIN label_3 --><th>{label_3}</th><!-- END label_3 -->
</tr>
<tr>
  <td valign="middle">{moveup}<br/>{movedown}<br/>{remove}</td>
  <td valign="top">{selected}</td>
  <td valign="middle">{add}</td>
  <td valign="top">{unselected}</td>
</tr>
</table>
{javascript}
END;

        $authSelect->setElementTemplate($template);

        $form->addElement('submit', 'Submit', 'Save');

        if ($form->validate()) {
            $values = $form->exportValues();

            assert('$values["login"]==$user->login');

            $user->name = $values['name'];
            $user->email = $values['email'];

            unset($user->collaborators);
            if (count($values['authors']) > 0) {
                $auth_list = new pdAuthorList($this->db);

                foreach ($values['authors'] as $author_id) {
                    $user->collaborators[$author_id]
                        = $auth_list->list[$author_id];
                }
            }

            $user->dbSave($this->db);
            echo 'Change to user information submitted.';
        }
        else {
            echo '<h2>Login Information</h2>';

            $defaults = array('name' => $user->name,
                              'email' => $user->email);

            if (count($user->collaborators) >0)
                $defaults['authors'] = array_keys($user->collaborators);

            $form->setDefaults($defaults);

            $renderer =& $form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" '
                . 'cellspacing="2" bgcolor="#CCCC99">'
                . '<form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $form->accept($renderer);

            $this->form =& $form;
            $this->renderer =& $renderer;
        }
}

function showUser() {
    $user =& $_SESSION['user'];
        $user->collaboratorsDbLoad($this->db);

        echo '<h2>Login Information&nbsp;&nbsp;'
            . '<a href="edit_user.php?status=edit">'
            . '<img src="../images/pencil.png" title="edit" '
            . 'alt="edit" height="16" width="16" border="0" '
            . 'align="top" /></a>'
            . '</h2>';

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        $table->addRow(array('Login:', $user->login));
        $table->addRow(array('Name:', $user->name));
        $table->addRow(array('E-mail:', $user->email));

        if (is_array($user->collaborators)
            && (count($user->collaborators) > 0)) {
            $rowcount = 0;
            foreach ($user->collaborators as $collaborator) {
                if ($rowcount == 0)
                    $cell1 = 'Favorite Collaborators:';
                else
                    $cell1 = '';
                $table->addRow(array($cell1, $collaborator));
                $rowcount++;
            }
        }
        else {
            $table->addRow(array('Favorite Collaborators:', 'None assigned'));
        }

        $table->updateColAttributes(0, array('id' => 'emph', 'width' => '30%'));
        $this->table =& $table;
    }
}

$page = new edit_user();
echo $page->toHtml();

?>

