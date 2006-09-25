<?php ;

// $Id: add_venue.php,v 1.21 2006/09/25 19:59:09 aicmltec Exp $

/**
 * This page displays, edits and adds venues.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';
require_once 'includes/jscalendar.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_venue extends pdHtmlPage {
    var $venue_id = null;

    function add_venue() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('add_venue');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        $venue = new pdVenue();

        if (isset($_GET['venue_id']) && ($_GET['venue_id'] != '')) {
            $this->venue_id = intval($_GET['venue_id']);
        }
        else if (isset($_POST['venue_id']) && ($_POST['venue_id'] != '')) {
            $this->venue_id = intval($_POST['venue_id']);
        }

        if ($this->venue_id != null)
            $venue->dbLoad($db, $this->venue_id);

        if (isset($_GET['type']) && ($_GET['type'] != ''))
            $venue->type = $_GET['type'];
        else if (isset($_POST['type']) && ($_POST['type'] != ''))
            $venue->type = $_POST['type'];

        if (isset($_GET['numNewOccurrences'])
            && ($_GET['numNewOccurrences'] != '')) {
            $newOccurrences =  intval($_GET['numNewOccurrences']);
        }
        else if (isset($_POST['numNewOccurrences'])
            && ($_POST['numNewOccurrences'] != '')) {
            $newOccurrences =  intval($_POST['numNewOccurrences']);
        }
        else {
            $newOccurrences = count($venue->occurrences);
        }

        $form = new HTML_QuickForm('venueForm', 'post',
                                   './add_venue.php?submit=true');

        if ($this->venue_id != '')
            $label = 'Edit Venue';
        else
            $label = 'Add Venue';

        $this->pageTitle = $label;

        if ($venue->type == 'Conference')
            $label .= '&nbsp;<span id="small"><a href="javascript:dataKeep('
                . ($newOccurrences+1) .')">[Add Occurrence]</a></span>';

        $form->addElement('header', null, $label);

        if ($this->venue_id != '') {
            $form->addElement('hidden', 'venue_id', $this->venue_id);
        }

        $form->addElement('radio', 'type', 'Type:', 'Journal', 'Journal',
                          array('onClick' => 'dataKeep(' . $newOccurrences . ');'));
        $form->addElement('radio', 'type', null, 'Conference', 'Conference',
                          array('onClick' => 'dataKeep(' . $newOccurrences . ');'));
        $form->addElement('radio', 'type', null, 'Workshop', 'Workshop',
                          array('onClick' => 'dataKeep(' . $newOccurrences . ');'));
        $form->addElement('text', 'title', 'Internal Title:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('title', 'a venue title is required', 'required',
                       null, 'client');
        $form->addElement('text', 'name', 'Venue Name:',
                          array('size' => 50, 'maxlength' => 250));
        $form->addRule('name', 'a venue name is required', 'required',
                       null, 'client');
        $form->addRule('name', 'venue name cannot be left blank',
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
                        'src' => '../images/calendar.gif',
                        'border' => 0
                        ),
                    'setup' => array(
                        'inputField' => 'venue_date',
                        'ifFormat' => '%Y-%m',
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
                            'jscalendar', 'venue_date_calendar', null,
                            $date_options)
                        ),
                    'dateGroup', 'Date:', '&nbsp;', false);
            }

            if ($venue->type == 'Conference') {
                $form->addElement('hidden', 'numNewOccurrences',
                                  $newOccurrences);

                for ($i = 0; $i < $newOccurrences; $i++) {
                    $occur_date_arr_name = '$occur_date_options' . $i;
                    $$occur_date_arr_name = array(
                        'baseURL' => '../includes/',
                        'styleCss' => 'calendar.css',
                        'language' => 'en',
                        'image' => array(
                            'src' => '../images/calendar.gif',
                            'border' => 0
                            ),
                        'setup' => array(
                            'inputField' => 'occur_date' . $i,
                            'ifFormat' => '%Y-%m-%d',
                            'showsTime' => false,
                            'time24' => true,
                            'weekNumbers' => false,
                            'showOthers' => true
                            )
                        );

                    $form->addElement('header', null, 'Occurrence ' . ($i + 1));
                    $form->addElement('text',
                                      'newOccurrenceLocation[' . $i . ']',
                                      'Location:',
                                      array('size' => 50, 'maxlength' => 250));
                    $form->addRule('newOccurrenceLocation[' . $i . ']',
                                   'venue occurrence ' . ($i + 1)
                                   . ' location cannot be left blank',
                                   'required', null, 'client');
                    $form->addGroup(
                        array(
                            HTML_QuickForm::createElement(
                                'text', 'newOccurrenceDate[' . $i . ']', null,
                                array('readonly' => '1',
                                      'id' => 'occur_date' . $i,
                                      'size' => 10)),
                            HTML_QuickForm::createElement(
                                'jscalendar', 'occur_calendar' . $i, null,
                                $$occur_date_arr_name)
                            ),
                        'occur_dateGroup' . $i, 'Date:', '&nbsp;', false);
                    $form->addGroupRule(
                        'occur_dateGroup' . $i,
                        array(
                            'newOccurrenceDate[' . $i . ']'
                            => array(array('venue occurrence ' . ($i + 1)
                                           . ' date cannot be left blank',
                                           'required', null, 'client'))),
                        'required', null, null, 'client');

                    $form->addElement('text',
                                      'newOccurrenceUrl[' . $i . ']',
                                      'Conference URL:',
                                      array('size' => 50, 'maxlength' => 250));

                    $form->addElement('button', 'delOccurrence[' . $i . ']',
                                      'Delete',
                                      'onClick=dataRemove(' . $i . ');');
                }
            }
        }

        if ($this->venue_id != '')
            $label = 'Submit';
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

            $venue->deleteOccurrences();
            for ($i = 0; $i < $values['numNewOccurrences']; $i++) {
                $venue->addOccurrence($values['newOccurrenceLocation'][$i],
                                      $values['newOccurrenceDate'][$i],
                                      $values['newOccurrenceUrl'][$i]);
            }

            $venue->dbSave($db);

            if (!isset($this->venue_id) || ($this->venue_id == '')) {
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
            if ($this->venue_id != '') {
                $arr = array('title'    => $venue->title,
                             'name'     => $venue->name,
                             'url'      => $venue->url,
                             'type'     => $venue->type,
                             'data'     => $venue->data,
                             'editor'   => $venue->editor,
                             'venue_date' => $venue->date);
                if (isset($_GET['numNewOccurrences'])) {
                    for ($i = 0; $i < $_GET['numNewOccurrences']; $i++) {
                        $arr['newOccurrenceLocation'][$c]
                            = $_GET['newOccurrenceLocation'][$c];
                        $arr['newOccurrenceDate'][$c]
                            = $_GET['newOccurrenceDate'][$c];
                        $arr['newOccurrenceUrl'][$c]
                            = $_GET['newOccurrenceUrl'][$c];
                    }
                }
                else if (count($venue->occurrences) > 0) {
                    $c = 0;
                    foreach ($venue->occurrences as $o) {
                        $arr['newOccurrenceLocation'][$c] = $o->location;
                        $arr['newOccurrenceDate'][$c] = $o->date;
                        $arr['newOccurrenceUrl'][$c] = $o->url;
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

        function dataRemove(num) {
            var qsArray = new Array();
            var qsString = "";
            var indexYear = 0;
            var indexLocation = 0;
            var indexDate = 0;
            var indexUrl = 0;

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
                        numOccur = parseInt(element.value) - 1;
                        qsArray.push(element.name + "=" + numOccur);
                    }
                    else if (element.name.indexOf("newOccurrenceLocation") >= 0) {
                        if (element.name != "newOccurrenceLocation[" + num + "]") {
                            qsArray.push("newOccurrenceLocation["
                                         + indexLocation + "]="
                                         + element.value);
                            indexLocation++;
                        }
                    }
                    else if (element.name.indexOf("newOccurrenceDate") >= 0) {
                        if (element.name != "newOccurrenceDate[" + num + "]") {
                            qsArray.push("newOccurrenceDate["
                                         + indexDate + "]=" + element.value);
                            indexDate++;
                        }
                    }
                    else if (element.name.indexOf("newOccurrenceUrl") >= 0) {
                        if (element.name != "newOccurrenceUrl[" + num + "]") {
                            qsArray.push("newOccurrenceUrl["
                                         + indexUrl + "]=" + element.value);
                            indexUrl++;
                        }
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
