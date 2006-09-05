<?php ;

// $Id: add_pub1.php,v 1.1 2006/09/05 22:59:51 aicmltec Exp $

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

        if ($pub != null)
            parent::pdHtmlPage('edit_publication');
        else
            parent::pdHtmlPage('add_publication');

        $this->navMenuItemEnable('add_publication', 0);
        $this->navMenuItemDisplay('add_author', 0);
        $this->navMenuItemDisplay('add_category', 0);
        $this->navMenuItemDisplay('add_venue', 0);

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        if (isset($_GET['pub_id']) && ($_GET['pub_id'] != '')) {
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $_GET['pub_id']);
            assert('$result');
        }

        $form = new HTML_QuickForm('add_pub2');
        $form->addElement('header', null, 'Add Publication');

        // title
        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'titleHelp') . ':',
                          array('size' => 60, 'maxlength' => 250));
        $form->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        // Venue
        $venue_list = new pdVenueList($db);
        $options = array(''   => '--- Select a Venue ---',
                         -2 => 'No Venue');
        foreach ($venue_list->list as $id => $title) {
            if ($title != '')
                $options[$id] = $title;
        }
        $form->addElement('select', 'venue_id',
                          $this->helpTooltip('Venue', 'venueHelp') . ':',
                          $options,
                          array('onchange' => 'datakeep();'));

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

        if ($pub != null) {
            $defaults = array('title'      => $pub->title,
                              'abstract'   => $pub->abstract,
                              'keywords'   => $pub->keywords);

            if ($pub->venue_id != null)
                $defaults['venue_id'] = $pub->venue_id;

            if (count($pub->authors) > 0) {
                foreach ($pub->authors as $author)
                    $defaults['authors'][] = $author->author_id;
            }

            $this->setConstants(array('keywords' => $pub->keywords));

            $this->setConstants($defaults);
        }

        $this->db =& $db;
        $this->form =& $form;

        if (($_SESSION['state'] == 'pub_add')
            && isset($_SESSION["selected_authors"])) {
            $auth_list = explode(',', $_SESSION['selected_authors']);

            if (count($auth_list) > 0) {
                $this->contentPre .= 'Authors selected so far:<br/><ul>';

                foreach ($auth_list as $auth_id) {
                    $sel_auth = new pdAuthor();
                    $sel_auth->dbLoad($db, $auth_id);
                    $this->contentPre .= '<li>' . $sel_auth->name . '</li>';
                }
                $this->contentPre .= '</ul>';
            }
        }

        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm();
        }

        //$this->contentPre .= '<pre>' . print_r($this, true) . '</pre>';
        $db->close();
    }

    function renderForm() {
        $defaults = array();

        if (isset($_SESSION['new_pub'])) {
            $pub =& $_SESSION['new_pub'];
            $defaults['title'] = $pub->title;
            $defaults['venue_id'] = $pub->venue_id;
            $defaults['abstract'] = $pub->abstract;
            $defaults['keywords'] = $pub->keywords;
        }

        $this->form->setDefaults($defaults);

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
        $db =& $this->db;

        $values = $this->form->exportValues();

        if ($pub != null) {
            if ($this->debug) {
                echo 'values<pre>' . print_r($values, true) . '</pre>';
            }

            $pub->load($values);
            if (isset($values['venue_id']) && ($values['venue_id'] != '')
                && ($values['venue_id'] != '0'))
                $pub->addVenue($db, $values['venue_id']);
            $pub->addCategory($db, $values['cat_id']);
        }
        else {
            $pub = new pdPublication();
            $pub->load($values);
            if ($values['venue_id'] > 0)
                $pub->addVenue($db, $values['venue_id']);
        }

        $_SESSION['state'] = 'pub_add';
        $_SESSION['new_pub'] =& $pub;

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
