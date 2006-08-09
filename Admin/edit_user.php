<?php ;

// $Id: edit_user.php,v 1.12 2006/08/09 03:17:08 loyola Exp $

/**
 * \file
 *
 * \brief This page displays/edits the users information.
 *
 * This includes fields like name, email and favorite collaborators. These
 * authors will be people the user will most likely be using a lot.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';

/**
 * Renders the whole page.
 */
class edit_user extends pdHtmlPage {
    function edit_user() {
        global $access_level;

        parent::pdHtmlPage('edit_user');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        if ((isset($_GET['status']) && ($_GET['status'] == 'edit'))
            || (isset($_POST['status']) && ($_POST['status'] == 'edit')))
            $this->editUser();
        else
            $this->showUser();

    }

    function editUser() {
        $db =& dbCreate();
        $user =& $_SESSION['user'];
        $user->collaboratorsDbLoad($db);

        $form = new HTML_QuickForm('pubForm');

        $form->addElement('hidden', 'login', $user->login);
        $form->addElement('hidden', 'status', 'edit');
        $form->addElement('text', 'name', null,
                          array('size' => 50, 'maxlength' => 100));
        $form->addElement('text', 'email', null,
                          array('size' => 50, 'maxlength' => 100));

        $auth_list = new pdAuthorList($db);
        assert('is_array($auth_list->list)');

        $authSelect =& $form->addElement('advmultiselect', 'authors', null,
                                         $auth_list->list,
                                         array('class' => 'pool',
                                               'style' => 'width:150px;'),
                                         SORT_ASC);
        $authSelect->setLabel(array('Authors:', 'Selected', 'Available'));

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
                $auth_list = new pdAuthorList($db);

                foreach ($values['authors'] as $author_id) {
                    $user->collaborators[$author_id]
                        = $auth_list->list[$author_id];
                }
            }

            $user->dbSave($db);
            $this->contentPre .= 'Change to user information submitted.';
        }
        else {
            $table = new HTML_Table(array('width' => '100%',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));
            $table->setAutoGrow(true);

            $this->contentPre .= '<h2>Login Information</h2>';

            $defaults = array('name' => $user->name,
                              'email' => $user->email);

            if (count($user->collaborators) >0)
                $defaults['authors'] = array_keys($user->collaborators);

            $form->setDefaults($defaults);

            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table->addRow(array('Login:', $user->login));
            $table->addRow(array('Name:',
                                 $renderer->elementToHtml('name')));
            $table->addRow(array('E-mail:',
                                 $renderer->elementToHtml('email')));
            $table->addRow(array('Favorite Collaborators:',
                                 $renderer->elementToHtml('authors')));
            $table->addRow(array($renderer->elementToHtml('Submit')),
                           array('colspan' => 2));

            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '30%'));

            $this->table =& $table;
            $this->form =& $form;
            $this->renderer =& $renderer;
        }
        $db->close();
    }

function showUser() {
        $db =& dbCreate();
        $user =& $_SESSION['user'];
        $user->collaboratorsDbLoad($db);

        $this->contentPre .= '<h2>Login Information&nbsp;&nbsp;'
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
        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new edit_user();
echo $page->toHtml();

?>

