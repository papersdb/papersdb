<?php ;

// $Id: add_author.php,v 1.28 2006/08/29 22:04:38 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding or editing author information.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdAuthor.php';

// this is just a stub, see javascript author_check() for the real code
function author_check() {
    return true;
}

/**
 * Renders the whole page.
 */
class add_author extends pdHtmlPage {
    var $author_id = null;

    function add_author() {
        global $access_level;

        $db =& dbCreate();
        $author = new pdAuthor();

        if (isset($_GET['author_id']) && ($_GET['author_id'] != ''))
            $this->author_id = intval($_GET['author_id']);
        else if (isset($_POST['author_id']) && ($_POST['author_id'] != ''))
            $this->author_id = intval($_POST['author_id']);

        if ($this->author_id != null) {
            $result = $author->dbLoad($db, $this->author_id);

            if (!$result) {
                $db->close();
                $this->pageError = true;
                return;
            }
        }

        if ($this->author_id == null)
            parent::pdHtmlPage('add_author');
        else
            parent::pdHtmlPage('edit_author');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        if (isset($_GET['numNewInterests'])
            && ($_GET['numNewInterests'] != '')) {
            $newInterests =  intval($_GET['numNewInterests']);
        }
        else if (isset($_POST['numNewInterests'])
            && ($_POST['numNewInterests'] != '')) {
            $newInterests =  intval($_POST['numNewInterests']);
        }
        else {
            $newInterests = 0;
        }

        $form = new HTML_QuickForm('authorForm');

        $form->addElement('hidden', 'author_id', $this->author_id);

        if ($this->author_id == null)
            $form->addElement('header', null,
                              $this->helpTooltip('Add Author',
                                                 'addAuthorPageHelp',
                                                 'helpHeading'));
        else
            $form->addElement('header', null, 'Edit Author');

        $form->addElement('text', 'firstname', 'First Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('firstname', 'a first name is required', 'required',
                       null, 'client');
        $form->addRule('firstname', 'the first name cannot contain punctuation',
                       'nopunctuation', null, 'client');
        $form->addElement('text', 'lastname', 'Last Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('lastname', 'a last name is required', 'required', null,
                       'client');

        $auth_list = new pdAuthorList($db);
        $form->addElement('select', 'authors_in_db', null, $auth_list->list,
                          array('style' => 'overflow: hidden; visibility: hidden; width: 1px; height: 0;'));

        $form->registerRule('author_check', 'callback', 'author_check');
        // author_check() is actually implemented in javascript
        $form->addRule(array('firstname', 'lastname'),
                       'First and last name: '
                       . 'A similar author already exists in the database',
                       'author_check', true, 'client');

        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'authTitleHelp') . ':',
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'email', 'email:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'organization', 'Organization:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'webpage', 'Webpage:',
                          array('size' => 50, 'maxlength' => 250));

        $interests = new pdAuthInterests($db);

        $ref = '<br/><div id="small"><a href="javascript:dataKeep('
            . ($newInterests+1) .')">[Add Interest]</a></div>';

        $form->addElement('select', 'interests',
                          'Interests:' . $ref,
                          $interests->list,
                          array('multiple' => 'multiple', 'size' => 10));

        for ($i = 0; $i < $newInterests; $i++) {
            $form->addElement('text', 'newInterests['.$i.']',
                              'Interest Name ' . ($i + 1) . ':',
                              array('size' => 50, 'maxlength' => 250));
        }
        if ($this->author_id == null)
            $button_label = 'Add Author';
        else
            $button_label = 'Submit';

        $form->addGroup(
            array(
                HTML_QuickForm::createElement('submit', 'submit', $button_label),
                HTML_QuickForm::createElement('reset', 'reset', 'Reset')
                ),
            'submit_group', null, '&nbsp;');

        $form->addElement('hidden', 'numNewInterests', $newInterests);

        if ($form->validate()) {
            $values = $form->exportValues();

            // check if an author with a similar name already exists
            if ($this->author_id == null) {
                $like_authors = new pdAuthorList($db, $values['firstname'],
                                                 $values['lastname']);
                if (count($like_authors->list) > 0) {
                    $this->contentPre
                        .= 'The following authors have similar names:<ul>';
                    foreach ($like_authors->list as $auth) {
                        $this->contentPre .= '<li>' . $auth . '</li>';
                    }
                    $this->contentPre .= '</ul>New author not submitted.';
                    $db->close();
                    return;
                }
            }

            $author = new pdAuthor();
            if ($this->author_id != null)
                $author->author_id = $this->author_id;

            $author->name = $values['lastname'] . ', ' . $values['firstname'];
            $author->title = $values['title'];
            $author->email = $values['email'];
            $author->organization = $values['organization'];
            $author->webpage = $values['webpage'];
            $author->interests = array_merge($values['interests'],
                                             $values['newInterests']);

            $author->dbSave($db);

            $this->contentPre .= 'Author "' . $values['firstname'] . ' '
                . $values['lastname'] . '" ';
            if ($this->author_id == null)
                $this->contentPre .= 'succesfully added to the database.'
                    . '<p/>'
                    . '<a href="' . $_SERVER['PHP_SELF'] . '">'
                    . 'Add another new author</a>';
            else
                $this->contentPre .= 'modified.';
        }
        else {
            $form->setDefaults($_GET);
            if ($author->author_id != '')
                $form->setDefaults($author->asArray());

            $renderer =& $form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
                . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $form->accept($renderer);
            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->javascript();
        }
        $db->close();
    }

    function javascript() {
        $this->js = <<<JS_END

            <script language="JavaScript" type="text/JavaScript">
            var addAuthorPageHelp=
            "To add an author you need to input the author's first name, "
            + "last name, email address and organization. You must also "
            + "select interet(s) that the author has. To do this you can "
            + "select interest(s) allready in the database by selecting "
            + "them from the listbox. You can select multiple interests "
            + "by control-clicking on them. If you do not see the "
            + "appropriate interest(s) you can add interest(s) using "
            + "the Add Interest link.<br/><br/>"
            + "Clicking the Add Interest link will bring up additional fields "
            + "everytime you click it. You can then type in the name of the "
            + "interest into the new field provided.";

        var authTitleHelp=
            "The title of an author. Will take the form of one of: "
            + "<ul>"
            + "<li>Prof</li>"
            + "<li>PostDoc</li>"
            + "<li>PhD student</li>"
            + "<li>MSc student</li>"
            + "<li>Colleague</li>"
            + "<li>etc</li>"
            + "</ul>";

        function dataKeep(num) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["authorForm"].elements.length; i++) {
                var element = document.forms["authorForm"].elements[i];

                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button")
                    && (element.value != "") && (element.value != null)) {

                    if (element.name == "interests[]") {
                        var interest_count = 0;

                        for (j = 0; j < element.length; j++) {
                            if (element[j].selected == 1) {
                                qsArray.push("interests["
                                             + interest_count + "]="
                                             + element[j].value);
                                interest_count++;
                            }
                        }
                    }
                    else if (element.name == "numNewInterests") {
                        qsArray.push(element.name + "=" + num);
                    }
                    else {
                        qsArray.push(element.name + "=" + element.value);
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace(" ", "%20");
                qsString.replace("\"", "?");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        // client side check to make sure new author not already in DB.
        function author_check(name, num) {
            var form = document.forms["authorForm"];

            if (form.elements["author_id"].length() > 0) return;

            if (name.length != 2) return false;

            var authors_in_db = form.elements["authors_in_db"];
            var newAuthorName = name[1] + ", " + name[0].substr(0, 1);
            var authName;

            newAuthorName = newAuthorName.toLowerCase();

            for (i=0; i < authors_in_db.length; i++) {
                authName = authors_in_db.options[i].text.toLowerCase();
                if (authName.indexOf(newAuthorName) >= 0)
                    return false;
            }
            return true;
        }

        </script>
JS_END;
    }
}

session_start();
$access_level = check_login();
$page = new add_author();
echo $page->toHtml();


?>
