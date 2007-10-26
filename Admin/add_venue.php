<?php ;

// $Id: add_venue.php,v 1.53 2007/10/26 22:03:15 aicmltec Exp $

/**
 * This page displays, edits and adds venues.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenue.php';
require_once 'includes/pdPublication.php';
require_once 'Admin/add_pub_base.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_venue extends pdHtmlPage {
    private $debug = 0;
    private $venue_id = null;
    private $venue;
    private $cat_id;
    private $title;
    private $name;
    private $url;
    private $v_usage;
    private $numNewOccurrences;
    private $newOccurrenceLocation;
    private $newOccurrenceDate;
    private $newOccurrenceUrl;
    private $newOccurrences;
    private $referer;

    function __construct() {
        parent::__construct('add_venue');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (!isset($this->referer)) {
            $referer = getenv('HTTP_REFERER');
            if (strpos($referer, 'list_venues.php') !== false) {
                $this->referer = $referer;
            }
        }

        $this->venue = new pdVenue();

        if ($this->venue_id != null)
            $this->venue->dbLoad($this->db, $this->venue_id);

        if (!empty($this->cat_id))
            $this->venue->categoryAdd($this->db, $this->cat_id);
        else if (is_object($this->venue->category))
            $this->cat_id = $this->venue->category->cat_id;

        $this->newOccurrences = 0;
        if (is_object($this->venue->category)
            && (($this->venue->category->category == 'In Conference')
                || ($this->venue->category->category == 'In Workshop'))) {
            if (isset($this->numNewOccurrences)
                && is_numeric($this->numNewOccurrences))
                $this->newOccurrences = intval($this->numNewOccurrences);
            else
                $this->newOccurrences = count($this->venue->occurrences);
        }

        $this->form = new HTML_QuickForm('venueForm', 'post',
                                         './add_venue.php?submit=true');
        $form =& $this->form;

        if (isset($_SESSION['state']) && ($_SESSION['state'] == 'pub_add')) {
            $pub =& $_SESSION['pub'];

            if (isset($pub->pub_id))
                $this->page_title = 'Edit Publication';
            else
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

        if (is_object($this->venue->category)
            && (($this->venue->category->category == 'In Conference')
                || ($this->venue->category->category == 'In Workshop')))
            $label .= '&nbsp;<span class="small"><a href="javascript:dataKeep('
                . ($this->newOccurrences+1) .')">[Add Occurrence]</a></span>';

        $form->addElement('header', null, $label);

        if ($this->venue_id != '') {
            $form->addElement('hidden', 'venue_id', $this->venue_id);
        }

        $form->addElement('hidden', 'referer', $this->referer);

        // category
        $category_list = new pdCatList($this->db);

        // Remove "In " from category names
        foreach ($category_list->list as $key => $category) {
            if (strpos($category, 'In ') === 0)
                $category_list->list[$key] = substr($category, 3);
        }

        $form->addElement(
            'select', 'cat_id',
            $this->helpTooltip('Category', 'categoryHelp') . ':',
            array(''  => '--- Please Select a Category ---',
                  '-1' => '-- No Category --')
            + $category_list->list,
            array('onchange' => 'dataKeep();'));

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

        // rankings radio selections
        $rankings = pdVenue::rankingsGlobalGet($this->db);
        foreach ($rankings as $rank_id => $description) {
            $radio_rankings[] = HTML_QuickForm::createElement(
                'radio', 'venue_rank', null, $description, $rank_id);
        }
        $radio_rankings[] = HTML_QuickForm::createElement(
            'radio', 'venue_rank', null,
            'other (fill in box below)', -1);
        $radio_rankings[] = HTML_QuickForm::createElement(
            'text', 'venue_rank_other', null,
            array('size' => 30, 'maxlength' => 250));

        $form->addGroup($radio_rankings, 'group_rank', 'Ranking:', '<br/>',
                        false);

        if (is_object($this->venue->category)) {
            if (($this->venue->category->category == 'In Journal')
                || ($this->venue->category->category == 'In Workshop')) {
                if ($this->venue->category->category == 'In Journal')
                    $label = 'Publisher:';
                else
                    $label = 'Associated Conference:';

                $form->addElement('text', 'data', $label,
                                  array('size' => 50, 'maxlength' => 250));
            }

            if ($this->venue->category->category == 'In Workshop') {
                $form->addElement('text', 'editor', 'Editor:',
                                  array('size' => 50, 'maxlength' => 250));

                $form->addElement('date', 'venue_date', 'Date:',
                                  array('format' => 'YM', 'minYear' => '1970'));
            }

            if (($this->venue->category->category == 'In Conference')
                || ($this->venue->category->category == 'In Workshop')) {
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
                                            'minYear' => '1970'));

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
                . 'papersdb/Admin/add_pub3.php';
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

    private function renderForm() {
        $form =& $this->form;

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            $defaults[$member] = $this->$member;
        }

        if (isset($this->venue->rank_id)) {
            $defaults['venue_rank'] = $this->venue->rank_id;
            if ($this->venue->rank_id == -1)
                $defaults['venue_rank_other'] = $this->venue->ranking;
        }

        $form->setConstants($defaults);

        if ($this->venue_id != '') {
            $arr = array('title'      => $this->venue->title,
                         'name'       => $this->venue->nameGet(),
                         'url'        => $this->venue->urlGet(),
                         'data'       => $this->venue->data,
                         'editor'     => $this->venue->editor,
                         'venue_date' => $this->venue->date,
                         'v_usage'    => $this->venue->v_usage);

            if (empty($this->cat_id))
                $arr['cat_id'] = -1;
            else if ($this->cat_id > 0)
                $arr['cat_id'] = $this->cat_id;

            if (isset($this->numNewOccurrences)) {
                for ($i = 0; $i < $this->numNewOccurrences; $i++) {
                    if (isset($this->newOccurrenceLocation[$i])) {
                        $arr['newOccurrenceLocation'][$i]
                            = $this->newOccurrenceLocation[$i];
                        $arr['newOccurrenceDate'][$i]
                            = $this->newOccurrenceDate[$i];
                        $arr['newOccurrenceUrl'][$i]
                            = $this->newOccurrenceUrl[$i];
                    }
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

        if (isset($pub->pub_id))
                echo '<h3>Editing Publication Entry</h3>';
            else
                echo '<h3>Adding Publication Entry</h3>';

            echo $pub->getCitationHtml('..', false) . '<p/>'
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

    private function processForm() {
        $form =& $this->form;

        $values = $form->exportValues();
        $this->venue->load($values);

        //add http:// to webpage address if needed
        if (($this->venue->url != '')
            && (strpos($this->venue->url, 'http') === false)) {
            $this->venue->url = "http://" . $this->venue->url;
        }
        $this->venue->title = str_replace('"', "'", $this->venue->title);

        if (isset($values['venue_rank']))
            $this->venue->rank_id = $values['venue_rank'];

        if (isset($values['venue_rank']) && ($values['venue_rank'] == -1)
            && (strlen($values['venue_rank_other']) > 0)) {
            $this->venue->rank_id = -1;
            $this->venue->ranking = $values['venue_rank_other'];
        }

        if (isset($values['venue_date']) && is_object($this->venue->category)
            && ($this->venue->category->category == 'In Workshop')) {
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

        if (isset($_SESSION['state'])
            && ($_SESSION['state'] == 'pub_add')) {
            assert('isset($_SESSION["pub"])');
            $pub =& $_SESSION['pub'];
            $pub->addVenue($this->db, $this->venue);

            if ($this->debug) {
                debugVar('values', $values);
                debugVar('venue', $this->venue);
                return;
            }

            if (isset($values['finish']))
                header('Location: add_pub_submit.php');
            else
                header('Location: add_pub4.php');
        }
        else {
            if (!isset($this->venue_id) || ($this->venue_id == '')) {
                echo 'You have successfully added the venue "';

                if (!empty($this->venue->title))
                    echo  $this->venue->title . '".';
                else
                    echo  $this->venue->name . '".';

                echo '<br><a href="./add_venue.php">Add another venue</a>';
            }
            else {
                echo 'You have successfully edited the venue "';

                if (!empty($this->venue->title))
                    echo $this->venue->title . '".';
                else
                    echo  $this->venue->name . '".';
            }

            if (!empty($this->referer))
                echo '<p/><a href="' . $this->referer . '">Return to venue list</a>';
        }

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('venue', $this->venue);
            return;
        }
    }

    private function javascript() {
        $js_file = FS_PATH . '/Admin/js/add_venue.js';
        assert('file_exists($js_file)');
        $content = file_get_contents($js_file);

        $this->js .= str_replace(array('{host}', '{self}'),
                                 array($_SERVER['HTTP_HOST'],
                                       $_SERVER['PHP_SELF']),
                                 $content);
    }
}

$page = new add_venue();
echo $page->toHtml();

?>
