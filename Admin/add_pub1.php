<?php ;

// $Id: add_pub1.php,v 1.20 2007/03/10 01:23:05 aicmltec Exp $

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
        global $access_level;

        $options = array('pub_id');
        foreach ($options as $opt) {
            if (isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $$opt = stripslashes($_GET[$opt]);
            else
                $$opt = null;
        }

        $db = dbCreate();

        if (isset($_SESSION['pub'])) {
            // according to session variables, we are already editing a
            // publication
            $pub =& $_SESSION['pub'];
        }
        else if ($pub_id != '') {
            // pub_id passed in with $_GET variable
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id);
            if (!$result) {
                $this->pageError = true;
                $db->close();
                return;
            }

            $_SESSION['pub'] =& $pub;
        }
        else {
            // create a new publication
            $pub = new pdPublication();
            $_SESSION['pub'] =& $pub;
        }

        if ($pub->pub_id != '')
            parent::pdHtmlPage('edit_publication');
        else
            parent::pdHtmlPage('add_publication');

        if ($access_level <= 0) {
            $this->loginError = true;
            $db->close();
            return;
        }

        $this->addPubDisableMenuItems();

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
        $venues = array(new pdVenueList($db),
                        new pdVenueList($db, 'Journal'),
                        new pdVenueList($db, 'Conference'),
                        new pdVenueList($db, 'Workshop'));

        $venue_sel2[0] = array('' => '--Select Venue--') + $venues[0]->list;
        $venue_sel2[1] = array('' => '--Select Venue--') + $venues[1]->list;
        $venue_sel2[2] = array('' => '--Select Venue--') + $venues[2]->list;
        $venue_sel2[3] = array('' => '--Select Venue--') + $venues[3]->list;

        // check if user info has 'Used by me' to venues
        $user =& $_SESSION['user'];
        $user->venueIdsGet($db);
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

        if ($pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->db =& $db;
        $this->form =& $form;

        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm();
        }

        if ($this->debug) {
            $this->contentPost
                .= 'values<pre>' . print_r($pub, true) . '</pre>';
        }

        $db->close();
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        switch ($pub->venue->type) {
            case 'Journal':    $type = 1; break;
            case 'Conference': $type = 2; break;
            case 'Workshop':   $type = 3; break;
            default: $type = 0;
        }

        $defaults = array('title'    => $pub->title,
                          'abstract' => $pub->abstract,
                          'keywords' => $pub->keywords,
                          'user'     => $pub->user,
                          'venue_id' => array($type, $pub->venue_id));

        if (!isset($pub->published) || ($pub->published == '')) {
            $defaults['pub_date'] = array('Y' => date('Y'), 'M' => date('m'));
        }
        else {
            $date = explode('-', $pub->published);

            $defaults['pub_date']['Y'] = $date[0];
            $defaults['pub_date']['M'] = $date[1];
        }

        if ($this->debug) {
            $this->contentPost
                .= 'defaults<pre>' . print_r($defaults, true) . '</pre>';
        }

        $this->form->setDefaults($defaults);

        if (isset($_SESSION['pub']) && ($_SESSION['pub']->title != '')) {
            $pub =& $_SESSION['pub'];

            $this->contentPre .= '<h3>Adding Following Publication</h3>'
                . $pub->getCitationHtml('..', false) . '<p/>'
                . add_pub_base::similarPubsHtml($db);
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
        $db =& $this->db;
        $form =& $this->form;
        $pub =& $_SESSION['pub'];

        $values = $form->exportValues();

        if ($this->debug) {
            echo 'values<pre>' . print_r($values, true) . '</pre>';
        }

        $pub->load($values);
        $pub->published = $values['pub_date']['Y'] . '-'
            .  $values['pub_date']['M'] . '-1';
        $_SESSION['state'] = 'pub_add';

        if (isset($values['venue_id'][1]) && ($values['venue_id'][1] > 0))
            $pub->addVenue($db, $values['venue_id'][1]);

        $result = $pub->duplicateTitleCheck($db);
        if (count($result) > 0)
            $_SESSION['similar_pubs'] = $result;

        if ($this->debug)
            $this->contentPre .= '<pre>' . print_r($_SESSION, true) . '</pre>';
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

session_start();
$access_level = check_login();
$page = new add_pub1();
echo $page->toHtml();

?>
