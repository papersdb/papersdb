<?php ;

// $Id: add_pub1.php,v 1.24 2007/03/14 22:14:03 aicmltec Exp $

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
        session_start();

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

        parent::add_pub_base();

        if ($this->loginError) return;

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

        $venue_sel2[0] = array('' => '--Select Venue--') + $venues[0]->list;
        $venue_sel2[1] = array('' => '--Select Venue--') + $venues[1]->list;
        $venue_sel2[2] = array('' => '--Select Venue--') + $venues[2]->list;
        $venue_sel2[3] = array('' => '--Select Venue--') + $venues[3]->list;

        // check if user info has 'Used by me' to venues
        $user =& $_SESSION['user'];
        $user->venueIdsGet($this->db);
        if (count($user->venue_ids) > 0) {
            array_push($venue_sel1, 'Used by me');
            $venue_sel2[4] = array('' => '--Select Venue--') + $user->venue_ids;
        }

        $sel =& $form->addElement(
            'hierselect', 'venue_id',
            $this->helpTooltip('Venue', 'venueHelp') . ':',
            array('style' => 'width: 70%;'), '<br/>');
        $sel->setOptions(array($venue_sel1, $venue_sel2));

        $form->addElement('submit', 'add_venue', 'Add New Venue');

        $form->addElement('textarea', 'abstract',
                          $this->helpTooltip('Abstract', 'abstractHelp')
                          . ':<br/><div id="small">HTML Enabled</div>',
                          array('cols' => 60, 'rows' => 10));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'keywords', null,
                    array('size' => 60, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span id="small">separate using semi-colons (;)</span>')),

            'kwgroup', $this->helpTooltip('Keywords', 'keywordsHelp') . ':',
            '<br/>', false);

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

        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm();
        }

        if ($this->debug) {
            echo 'values<pre>' . print_r($this->pub, true) . '</pre>';
        }
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array('title'    => $this->pub->title,
                          'abstract' => $this->pub->abstract,
                          'keywords' => $this->pub->keywords,
                          'user'     => $this->pub->user);

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

        if ($this->debug) {
            echo 'defaults<pre>' . print_r($defaults, true) . '</pre>';
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

        if ($this->debug) {
            echo 'values<pre>' . print_r($values, true) . '</pre>';
        }

        $this->pub->load($values);
        $this->pub->published = $values['pub_date']['Y'] . '-'
            .  $values['pub_date']['M'] . '-1';
        $_SESSION['state'] = 'pub_add';

        if (isset($values['venue_id'][1]) && ($values['venue_id'][1] > 0))
            $this->pub->addVenue($this->db, $values['venue_id'][1]);

        $result = $this->pub->duplicateTitleCheck($this->db);
        if (count($result) > 0)
            $_SESSION['similar_pubs'] = $result;

        if ($this->debug)
            echo '<pre>' . print_r($_SESSION, true) . '</pre>';
        else if (isset($values['add_venue']))
            header('Location: add_venue.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub2.php');
    }

    function javascript() {
        $this->js = <<<JS_END
            <script language="JavaScript" type="text/JavaScript">

            var venueHelp=
            "Where the paper was published -- specific journal, conference, "
            + "workshop, etc. If many of the database papers are in the same "
            + "venue, you can create a single <b>label</b> for that "
            + "venue, to specify name of the venue, location, date, editors "
            + "and other common information. You will then be able to use "
            + "and re-use that information.";

        var categoryHelp=
            "Category describes the type of document that you are submitting "
            + "to the site. For examplethis could be a journal entry, a book "
            + "chapter, etc.<br/><br/>"
            + "Please use the drop down menu to select an appropriate "
            + "category to classify your paper. If you cannot find an "
            + "appropriate category you can select 'Add New Category' from "
            + "the drop down menu and you will be asked for the new category "
            + "information on a subsequent page.<br/><br/>";

        var titleHelp=
            "Title should contain the title given to your document.";
            ;

        var abstractHelp=
            "Abstract is an area for you to provide an abstract of the "
            + "document you are submitting.<br/><br/>"
            + "To do this enter a plain text abstract for your paper in the "
            + "field provided. HTML tags can be used.";


        var keywordsHelp=
            "Keywords is a field where you can enter keywords that will be "
            + "used to possibly locate your paper by others searching the "
            + "database. You may want to enter multiple terms that are "
            + "associated with your document. Examples may include words "
            + "like: medical imaging; robotics; data mining.<br/><br/>"
            + "Please enter keywords used to describe your paper, each "
            + "keyword should be seperated by a semicolon.";

        var userInfoHelp
            = "A place for the user to enter his/her own information";
        </script>
JS_END;
    }
}

$page = new add_pub1();
echo $page->toHtml();

?>
