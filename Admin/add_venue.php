<?php ;

// $Id: add_venue.php,v 1.10 2006/07/13 23:31:51 aicmltec Exp $

/**
 * \file
 *
 * \brief This page displays, edits and adds venues.
 *
 * Depending on a the varaible passed is what segment of the page is used.  If
 * $status == view then, it displays a list of all the venues and some
 * information about them. It links to whether you would like to add a new
 * venue, or edit/delete an existing one.
 *
 * If there is no variable passed, it displays the form to add a new venue.
 *
 * If $status == change, then it displays the same form, but with the values
 * already filled in, as the user is editing a venue.
 *
 * If $submit == true then it takes the input it passed itself and adds it to
 * the database. If it is passed a venue_id then it replaces the information,
 * if it is not passed a venue_id, it adds a new id to the database and puts
 * the information there.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';

/**
 * Renders the whole page.
 */
class add_venue extends pdHtmlPage {
    var $venue_id = null;

    function add_venue() {
        global $logged_in;

        parent::pdHtmlPage('add_venue');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        $venue = new pdVenue();

        if (isset($_GET['venue_id']) && ($_GET['venue_id'] != '')) {
            $this->venue_id = intval($_GET['venue_id']);
            $venue->dbLoad($db, $this->venue_id);
        }

        if (isset($_GET['type']) && ($_GET['type'] != ''))
            $venue->type = $_GET['type'];
        else if (isset($_POST['type']) && ($_POST['type'] != ''))
            $venue->type = $_POST['type'];

        $form = new HTML_QuickForm('venueForm', 'post',
                                   './add_venue.php?submit=true');

        if ($venue->venue_id != '') {
            $form->addElement('hidden', 'venue_id', $this->venue_id);
            $form->addElement('hidden', 'id', 'true');
            $form->addElement('submit', 'Submit', 'Edit Venue');
        }
        else {
            $form->addElement('submit', 'Submit', 'Add Venue');
        }

        $form->addElement('radio', 'type', null, 'Journal', 'Journal',
                          array('onClick' => 'dataKeep();'));
        $form->addElement('radio', 'type', null, 'Conference', 'Conference',
                          array('onClick' => 'dataKeep();'));
        $form->addElement('radio', 'type', null, 'Workshop', 'Workshop',
                          array('onClick' => 'dataKeep();'));
        $form->addElement('text', 'title', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('title', 'a venue title is required', 'required',
                       null, 'client');
        $form->addElement('text', 'name', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('name', 'a venue name is required', 'required',
                       null, 'client');
        $form->addRule('name', 'venue name cannot be empty',
                       'required', null, 'client');
        $form->addElement('text', 'url', null,
                          array('size' => 50, 'maxlength' => 250));

        if ($venue->type != '') {
            $form->addElement('text', 'data', null,
                              array('size' => 50, 'maxlength' => 250));
            if ($venue->type == 'Workshop')
                $form->addElement('text', 'editor', null,
                                  array('size' => 50, 'maxlength' => 250));
            if (($venue->type == 'Conference') || ($venue->type == 'Workshop'))
                $form->addElement('text', 'date', null,
                                  array('size' => 10, 'maxlength' => 10));
        }

        $form->addElement('reset', 'Reset', 'Reset');

        if ($form->validate()) {
            $values = $form->exportValues();

            $venue->load($values);

            //add http:// to webpage address if needed
            if (strpos($venue->url, 'http') === false) {
                $venue->url = "http://" . $venue->url;
            }
            $venue->title = str_replace("\"","'", $venue->title);
            $venue->dbSave($db);

            if (!isset($venue->venue_id) || ($venue->venue_id == '')) {
                $this->contentPre .= 'You have successfully added the venue "'
                    .  $venue->title . '".'
                    . '<br><a href="./add_venue.php">Add another venue</a>';
            }
            else {
                $this->contentPre .= 'You have successfully edited the venue "'
                    . $venue->title . '".';
            }
        }
        else {
            $form->setDefaults($_GET);
            if ($venue->venue_id != '') {
                $form->setDefaults(array('venue_id' => $venue->venue_id,
                                         'title'    => $venue->title,
                                         'name'     => $venue->name,
                                         'url'      => $venue->url,
                                         'type'     => $venue->type,
                                         'data'     => $venue->data,
                                         'editor'   => $venue->editor,
                                         'date'     => $venue->date));
            }
            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table = new HTML_Table(array('width' => '100%',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));
            $table->setAutoGrow(true);

            $table->addRow(array('Type:',
                                 $renderer->elementToHtml('type', 'Journal')));
            $table->addRow(array('',
                                 $renderer->elementToHtml('type', 'Conference')));
            $table->addRow(array('',
                                 $renderer->elementToHtml('type', 'Workshop')));
            $table->addRow(array('Internal Title:',
                                 $renderer->elementToHtml('title')));
            $table->addRow(array('Venue Name:',
                                 $renderer->elementToHtml('name')));
            $table->addRow(array('Venue URL:',
                                 $renderer->elementToHtml('url')));

            if ($venue->type != '') {
                if ($venue->type == 'Conference')
                    $cell1 = 'Location';
                else if ($venue->type == 'Journal')
                    $cell1 = 'Publisher';
                else if ($venue->type == 'Workshop')
                    $cell1 = 'Associated Conference';
                $table->addRow(array($cell1 . ':',
                                     $renderer->elementToHtml('data')));

                if ($venue->type == 'Workshop')
                    $table->addRow(array('Editor:',
                                         $renderer->elementToHtml('editor')));


                if (($venue->type == 'Conference')
                    || ($venue->type == 'Workshop'))
                    $table->addRow(array('Date:',
                                         $renderer->elementToHtml('date')
                                         . '<a href="doNothing()" '
                                         . 'onClick="setDateField('
                                         . 'document.venueForm.date);'
                                         . 'top.newWin=window.open('
                                         . '\'../calendar.html\','
                                         . '\'cal\',\'dependent=yes,width=230,'
                                         . 'height=250,screenX=200,screenY=300,'
                                         . 'titlebar=yes\')">'
                                         . '<img src="../calendar.gif" '
                                         . 'border=0></a> '
                                         . '(yyyy-mm-dd) '));
            }

            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '25%'));

            if ($venue->venue_id != '')
                $this->contentPre .= '<h3>Edit Venue</h3>';
            else
                $this->contentPre .= '<h3>Add Venue</h3>';

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->table =& $table;
            $this->javascript();
        }
        $db->close();
    }

    function javascript() {
        $this->js = <<< JS_END
            <script language="JavaScript" type="text/JavaScript">

            function closewindow() {
            window.close();
        }
        function dataKeep() {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["venueForm"].elements.length; i++) {
                var element = document.forms["venueForm"].elements[i];
                if ((element.type != "submit") && (element.type != "reset")
                    && (element.value != "") && (element.value != null)) {

                    if (element.name == "venue_id") {
                        qsArray.push(element.name + "=" + element.value);
                        qsArray.push("status=change");
                    }
                    else if (element.name == "type") {
                        if (element.checked) {
                            qsArray.push(element.name + "="
                                         + element.value.replace("\"","'"));
                        }
                    }
                    else {
                        qsArray.push(element.name + "=" + element.value);
                    }
                }
            }

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace("\"", "?");
                qsString.replace(" ", "%20");
            }

            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }
        </script>
JS_END;
    }
}

session_start();
$logged_in = check_login();
$page = new add_venue();
echo $page->toHtml();

?>
