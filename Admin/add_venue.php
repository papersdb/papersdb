<?php ;

// $Id: add_venue.php,v 1.15 2006/08/04 18:00:33 aicmltec Exp $

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
require_once 'includes/jscalendar.php';

/**
 * Renders the whole page.
 */
class add_venue extends pdHtmlPage {
    var $venue_id = null;

    function add_venue() {
        global $access_level;

        parent::pdHtmlPage('add_venue');

        if ($access_level <= 0) {
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

        if (isset($_GET['numNewOccurrences'])
            && ($_GET['numNewOccurrences'] != '')) {
            $newOccurrence =  intval($_GET['numNewOccurrences']);
        }
        else if (isset($_POST['numNewOccurrences'])
            && ($_POST['numNewOccurrences'] != '')) {
            $newOccurrence =  intval($_POST['numNewOccurrences']);
        }
        else {
            $newOccurrence = count($venue->occurrence);
        }

        $form = new HTML_QuickForm('venueForm', 'post',
                                   './add_venue.php?submit=true');

        if ($venue->venue_id != '')
            $label = 'Edit Venue';
        else
            $label = 'Add Venue';

        if ($venue->type == 'Conference')
            $label .= '&nbsp;<span id="small"><a href="javascript:dataKeep('
                . ($newOccurrence+1) .')">[Add Occurrence]</a></span>';

        $form->addElement('header', null, $label);

        if ($venue->venue_id != '') {
            $form->addElement('hidden', 'venue_id', $this->venue_id);
            $form->addElement('hidden', 'id', 'true');
        }

        $form->addElement('radio', 'type', 'Type:', 'Journal', 'Journal',
                          array('onClick' => 'dataKeep(' . $newOccurrence . ');'));
        $form->addElement('radio', 'type', null, 'Conference', 'Conference',
                          array('onClick' => 'dataKeep(' . $newOccurrence . ');'));
        $form->addElement('radio', 'type', null, 'Workshop', 'Workshop',
                          array('onClick' => 'dataKeep(' . $newOccurrence . ');'));
        $form->addElement('text', 'title', 'Internal Title:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('title', 'a venue title is required', 'required',
                       null, 'client');
        $form->addElement('text', 'name', 'Venue Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('name', 'a venue name is required', 'required',
                       null, 'client');
        $form->addRule('name', 'venue name cannot be empty',
                       'required', null, 'client');
        $form->addElement('text', 'url', 'Venue URL:',
                          array('size' => 50, 'maxlength' => 250));

        if ($venue->type != '') {
            if (($venue->type == 'Journal') || ($venue->type == 'Workshop')) {
                if ($venue->type == 'Journal')
                    $label = 'Publisher:';
                else
                    $label = 'Associated Conference:';

                $form->addElement('text', 'data', $label,
                                  array('size' => 50, 'maxlength' => 250));
            }

            if ($venue->type == 'Workshop') {
                $form->addElement('text', 'editor', 'Editor:',
                                  array('size' => 50, 'maxlength' => 250));

                $date_options = array(
                    'baseURL' => '../includes/',
                    'styleCss' => 'calendar.css',
                    'language' => 'en',
                    'image' => array(
                        'src' => '../calendar.gif',
                        'border' => 0
                        ),
                    'setup' => array(
                        'inputField' => 'venue_date',
                        'ifFormat' => '%Y-%m-%d',
                        'showsTime' => false,
                        'time24' => true,
                        'weekNumbers' => false,
                        'showOthers' => true
                        )
                    );

                $form->addGroup(
                    array(
                        HTML_QuickForm::createElement(
                            'text', 'venue_date', null,
                            array('readonly' => '1', 'id' => 'venue_date',
                                  'size' => 10)),
                        HTML_QuickForm::createElement(
                            'jscalendar', 'startdate_calendar', null,
                            $date_options)
                        ),
                    'dateGroup', 'Date:', '&nbsp;', false);
            }

            if ($venue->type == 'Conference') {
                $form->addElement('hidden', 'numNewOccurrences',
                                  $newOccurrence);

                for ($i = 0; $i < $newOccurrence; $i++) {
                    $form->addElement('header', null, 'Occurrence ' . ($i + 1));
                    $form->addElement('text',
                                      'newOccurrenceYear[' . $i . ']',
                                      'Year:',
                                      array('size' => 50, 'maxlength' => 250));
                    $form->addElement('text',
                                      'newOccurrenceLocation[' . $i . ']',
                                      'Location:',
                                      array('size' => 50, 'maxlength' => 250));
                }
            }
        }

        if ($venue->venue_id != '')
            $label = 'Edit Venue';
        else
            $label = 'Add Venue';

        $form->addGroup(
            array(
                HTML_QuickForm::createElement('submit', 'Submit', $label),
                HTML_QuickForm::createElement('reset', 'Reset', 'Reset')
                ),
            'submit_group', null, '&nbsp;', false);

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
                $arr = array('venue_id' => $venue->venue_id,
                             'title'    => $venue->title,
                             'name'     => $venue->name,
                             'url'      => $venue->url,
                             'type'     => $venue->type,
                             'data'     => $venue->data,
                             'editor'   => $venue->editor,
                             'venue_date' => $venue->date);
                if (count($venue->occurrence) > 0) {
                    $c = 0;
                    foreach ($venue->occurrence as $year => $location) {
                        $arr['newOccurrenceYear'][$c] = $year;
                        $arr['newOccurrenceLocation'][$c] = $location;
                        $c++;
                    }
                }

                $form->setDefaults($arr);
            }
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
        function dataKeep(num) {
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
                    else if (element.name == "numNewOccurrences") {
                        qsArray.push(element.name + "=" + num);
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
$access_level = check_login();
$page = new add_venue();
echo $page->toHtml();

?>
