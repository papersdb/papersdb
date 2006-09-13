<?php ;

// $Id: add_pub1.php,v 1.8 2006/09/13 22:12:34 aicmltec Exp $

/**
 * \file
 *
 * \brief This page is the form for adding/editing a publication.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdExtraInfoList.php';
require_once 'includes/authorselect.php';
require_once 'includes/jscalendar.php';
require_once 'includes/pdAttachmentTypesList.php';

class add_pub1 extends pdHtmlPage {
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

        $db =& dbCreate();

        if (isset($_SESSION['pub'])) {
            $pub =& $_SESSION['pub'];
        }
        else if ($pub_id != '') {
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id);
            assert('$result');
            $_SESSION['pub'] =& $pub;
        }
        else {
            $pub = new pdPublication();
            $_SESSION['pub'] =& $pub;
        }

        if ($pub != null)
            parent::pdHtmlPage('edit_publication');
        else
            parent::pdHtmlPage('add_publication');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);

        $form = new HTML_QuickForm('add_pub2');
        $form->addElement('header', null, 'Add Publication');

        // title
        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'titleHelp') . ':',
                          array('size' => 60, 'maxlength' => 250));
        $form->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        // Venue
        $venue_sel1 = array('All Types', 'Journals', 'Conferences',
                            'Workshops');
        $venues = array(new pdVenueList($db),
                        new pdVenueList($db, 'Journal'),
                        new pdVenueList($db, 'Conference'),
                        new pdVenueList($db, 'Workshop'));

        $venue_sel2[0] = array('' => '--Select Venue--') + $venues[0]->list;
        $venue_sel2[1] = array('' => '--Select Venue--') + $venues[1]->list;
        $venue_sel2[2] = array('' => '--Select Venue--') + $venues[2]->list;
        $venue_sel2[3] = array('' => '--Select Venue--') + $venues[3]->list;

        $sel =& $form->addElement(
            'hierselect', 'venue_id',
            $this->helpTooltip('Venue', 'venueHelp') . ':',
            array('style' => 'width: 450px'), '<br/>');
        $sel->setOptions(array($venue_sel1, $venue_sel2));

        $form->addElement('textarea', 'abstract',
                          $this->helpTooltip('Abstract',
                                                         'abstractHelp')
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

            'kwgroup', $this->helpTooltip('Keywords',
                                                'keywordsHelp') . ':',
            '<br/>', false);

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';


        $form->addElement('date', 'pub_date', 'Date:',
                          array('format' => 'YM', 'minYear' => '1990'));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'button', 'cancel', 'Cancel',
                    array('onclick' => "location.href='" . $url . "';")),
                HTML_QuickForm::createElement(
                    'reset', 'reset', 'Reset'),
                HTML_QuickForm::createElement(
                    'submit', 'next', 'Next step >>')),
            'buttons', '', '&nbsp', false);

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

        $defaults = array('title'    => $pub->title,
                          'abstract' => $pub->abstract,
                          'keywords' => $pub->keywords,
                          'venue_id' => $pub->venue_id);

        $defaults['venue_id'][0] = 0;
        $defaults['venue_id'][1] = $pub->venue_id;

        $date = explode('-', $pub->published);

        $defaults['pub_date']['Y'] = $date[0];
        $defaults['pub_date']['M'] = $date[1];

        $this->form->setDefaults($defaults);

        if (isset($_SESSION['pub']) && ($_SESSION['pub']->title != '')) {
            $pub =& $_SESSION['pub'];

            $this->contentPre .= '<h3>Publication Information</h3>'
                . $pub->getCitationHtml('..', false) . '<p/>';
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

        if (isset($values['venue_id']) && ($values['venue_id'] > 0))
            $pub->addVenue($db, $values['venue_id'][1]);

        if ($this->debug)
            $this->contentPre .= '<pre>' . print_r($_SESSION, true) . '</pre>';
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
        </script>
JS_END;
    }
}

session_start();
$access_level = check_login();
$page = new add_pub1();
echo $page->toHtml();

?>
