<?php ;

// $Id: edit_user.php,v 1.1 2006/06/09 06:32:06 aicmltec Exp $

/**
 * \file
 *
 * \brief This page displays/edits the users information.
 *
 * This includes fields like name, email and favorite collaborators. These
 * authors will be people the user will most likely be using a lot.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdAuthorList.php';

require_once 'HTML/Table.php';
require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';

$db =& dbCreate();

htmlHeader('edit_user', 'User Information');
if (!$logged_in) {
    echo '<body>';
    pageHeader();
    navMenu('edit_user');
    echo "<div id='content'>\n";
    loginErrorMessage();
}

echo '<body>';
pageHeader();
navMenu('edit_user');
echo "<div id='content'>\n";

$user = $_SESSION['user'];
if (isset($_GET['status']) && ($_GET['status'] != ''))
    $status = $_GET['status'];

if ($status == 'edit') {
    $form = new HTML_QuickForm('pubForm', 'post',
                               './edit_user.php?status=change',
                               "add_publication.php");
    $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
}

$tableAttrs = array('width' => '100%',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);


/* Connecting, selecting database */
if ($status == "change") {
    assert('$_POST["login"]==$user->login');

    $user->name = $_POST['name'];
    $user->email = $_POST['email'];

    if (count($_POST['authors']) > 0) {
        $auth_list = new pdAuthorList();
        $auth_list->dbLoad($db);

        unset($user->collaborators);
        foreach ($_POST['authors'] as $author_id) {
            $user->collaborators[] = $auth_list->list[$author_id];
        }
    }
    $user->dbSave($db);
}
?>

<h2><b><u>Login Information</u></b></h2>
<?

$edit = false;

if ($status == 'edit') {
    $form->addElement('hidden', 'login', $_SESSION['user']->login);
    $form->addElement('text', 'name', null,
                      array('size' => 50, 'maxlength' => 100));
    $form->addElement('text', 'email', null,
                      array('size' => 50, 'maxlength' => 100));

    $auth_list = new pdAuthorList();
    $auth_list->dbLoad($db);
    assert('is_array($auth_list->list)');
    unset($options);
    foreach ($auth_list->list as $auth) {
        $options[$auth->author_id] = $auth->name;
    }

    $authSelect =& $form->addElement('advmultiselect', 'authors', null,
                                     $options,
                                     array('class' => 'pool',
                                           'style' => 'width:150px;'),
                                     SORT_ASC);
    $authSelect->setLabel(array('Authors:', 'Selected', 'Available'));

    $authSelect->setButtonAttributes('add',
                                     array('value' => '<<',
                                           'class' => 'inputCommand'));
    $authSelect->setButtonAttributes('remove',
                                     array('value' => '>>',
                                           'class' => 'inputCommand'));
    $authSelect->setButtonAttributes('moveup',
                                     array('class' => 'inputCommand'));
    $authSelect->setButtonAttributes('movedown',
                                     array('class' => 'inputCommand'));

    // template for a dual multi-select element shape
    $template = '<table{class}>'
        . '<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->'
        . '<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr>'
        . '<!-- END label_3 -->'
        . '<tr>'
        . '  <td>{selected}</td>'
        . '  <td align="center">'
        . '    {add}<br />{remove}<br /><br />{moveup}<br />{movedown}'
        . '  </td>'
        . '  <td>{unselected}</td>'
        . '</tr>'
        . '</table>'
        . '{javascript}';

    $authSelect->setElementTemplate($template);

    $form->addElement('submit', 'Submit', 'Save');

    $defaults['name'] = $user->name;
    $defaults['email'] = $user->email;
    if (is_array($user->collaborators)) {
        foreach ($user->collaborators as $collaborator) {
            $defaults['authors'][] = $collaborator->author_id;
        }
    }
    $form->setDefaults($defaults);
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

    $table->updateColAttributes(0, array('id' => 'emph', 'width' => '30%'));
    echo $renderer->toHtml(($table->toHtml())) . '</div>';
}
else {
    $table->addRow(array('Login:', $user->login));
    $table->addRow(array('Name:', $user->name));
    $table->addRow(array('E-mail:', $user->email));

    if (is_array($user->collaborators)) {
        $rowcount = 0;
        foreach ($user->collaborators as $collaborator) {
            if ($rowcount == 0)
                $cell1 = 'Favorite Collaborators:';
            else
                $cell1 = '';
            $table->addRow(array($cell1, $collaborator->name));
            $rowcount++;
        }
    }
    else {
        $table->addRow(array('Favorite Collaborators:', 'None assigned'));
    }
    $table->addRow(array(''));
    $table->addRow(array('<a href="edit_user.php?status=edit">'
                         . 'Edit this information</a>'),
                   array('colspan' => 2));

    $table->updateColAttributes(0, array('id' => 'emph', 'width' => '30%'));
    print $table->toHtml();
}

echo '</div>';

$db->close();

pageFooter();

?>

