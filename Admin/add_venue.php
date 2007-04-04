<?php ;

// $Id: add_venue.php,v 1.40 2007/04/04 22:48:28 loyola Exp $

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
require_once 'includes/pdPublication.php';
require_once 'Admin/add_pub_base.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_venue extends pdHtmlPage {
    var $debug = 0;
    var $venue_id = null;
    var $venue;
    var $type;
    var $title;
    var $name;
    var $url;
    var $v_usage;
    var $numNewOccurrences;
    var $newOccurrenceLocation;
    var $newOccurrenceDate;
    var $newOccurrenceUrl;
    var $newOccurrences;

    function add_venue() {
        parent::pdHtmlPage('add_venue');

        if ($this->loginError) return;

        $this->loadHttpVars();

        $this->venue = new pdVenue();

        if ($this->venue_id != null)
            $this->venue->dbLoad($this->db, $this->venue_id);

        if (isset($this->type) && ($this->type != ''))
            $this->venue->type = $this->type;

        $this->newOccurrences = 0;
        if (($this->venue->type == 'Conference')
            || ($this->venue->type == 'Workshop')) {
            if (isset($this->numNewOccurrences)
                && is_numeric($this->numNewOccurrences))
                $this->newOccurrences =  intval($this->numNewOccurrences);
            else
                $this->newOccurrences = count($this->venue->occurrences);
        }

        $this->form = new HTML_QuickForm('venueForm', 'post',
                                         './add_venue.php?submit=true');
        $form =& $this->form;

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $this->page_title = 'Add Publication';
            $label = 'Add Venue';
        }
        else if ($this->venue_id != '') {
            $this->page_title = 'Edit Venue';
            $label = 'Edit Venue';
        }
        else {
            $this->page_title = 'Add Venue';
            $label = 'Add Venue';
        }

        if (($this->venue->type == 'Conference')
            || ($this->venue->type == 'Workshop'))
            $label .= '&nbsp;<span class="small"><a href="javascript:dataKeep('
                . ($this->newOccurrences+1) .')">[Add Occurrence]</a></span>';

        $form->addElement('header', null, $label);

        if ($this->venue_id != '') {
            $form->addElement('hidden', 'venue_id', $this->venue_id);
        }

        $form->addElement('radio', 'type', 'Type:', 'Journal', 'Journal',
                          array('onClick'
                                => 'dataKeep(' . $this->newOccurrences . ');'));
        $form->addElement('radio', 'type', null, 'Conference', 'Conference',
                          array('onClick'
                                => 'dataKeep(' . $this->newOccurrences . ');'));
        $form->addElement('radio', 'type', null, 'Workshop', 'Workshop',
                          array('onClick'
                                => 'dataKeep(' . $this->newOccurrences . ');'));

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $form->addElement('advcheckbox', 'v_usage', 'Usage:',
                              'Use for this Publication only', null,
                              array('all', 'single'));
        }

        $form->addElement('text', 'title', 'Acronym:',
                          array('size' => 50, 'maxlength' => 250));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'name', null,
                    array('size' => 50, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', null, null,
                    '<span style="font-size:0.85em;">'
                    . 'If venue has an acronym please append it to the Venue '
                    . 'Name in parenthesis,<br/>eg. International Joint '
                    . 'Conference on Artificial Intelligence (IJCAI).</span>')
                ),
            'venue_name_group', 'Venue Name:', '<br/>', false);

        $form->addGroupRule('venue_name_group',
                            array('name' => array(array(
                                      'a venue name is required', 'required',
                                      null, 'client'))));

        $form->addElement('text', 'url', 'Venue URL:',
                          array('size' => 50, 'maxlength' => 250));

        if ($this->venue->type != '') {
            if (($this->venue->type == 'Journal')
                || ($this->venue->type == 'Workshop')) {
                if ($this->venue->type == 'Journal')
                    $label = 'Publisher:';
                else
                    $label = 'Associated Conference:';

                $form->addElement('text', 'data', $label,
                                  array('size' => 50, 'maxlength' => 250));
            }

            if ($this->venue->type == 'Workshop') {
                $form->addElement('text', 'editor', 'Editor:',
                                  array('size' => 50, 'maxlength' => 250));

                $form->addElement('date', 'venue_date', 'Date:',
                                  array('format' => 'YM', 'minYear' => '1985'));
            }

            if (($this->venue->type == 'Conference')
                || ($this->venue->type == 'Workshop')) {
                $form->addElement('hidden', 'numNewOccurrences',
                                  $this->newOccurrences);

                for ($i = 0; $i < $this->newOccurrences; $i++) {

                    $form->addElement('header', null, 'Occurrence ' . ($i + 1));
                    $form->addElement('text',
                                      'newOccurrenceLocation[' . $i . ']',
                                      'Location:',
                                      array('size' => 50, 'maxlength' => 250));
                    $form->addRule('newOccurrenceLocation[' . $i . ']',
                                   'venue occurrence ' . ($i + 1)
                                   . ' location cannot be left blank',
                                   'required', null, 'client');

                    $form->addElement('date', 'newOccurrenceDate[' . $i . ']',
                                      'Date:',
                                      array('format' => 'YM',
                                            'minYear' => '1985'));

                    $form->addElement('text',
                                      'newOccurrenceUrl[' . $i . ']',
                                      'URL:',
                                      array('size' => 50, 'maxlength' => 250));

                    $form->addElement('button', 'delOccurrence[' . $i . ']',
                                      'Delete Occurrence',
                                      'onClick=dataRemove(' . $i . ');');
                }
            }
        }

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
            $prev_page = substr($_SERVER['PHP_SELF'], 0, $pos)
                . 'papersdb/Admin/add_pub1.php';
            $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

            $buttons[] = HTML_QuickForm::createElement(
                'button', 'prev_step', '<< Previous Step',
                array('onClick' => "location.href='"
                      . $prev_page . "';"));
            $buttons[] = HTML_QuickForm::createElement(
                'button', 'cancel', 'Cancel',
                array('onclick' => "location.href='" . $url . "';"));
            $buttons[] = HTML_QuickForm::createElement(
                'reset', 'reset', 'Reset');
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'next_step', 'Next Step >>');

            $pub =& $_SESSION['pub'];

            if ($pub->pub_id != '')
                $buttons[] = HTML_QuickForm::createElement(
                    'submit', 'finish', 'Finish');

            $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

            add_pub_base::addPubDisableMenuItems();
        }
        else {
            if ($this->venue_id != '')
                $label = 'Submit';
            else
                $label = 'Add Venue';

            $form->addGroup(
                array(
                    HTML_QuickForm::createElement('reset', 'Reset', 'Reset'),
                    HTML_QuickForm::createElement('submit', 'Submit', $label)
                    ),
                'submit_group', null, '&nbsp;', false);
        }

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    function renderForm() {
        $form =& $this->form;

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            $defaults[$member] = $this->$member;
        }

        $form->setConstants($defaults);

        if ($this->venue_id != '') {
            $arr = array('title'      => $this->venue->title,
                         'name'       => $this->venue->nameGet(),
                         'url'        => $this->venue->urlGet(),
                         'type'       => $this->venue->type,
                         'data'       => $this->venue->data,
                         'editor'     => $this->venue->editor,
                         'venue_date' => $this->venue->date,
                         'v_usage'    => $this->venue->v_usage);

            if (isset($this->numNewOccurrences)) {
                for ($i = 0; $i < $this->numNewOccurrences; $i++) {
                    $arr['newOccurrenceLocation'][$i]
                        = $this->newOccurrenceLocation[$i];
                    $arr['newOccurrenceDate'][$i]
                        = $this->newOccurrenceDate[$i];
                    $arr['newOccurrenceUrl'][$i]
                        = $this->newOccurrenceUrl[$i];
                }
            }
            else if (count($this->venue->occurrences) > 0) {
                $c = 0;
                foreach ($this->venue->occurrences as $o) {
                    $arr['newOccurrenceLocation'][$c] = $o->location;
                    $arr['newOccurrenceDate'][$c] = $o->date;
                    $arr['newOccurrenceUrl'][$c] = $o->url;
                    $c++;
                }
            }

            // set the default date for the new occurrences
            if (count($this->venue->occurrences) < $this->newOccurrences) {
                $curdate = array('Y' => date('Y'), 'M' => date('m'));
                for ($i = count($this->venue->occurrences);
                     $i < $this->newOccurrences; ++$i)
                    $arr['newOccurrenceDate'][$i] = $curdate;
            }

            $form->setConstants($arr);
        }
        else {
            $curdate = array('Y' => date('Y'), 'M' => date('m'));
            $arr = array('venue_date' => $curdate);
            for ($i = 0; $i < $this->newOccurrences; ++$i) {
                if (isset($this->numNewOccurrences[$i])) {
                    $arr['newOccurrenceLocation'][$i]
                        = $this->newOccurrenceLocation[$i];
                    $arr['newOccurrenceDate'][$i]
                        = $this->newOccurrenceDate[$i];
                    $arr['newOccurrenceUrl'][$i]
                        = $this->newOccurrenceUrl[$i];
                }
                else
                    $arr['newOccurrenceDate'][$i] = $curdate;
            }
            $form->setConstants($arr);
        }

        if (isset($_SESSION['state'])
            && ($_SESSION['state'] == 'pub_add')) {
            assert('isset($_SESSION["pub"])');
            $pub =& $_SESSION['pub'];

            echo '<h3>Adding Following Publication</h3>'
                . $pub->getCitationHtml('..', false) . '<p/>'
                . add_pub_base::similarPubsHtml();
        }

        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $form->accept($renderer);

        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        $form =& $this->form;

        $values = $form->exportValues();
        $this->venue->load($values);

        //add http:// to webpage address if needed
        if (($this->venue->url != '')
            && (strpos($this->venue->url, 'http') === false)) {
            $this->venue->url = "http://" . $this->venue->url;
        }
        $this->venue->title = str_replace('"', "'", $this->venue->title);

        if (isset($values['venue_date']))
            if (($this->venue->type == 'Conference')
                || ($this->venue->type == 'Workshop')) {
                $this->venue->date = $values['venue_date']['Y']
                    . '-' . $values['venue_date']['M'] . '-1';
            }

        $this->venue->deleteOccurrences();
        if (isset($values['numNewOccurrences'])
            && (count($values['numNewOccurrences']) > 0))
            for ($i = 0; $i < $values['numNewOccurrences']; $i++) {
                $this->venue->addOccurrence(
                    $values['newOccurrenceLocation'][$i],
                    $values['newOccurrenceDate'][$i]['Y']
                    . '-' . $values['newOccurrenceDate'][$i]['M']
                    . '-1',
                    $values['newOccurrenceUrl'][$i]);
            }

        $this->venue->dbSave($this->db);

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('venue', $this->venue);
            return;
        }

        if (isset($_SESSION['state'])
            && ($_SESSION['state'] == 'pub_add')) {
            assert('isset($_SESSION["pub"])');
            $pub =& $_SESSION['pub'];
            $pub->addVenue($this->db, $this->venue);

            if ($this->debug) return;

            if (isset($values['finish']))
                header('Location: add_pub_submit.php');
            else
                header('Location: add_pub2.php');
        }
        else {
            if (!isset($this->venue_id) || ($this->venue_id == '')) {
                echo 'You have successfully added the venue "'
                    .  $this->venue->title . '".'
                    . '<br><a href="./add_venue.php">Add another venue</a>';
            }
            else {
                echo 'You have successfully edited the venue "'
                    . $this->venue->title . '".';
            }
        }
    }

    function javascript() {
        $js_file = 'js/add_venue.js';
        assert('file_exists($js_file)');
        $this->js = file_get_contents($js_file);

        $this->js = str_replace(array('{host}', '{self}'),
                                array($_SERVER['HTTP_HOST'],
                                      $_SERVER['PHP_SELF']),
                                $this->js);
    }
}

$page = new add_venue();
echo $page->toHtml();

?>
