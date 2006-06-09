<?php ;

// $Id: add_author.php,v 1.11 2006/06/09 23:18:00 aicmltec Exp $

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

        if (isset($_GET['newInterests'])) {
            $newInterests =  $_GET['newInterests'] + 1;
        }
        else {
            $newInterests = 0;
        }
        $this->contentPre
            .= '<h3>' . helpTooltip('Add Author', 'addAuthorPageHelp') . '</h3>';

        $formAttr = array();
        if($_GET['popup'] != "false")
            $formAttr = array('onsubmit' => "setTimeout('self.close()',0);");

        $form = new HTML_QuickForm('authorForm', 'post',
                                   "./add_publication.php?"
                                   . $_SERVER['QUERY_STRING'],
                                   "add_publication.php",
                                   $formAttr);

        $form->addElement('text', 'firstname', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'lastname', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'auth_title', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'email', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'organization', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('text', 'webpage', null,
                          array('size' => 50, 'maxlength' => 250));

        $interests = new pdAuthInterests();
        $interests->dbLoad($db);
        assert('is_array($interests->list)');
        foreach($interests->list as $intr) {
            $options[$intr->interest_id] = $intr->interest;
        }

        $form->addElement('select', 'interests', null, $options,
                          array('multiple' => 'multiple', 'size' => 4));

        $form->addElement('submit', 'Submit', 'Add Author',
                          array('onClick' => 'return verify();'));
        $form->addElement('reset', 'Reset', 'Reset',
                          array('class' => 'text',
                                'onClick' => 'resetAll();'));

        if ($_GET['popup'])
            $form->addElement('reset', 'Cancel', 'Cancel',
                              array('class' => 'text',
                                    'onClick' => 'closeWindow();'));
        else
            $form->addElement('reset', 'Cancel', 'Cancel',
                              array('class' => 'text',
                                    'onClick' => 'history.back();'));

        $form->addElement('hidden', 'newAuthorSubmitted', 'true');
        $form->addElement('hidden', 'numInterests', $counter + 1);
        $form->addElement('hidden', 'fromauthorspage', 'true');

        for ($i = 0; $i < $newInterests; $i++) {
            $form->addElement('text', 'newInterest' . $i, null,
                              array('size' => 50, 'maxlength' => 250));
        }

        $form->setDefaults($_GET);

        $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
        $form->accept($renderer);


        $tableAttrs = array('width' => '100%',
                            'border' => '0',
                            'cellpadding' => '6',
                            'cellspacing' => '0');
        $table = new HTML_Table($tableAttrs);
        $table->setAutoGrow(true);

        $table->addRow(array('First Name:',
                             $renderer->elementToHtml('firstname')));
        $table->addRow(array('Last Name:',
                             $renderer->elementToHtml('lastname')));
        $table->addRow(array(helpTooltip('Title', 'authTitleHelp') . ':',
                             $renderer->elementToHtml('auth_title')));
        $table->addRow(array('Email:', $renderer->elementToHtml('email')));
        $table->addRow(array('Organization:',
                             $renderer->elementToHtml('organization')));
        $table->addRow(array('Webpage:', $renderer->elementToHtml('webpage')));

        $ref = '<br/><div id="small"><a href="javascript:dataKeep('
            . $newInterests .')">[Add Interest]</a></div>';

        $table->addRow(array('Interests:' . $ref,
                             $renderer->elementToHtml('interests')));

        for ($i = 0; $i < $newInterests; $i++) {
            $table->addRow(array('Interest Name:',
                                 $renderer->elementToHtml('newInterest' . $i)));
        }

        $this->form =& $form;
        $this->renderer =& $renderer;
        $this->table =& $table;
        $this->javascript();
        $db->close();
    }

    function javascript() {
        $this->js = <<<JS_END

            <script language="JavaScript" type="text/JavaScript">
            var addAuthorPageHelp=
            "This window is used to add an author to the database. In order "
            + "to add an author you need to input the author's first name, "
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

        function verify() {
            var form = document.forms["authorForm"];
            if (form.elements["firstname"].value == "") {
                alert("Please enter a complete name for the new author.");
                return false;
            }
            if (form.elements["lastname"].value == "") {
                alert("Please enter a complete name for the new author.");
                return false;
            }
            if ((form.elements["firstname"].value).search(",")
                !=-1){
                alert("Please do not use commas in author's first name");
                return false;
            }
            if ((form.elements["lastname"].value).search(",")
                !=-1){
                alert("Please do not use commas in author's last name");
                return false;
            }
            return true;
        }

        function dataKeep(num) {
            var temp_qs = "";
            var info_counter = 0;

            var form = document.forms["authorForm"];
            for (i = 0; i < form.elements.length; i++) {
                if ((form.elements[i].value != "")
                    && (form.elements[i].value != null)) {
                    if (info_counter > 0) {
                        temp_qs = temp_qs + "&";
                    }

                    if (form.elements[i].name == "interests[]") {
                        interest_array = form.elements['interests[]'];
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

                        temp_qs = temp_qs + interest_list;
                    }
                    else {
                        temp_qs += form.elements[i].name + "="
                            + form.elements[i].value;
                    }

                    info_counter++;
                }
            }

            temp_qs.replace(" ", "%20");
            temp_qs.replace("\"", "?");
            location.replace("./add_author.php?{$_SERVER['QUERY_STRING']}&newInterests=" + num + "&" + temp_qs);
        }
        function closewindow() {window.close();}

        function gotoAuthors() {
            location.replace("../list_author.php?type=view&admin=true&newauthor=true");
        }

        function resetAll() {
            location.replace("./add_author.php?z{$_SERVER['QUERY_STRING']}&newInterests=0");
        }
        </script>

JS_END;
    }
}

$page = new add_author();
echo $page->toHtml();


?>
