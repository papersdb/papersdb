<?php ;

// $Id: add_author.php,v 1.15 2006/06/12 23:34:38 aicmltec Exp $

/**
 * \file
 *
 * \brief This is the form portion for adding an author.
 *
 * The changes in the database actually are made in add_publication.php. This
 * is so when the author is added to the database the publication a user is
 * working in is then updated with that author available to them.
 *
 * If the user chooses the "add author to database" link while editing/adding
 * a publication, then it will be a pop-up and when submitted will return to
 * add_publication page with the fields restored and author added to the list.
 *
 * If the user chooses "add new publication" from the list_authors.php
 * page. Then the information will be sent to add_publication and the author
 * will be added to the database, the user will be given confirmation and the
 * option to return to the authors page or go the admin menu(index.php).
 *
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 */
class add_author extends pdHtmlPage {
    function add_author() {
        global $logged_in;

        parent::pdHtmlPage('add_author');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        // Connecting, selecting database
        $db =& dbCreate();

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

        $form->addElement('text', 'firstname', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('firstname', 'a first name is required', 'required',
                       null, 'client');
        $form->addRule('firstname',
                       'the first name cannot contain punctuation',
                       'lettersonly', null, 'client');
        $form->addElement('text', 'lastname', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('lastname', 'a last name is required', 'required', null,
                       'client');
        $form->addRule('firstname', 'the lst name cannot contain punctuation',
                       'lettersonly', null, 'client');
        $form->addElement('text', 'title', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'email', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('email', 'invalid email address', 'email', null,
                       'client');
        $form->addElement('text', 'organization', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'webpage', null,
                          array('size' => 50, 'maxlength' => 250));

        $interests = new pdAuthInterests($db);
        assert('is_array($interests->list)');
        foreach($interests->list as $intr) {
            $options[$intr->interest] = $intr->interest;
        }

        $form->addElement('select', 'interests', null, $options,
                          array('multiple' => 'multiple', 'size' => 4));

        for ($i = 0; $i < $newInterests; $i++) {
            $form->addElement('text', 'newInterests['.$i.']', null,
                              array('size' => 50, 'maxlength' => 250));
        }

        $form->addElement('submit', 'submit', 'Add Author');
        $form->addElement('reset', 'reset', 'Reset');

        $form->addElement('hidden', 'numNewInterests', $newInterests);

        if ($form->validate()) {
            $values = $form->exportValues();

            $author = new pdAuthor();
            $author->name = $values['lastname'] . ', ' . $values['firstname'];
            $author->title = $values['title'];
            $author->email = $values['email'];
            $author->organization = $values['organization'];
            $author->webpage = $values['webpage'];
            $author->interests = array_merge($values['interests'],
                                             $values['newInterests']);

            $this->contentPre .= '<pre>' . print_r($author, true) . '</pre>';

            $this->contentPre .= 'Author "' . $values['firstname'] . ' '
                . $values['lastname'] . '" succesfully added to the database.'
                . '<p/>'
                . '<a href="' . $_SERVER['PHP_SELF'] . '">'
                . 'Add another new author</a>';

            $author->dbSave($db);
        }
        else {
            $this->contentPre .= '<h3>'
                . $this->helpTooltip('Add Author', 'addAuthorPageHelp')
                . '</h3>';

            $form->setDefaults($_GET);

            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table = new HTML_Table(array('width' => '100%',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));
            $table->setAutoGrow(true);

            $table->addRow(array('First Name:',
                                 $renderer->elementToHtml('firstname')));
            $table->addRow(array('Last Name:',
                                 $renderer->elementToHtml('lastname')));
            $table->addRow(array($this->helpTooltip('Title', 'authTitleHelp') . ':',
                                 $renderer->elementToHtml('title')));
            $table->addRow(array('Email:', $renderer->elementToHtml('email')));
            $table->addRow(array('Organization:',
                                 $renderer->elementToHtml('organization')));
            $table->addRow(array('Webpage:', $renderer->elementToHtml('webpage')));

            $ref = '<br/><div id="small"><a href="javascript:dataKeep('
                . ($newInterests+1) .')">[Add Interest]</a></div>';

            $table->addRow(array('Interests:' . $ref,
                                 $renderer->elementToHtml('interests')));

            for ($i = 0; $i < $newInterests; $i++) {
                $table->addRow(array('Interest Name:',
                                     $renderer->elementToHtml(
                                         'newInterests['.$i.']')));
            }


            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '25%'));

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
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
            + "{Prof, PostDoc, PhD student, MSc student, Colleague, etc...}.";

        function dataKeep(num) {
            var temp_qs = "";
            var info_counter = 0;

            for (i = 0; i < document.forms["authorForm"].elements.length; i++) {
                var element = document.forms["authorForm"].elements[i];

                if ((element.type != "submit") && (element.type != "reset")
                    && (element.value != "") && (element.value != null)) {

                    if (info_counter > 0) {
                        temp_qs = temp_qs + "&";
                    }

                    if (element.name == "interests[]") {
                        interest_array = element;
                        var interest_list = "";
                        var interest_count = 0;

                        for (j = 0; j < interest_array.length; j++) {
                            if (interest_array[j].selected == 1) {
                                if (interest_count > 0) {
                                    interest_list += "&";
                                }
                                interest_list += "interests[" + j + "]="
                                    + interest_array[j].value;
                                interest_count++;
                            }
                        }

                        temp_qs += interest_list;
                    }
                    else if (element.name == "numNewInterests") {
                        temp_qs += element.name + "=" + num;
                    }
                    else {
                        temp_qs += element.name + "=" + element.value;
                    }

                    info_counter++;
                }
            }

            temp_qs.replace(" ", "%20");
            temp_qs.replace("\"", "?");
            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + temp_qs;
        }
        </script>
JS_END;
    }
}

$page = new add_author();
echo $page->toHtml();


?>
