<?php ;

// $Id: add_pub1.php,v 1.33 2007/04/10 15:56:51 aicmltec Exp $

/**
 * This page is the form for adding/editing a publication.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requires the base class and classes to access the database. */
require_once 'Admin/add_pub_base.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdExtraInfoList.php';
require_once 'includes/authorselect.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub1 extends add_pub_base {
    var $debug = 0;

    function add_pub1() {
        parent::add_pub_base();

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        if (isset($_SESSION['pub'])) {
            // according to session variables, we are already editing a
            // publication
            $this->pub =& $_SESSION['pub'];
        }
        else if ($this->pub_id != '') {
            // pub_id passed in with $_GET variable
            $this->db = dbCreate();
            $this->pub = new pdPublication();
            $result = $this->pub->dbLoad($this->db, $this->pub_id);
            if (!$result) {
                $this->pageError = true;
                return;
            }

            $_SESSION['pub'] =& $this->pub;
        }
        else {
            // create a new publication
            $this->pub = new pdPublication();
            $_SESSION['pub'] =& $this->pub;
        }

        if ($this->pub->pub_id != '')
            $this->page_title = 'edit_publication';

        $form = new HTML_QuickForm('add_pub2');
        $form->addElement('header', null, 'Add Publication');

        // title
        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'titleHelp') . ':',
                          array('size' => 60, 'maxlength' => 250));
        $form->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        // Venue
        $venue_sel1 = array('All Venues', 'Journal', 'Conference',
                            'Workshop');
        $venues = array(new pdVenueList($this->db,
                                        array('concat' => true)),
                        new pdVenueList($this->db,
                                        array('type' => 'Journal',
                                              'concat' => true)),
                        new pdVenueList($this->db,
                                        array('type' => 'Conference',
                                              'concat' => true)),
                        new pdVenueList($this->db,
                                        array('type' => 'Workshop',
                                              'concat' => true)));

        $common = array(''   => '--Select Venue--',
                        '-1' => '--No venue--');

        $venue_sel2[0] = $common + $venues[0]->list;
        $venue_sel2[1] = $common + $venues[1]->list;
        $venue_sel2[2] = $common + $venues[2]->list;
        $venue_sel2[3] = $common + $venues[3]->list;

        // check if user info has 'Used by me' to venues
        $user =& $_SESSION['user'];
        $user->venueIdsGet($this->db);
        if (count($user->venue_ids) > 0) {
            array_push($venue_sel1, 'Used by me');
            $venue_sel2[4] = $common + $user->venue_ids;
        }

        $sel =& $form->addElement(
            'hierselect', 'venue_id',
            $this->helpTooltip('Venue', 'venueHelp') . ':',
            array('style' => 'width: 70%;'), '<br/>');
        $sel->setOptions(array($venue_sel1, $venue_sel2));

        $form->addElement('submit', 'add_venue', 'Add New Venue');

        $form->addElement('textarea', 'abstract',
                          $this->helpTooltip('Abstract', 'abstractHelp')
                          . ':<br/><div class="small">HTML Enabled</div>',
                          array('cols' => 60, 'rows' => 10));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'keywords', null,
                    array('size' => 60, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span class="small">separate using semi-colons (;)</span>')),

            'kwgroup', $this->helpTooltip('Keywords', 'keywordsHelp') . ':',
            '<br/>', false);

        // rankings radio selections
        $rankings = pdPublication::rankingsGlobalGet($this->db);
        foreach ($rankings as $rank_id => $description) {
            $radio_rankings[] = HTML_QuickForm::createElement(
                'radio', 'paper_rank', null, $description, $rank_id);
        }
        $radio_rankings[] = HTML_QuickForm::createElement(
            'radio', 'paper_rank', null,
            'other (fill in box below)', -1);
        $radio_rankings[] = HTML_QuickForm::createElement(
            'text', 'paper_rank_other', null,
            array('size' => 30, 'maxlength' => 250));

        $form->addGroup($radio_rankings, 'group_rank', 'Ranking:', '<br/>',
                        false);

        // collaborations radio selections
        $collaborations = pdPublication::collaborationsGet($this->db);

        foreach ($collaborations as $col_id => $description) {
            $radio_cols[] = HTML_QuickForm::createElement(
                'checkbox', 'paper_col[' . $col_id . ']', null, $description,
                1);
        }

        $form->addGroup($radio_cols, 'group_collaboration',
                        'Collaboration:', '<br/>', false);

        $form->addElement('textarea', 'user',
                          $this->helpTooltip('User Info:', 'userInfoHelp'),
                          array('cols' => 60, 'rows' => 2));

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $form->addElement('date', 'pub_date', 'Date:',
                          array('format' => 'YM', 'minYear' => '1985'));

        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "location.href='" . $url . "';"));
        $buttons[] = HTML_QuickForm::createElement(
            'reset', 'reset', 'Reset');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'next', 'Next step >>');

        if ($this->pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->db =& $this->db;
        $this->form =& $form;

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array('title'    => $this->pub->title,
                          'abstract' => $this->pub->abstract,
                          'keywords' => $this->pub->keywords,
                          'user'     => $this->pub->user);

        if (isset($this->pub->rank_id)) {
            $defaults['paper_rank'] = $this->pub->rank_id;
            if ($this->pub->rank_id == -1)
                $defaults['paper_rank_other'] = $this->pub->ranking;
        }

        if (is_array($this->pub->collaborations)
            && (count($this->pub->collaborations) > 0)) {
            foreach ($this->pub->collaborations as $col_id) {
                $defaults['paper_col'][$col_id] = 1;
            }
        }

        if (is_object($this->pub->venue)) {
            switch ($this->pub->venue->type) {
                case 'Journal':    $type = 1; break;
                case 'Conference': $type = 2; break;
                case 'Workshop':   $type = 3; break;
                default: $type = 0;
            }
            $defaults['venue_id'] = array($type, $this->pub->venue_id);
        }

        if (!isset($this->pub->published) || ($this->pub->published == '')) {
            $defaults['pub_date'] = array('Y' => date('Y'), 'M' => date('m'));
        }
        else {
            $date = explode('-', $this->pub->published);

            $defaults['pub_date']['Y'] = $date[0];
            $defaults['pub_date']['M'] = $date[1];
        }

        $this->form->setDefaults($defaults);

        if (isset($_SESSION['pub']) && ($_SESSION['pub']->title != '')) {
            $this->pub =& $_SESSION['pub'];

            echo '<h3>Adding Following Publication</h3>'
                . $this->pub->getCitationHtml('..', false) . '<p/>'
                . add_pub_base::similarPubsHtml();
        }

        $renderer =& $this->form->defaultRenderer();

        $this->form->setRequiredNote(
            '<font color="#FF0000">*</font> shows the required fields.');

        $renderer->setFormTemplate(
            '<table width="100%" bgcolor="#CCCC99">'
            . '<form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');
        $renderer->setGroupTemplate(
            '<table><tr>{content}</tr></table>', 'name');

        $renderer->setElementTemplate(
            '<tr><td colspan="2">{label}</td></tr>',
            'categoryinfo');

        $this->form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');
        $form =& $this->form;

        $values = $form->exportValues();

        $this->pub->load($values);
        $this->pub->published = $values['pub_date']['Y'] . '-'
            .  $values['pub_date']['M'] . '-1';
        $_SESSION['state'] = 'pub_add';

        if (isset($values['venue_id'][1])
            && is_numeric($values['venue_id'][1]))
            if ($values['venue_id'][1] > 0)
                $this->pub->addVenue($this->db, $values['venue_id'][1]);
            else if (($values['venue_id'][1] == -1)
                     && is_object($this->pub->venue)) {
                unset($this->pub->venue);
                unset($this->pub->venue_id);
            }

        if (isset($values['paper_rank']))
            $this->pub->rank_id = $values['paper_rank'];

        if (($values['paper_rank'] == -1)
            && (strlen($values['paper_rank_other']) > 0)) {
            $this->pub->rank_id = -1;
            $this->pub->ranking = $values['paper_rank_other'];
        }

        if (isset($values['paper_col'])
            && (count($values['paper_col']) > 0)) {
            $this->pub->collaborations = array_keys($values['paper_col']);
        }

        $result = $this->pub->duplicateTitleCheck($this->db);
        if (count($result) > 0)
            $_SESSION['similar_pubs'] = $result;

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('pub', $this->pub);
            return;
        }

        if (isset($values['add_venue']))
            header('Location: add_venue.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub2.php');
    }

    function javascript() {
        $js_file = FS_PATH . '/Admin/js/add_pub1.js';
        assert('file_exists($js_file)');
        $this->js = file_get_contents($js_file);
    }
}

$page = new add_pub1();
echo $page->toHtml();

?>
