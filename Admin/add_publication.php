<?php ;

// $Id: add_publication.php,v 1.45 2006/07/12 21:47:43 aicmltec Exp $

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
require_once 'includes/authorselect.php';
require_once 'includes/pdExtraInfoList.php';

class add_publication extends pdHtmlPage {
    function add_publication($pub = null) {
        global $logged_in;

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

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }
    }

    function setDefaults() {
        global $db;

        $defaults = array();

        if ($pub_id == null) return;

        $pub = new pdPublication();
        $pub->dbLoad($db, $pub_id);

        $defaults += array('cat_id'     => $pub->category->cat_id,
                           'title'      => $pub->title,
                           'abstract'   => $pub->abstract,
                           'extra_info' => $pub->extra_info,
                           'keywords'   => $pub->keywords,
                           'date_published' => $pub->published
            );

        if ($pub->venue != null)
            $defaults['venue_id'] = $pub->venue_id;
        else
            $defaults['venue_id'] = -3;

        if (count($pub->authors) > 0) {
            foreach ($pub->authors as $author)
                $defaults['authors'][] = $author->author_id;
        }

        if (count($pub->extPointer) > 0) {
            $c = 1;
            foreach ($pub->extPointer as $name => $value) {
                $defaults['extname' . $c] = $name;
                $defaults['extvalue' . $c] = $value;
                $c++;
            }
        }

        if (count($pub->intPointer) > 0) {
            $c = 1;
            foreach ($pub->intPointer as $i) {
                $defaults['intpointer' . $c] = $i->value;
                $c++;
            }
        }

        $this->form_controller->setDefaults($defaults);
    }

    function javascript() {
        $ext_next = $this->ext + 1;
        $ext_prev = $this->ext - 1;
        $intpoint_next = $this->intpoint + 1;
        $intpoint_prev = $this->intpoint - 1;
        $nummaterials_next = $this->nummaterials + 1;
        $nummaterials_prev = $this->nummaterials - 1;

        $this->js = <<<JS_END
            <script language="JavaScript" src="../calendar.js"></script>
            <script language="JavaScript" type="text/JavaScript">

            var venueHelp=
            "Where the paper was published -- specific journal, conference, "
            + "workshop, etc. If many of the database papers are in the same "
            + "venue, you can create a single &quot;label&quot; for that "
            + "venue, to specify name of the venue, location, date, editors "
            + "and other common information. You will then be able to use "
            "and re-use that information.";

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
            "Title should contain the title given to your document.<br/><br/>"
            +  "Please enter the title of your document in the field provided.";

        var authorsHelp=
            "This field is to store the author(s) of your document in the database."
            + "<br/><br/>"
            + "To use this field select the author(s) of your document from the "
            + "listbox. You can select multiple authors by holding down the control "
            + "key and clicking. If you do not see the name of the author(s) of the "
            + "document listed in the listbox then you must add them with the Add "
            + "Author button.";

        var abstractHelp=
            "Abstract is an area for you to provide an abstract of the document you "
            + "are submitting.<br/><br/>"
            + "To do this enter a plain text abstract for your paper in the field "
            + "provided. HTML tags can be used.";

        var extraInfoHelp=
            "Specify auxiliary information, to help classify this publication. "
            + "Eg, &quot;with student&quot; or &quot;best paper&quot;, etc. Note "
            + "that, by default, this information will NOT be shown when this "
            + "document is presented. Separate using semicolons(;).";

        var extraInfoListHelp=
            "Select extra information from entries already in the database.";

        var extLinks=
            "Used to link this publication to an outside source such as a website or a "
            + "publication that is not in the current database.";

        var webLinkHelp =
            "The first field contains the link title and the second link "
            + "contains the URL.";


        var keywordsHelp=
            "Keywords is a field where you can enter keywords that will be used to "
            + "possibly locate your paper by others searching the database. You may "
            + "want to enter multiple terms that are associated with your document. "
            + "Examples may include words like: medical imaging; robotics; data "
            + "mining.<br/><br/>"
            + "Please enter keywords used to describe your paper, each keyword should "
            + "be seperated by a semicolon.";

        var datePublishedHelp=
            "Specifies the date that this document was published. If you have "
            + "specified a publication_venue that include a date, then this date "
            + "field will already be enterred.";

        var paperAtt =
            "Attach a postscript, PDF, or other version of the publication.";

        var otherAtt =
            "In addition to the primary paper attachment, attach additional "
            + "files to this publication.";

        var intLinks =
            "Used to link other publications in the database to this publication.";

        </script>
JS_END;
    }
}

class pubStep1Page extends HTML_QuickForm_Page {
    function buildForm() {
        $data =& $this->controller->container();
        $db =& $data['db'];
        assert('$db != null');
        $masterPage =& $data['masterPage'];
        assert('$masterPage != null');
        $pub =& $data['pub'];
        assert('$pub != null');

        $this->_formBuilt = true;

        $this->addElement('header', null, 'Add Publication: Step 1');

        $element = $this->addElement('hidden', 'pub_id', 0);

        // Venue
        $venue_list = new pdVenueList($db);
        $options = array(''   => '--- Select a Venue ---',
                         -2 => 'No Venue',
                         -3 => 'Unique Venue');
        $options += $venue_list->list;
        $this->addElement('select', 'venue_id',
                          $masterPage->helpTooltip('Publication Venue',
                                                   'venueHelp') . ':',
                          $options);

        // title
        $this->addElement('text', 'title',
                          $masterPage->helpTooltip('Title', 'titleHelp') . ':',
                          array('size' => 60, 'maxlength' => 250));
        $this->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        // Authors
        $user = $_SESSION['user'];
        $auth_list = new pdAuthorList($db);
        $all_authors = $auth_list->list;

        foreach (array_keys($user->collaborators) as $author_id) {
            unset($all_authors[$author_id]);
        }

        // get the first 10 popular authors used by this user
        $user->popularAuthorsDbLoad($db);

        $most_used_authors = array();
        if (count($user->author_rank) > 0) {
            $most_used_author_ids
                = array_slice(array_keys($user->author_rank), 0, 10);

            foreach($most_used_author_ids as $author_id) {
                $most_used_authors[$author_id] = $all_authors[$author_id];
                unset($all_authors[$author_id]);
            }
            asort($most_used_authors);
        }

        $this->addElement('authorselect', 'authors',
                          $masterPage->helpTooltip('Author(s)',
                                                   'authorsHelp') . ':',
                          array('form_name' => $this->_attributes['name'],
                                'author_list' => $all_authors,
                                'favorite_authors' => $user->collaborators,
                                'most_used_authors' => $most_used_authors),
                          array('class' => 'pool',
                                'style' => 'width:150px;'));

        $this->addElement('textarea', 'abstract',
                          $masterPage->helpTooltip('Abstract',
                                                         'abstractHelp')
                          . ':<br/><div id="small">HTML Enabled</div>',
                          array('cols' => 60, 'rows' => 10));

        $kwGroup[] =& HTML_QuickForm::createElement(
            'text', 'keywords', null, array('size' => 55, 'maxlength' => 250));
        $kwGroup[] =& HTML_QuickForm::createElement(
            'static', 'kwgroup_help', null,
            '<span style="font-size:10px;">seperate using semi-colon (;)</span>');
        $this->addGroup($kwGroup, 'kwgroup',
                        $masterPage->helpTooltip('Keywords',
                                                       'keywordsHelp') . ':',
                        '<br/>', false);

        $dateGroup[] =& HTML_QuickForm::createElement(
            'text', 'date_published', null,
            array('size' => 10, 'maxlength' => 10));

        $dateGroup[] =& HTML_QuickForm::createElement(
            'static', 'dategroup_icon', null,
            '<a href="javascript:doNothing()" onClick="setDateField('
            . 'document.page1.date_published);'
            . 'top.newWin=window.open(\'../calendar.html\','
            . '\'cal\',\'dependent=yes,width=230,height=250,'
            . 'screenX=200,screenY=300,titlebar=yes\')">'
            . '<img src="../calendar.gif" border=0></a> '
            . '<span style="font-size:10px;">(yyyy-mm-dd)</span>');
        $this->addGroup($dateGroup, 'dategroup',
                        $masterPage->helpTooltip('Date Published',
                                                       'datePublishedHelp')
                        . ':',
                        '&nbsp;', false);

        $this->addRule('dategroup', 'please enter a publication date',
                       'required', null, 'client');

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $buttons[] =& HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "javascript:location.href='" . $url . "';"));
        $buttons[] =& HTML_QuickForm::createElement(
            'submit', $this->getButtonName('reset'), 'Reset');
        $buttons[] =& HTML_QuickForm::createElement(
            'submit', $this->getButtonName('next'), 'Next step >>');
        $this->addGroup($buttons, 'buttons', '', '&nbsp', false);

        if ($pub != null) {
            $defaults = array('pub_id'     => $pub->pub_id,
                              'cat_id'     => $pub->category->cat_id,
                              'title'      => $pub->title,
                              'abstract'   => $pub->abstract,
                              'keywords'   => $pub->keywords,
                              'date_published' => $pub->published
                );

            if ($pub->venue_id != null)
                $defaults['venue_id'] = $pub->venue_id;
            else
                $defaults['venue_id'] = -3;

            if (count($pub->authors) > 0) {
                foreach ($pub->authors as $author)
                    $defaults['authors'][] = $author->author_id;
            }

            $this->setConstants($defaults);
        }
    }
}

class pubStep2Page extends HTML_QuickForm_Page {
    function buildForm() {
        $data =& $this->controller->container();
        $db =& $data['db'];
        assert('$db != null');
        $masterPage =& $data['masterPage'];
        assert('$masterPage != null');
        $pub =& $data['pub'];
        assert('$pub != null');

        $this->addElement('header', null, 'Add Publication: Step 2');

        $venue_id = $this->controller->exportValue('page1', 'venue_id');

        if ($venue_id == -3) {
            $this->addElement('textarea', 'venue_name', 'Unique Venue Name:',
                              array('cols' => 60, 'rows' => 5));
        }

        // category
        $category_list = new pdCatList($db);
        $options = array('' => '--- Please Select a Category ---')
            + $category_list->list;
        $catElement = $this->addElement(
            'select', 'cat_id',
            $masterPage->helpTooltip('Category', 'categoryHelp') . ':',
            $options);

        $this->addElement('advcheckbox', 'add_paper',
                          $masterPage->helpTooltip('Attach Paper',
                                                         'paperAtt') . ':',
                          'check this box to attach the primary document',
                          null, array('no', 'yes'));

        $numOptions = array('' => 'none',
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                            '7' => '7',
                            '8' => '8',
                            '9' => '9',
                            '10' => '10');

        $attachments[] =& HTML_QuickForm::createElement(
            'select', 'other_attachments', null, $numOptions);
        $attachments[] =& HTML_QuickForm::createElement(
            'static', 'attachmentsStatic', null, 'additional attachments');
        $this->addGroup($attachments, 'attachmentsGroup',
                        $masterPage->helpTooltip('Other Attachments',
                                                       'otherAtt') . ':',
                        '&nbsp;', false);

        $this->addElement('header', 'other_info',
                          'Other information', null);

        $this->addElement('textarea', 'extra_info',
                          $masterPage->helpTooltip('Extra Information',
                                                         'extraInfoHelp')
                          . ':',
                          array('cols' => 60, 'rows' => 5));

        $this->addElement('advcheckbox', 'extra_info_list',
                          $masterPage->helpTooltip(
                              'Extra Info From List', 'extraInfoListHelp')
                          . ':',
                          'check this box to select extra info from a list',
                          null, array('no', 'yes'));

        $this->addElement('select', 'web_links',
                          $masterPage->helpTooltip('Web Links',
                                                         'extLinks') . ':',
                          $numOptions);
        $this->addElement('select', 'int_links',
                          $masterPage->helpTooltip('Publication Links',
                                                         'intLinks') . ':',
                          $numOptions);

        $buttons[0] =& $this->createElement(
            'submit', $this->getButtonName('back'), '<< Previous step');
        $buttons[1] =& HTML_QuickForm::createElement(
            'submit', $this->getButtonName('next'), 'Next step >>');
        $this->addGroup($buttons, 'buttons', '', '&nbsp', false);

        if ($pub != null) {
            $defaults = array();
            if (is_string($pub->venue))
                $defaults['venue_name'] = $pub->venue;
            $defaults['cat_id'] = $pub->category->cat_id;
        }
        else {
            if ($venue_id > 0) {
                $venue = new pdVenue();
                $result = $this->venue->dbLoad($db, $venue_id);
                assert('$result');

                $category = null;
                if ($this->venue->type == 'Conference') {
                    $result = $category->dbLoad($db, null, 'In Conference');
                    assert('$result');
                }
                else if ($this->venue->type == 'Workshop') {
                    $result = $category->dbLoad($db, null, 'In Workshop');
                    assert('$result');
                }
                else if ($this->venue->type == 'Journal') {
                    $result = $category->dbLoad($db, null, 'In Journal');
                    assert('$result');
                }

                if ($category != null)
                    $defaults['cat_id'] = $category->cat_id;
            }
        }

        $this->setConstants($defaults);
    }
}


class pubStep3Page extends HTML_QuickForm_Page {
    function buildForm() {
        $data =& $this->controller->container();
        $db =& $data['db'];
        assert('$db != null');
        $masterPage =& $data['masterPage'];
        assert('$masterPage != null');

        $this->addElement('header', null, 'Add Publication: Step 3');

        $cat_id = $this->controller->exportValue('page1', 'cat_id');
        $add_paper = $this->controller->exportValue('page2', 'add_paper');
        $other_attachments
            = $this->controller->exportValue('page2', 'other_attachments');
        $extra_info_list
            = $this->controller->exportValue('page2', 'extra_info_list');
        $web_links = $this->controller->exportValue('page2', 'web_links');
        $int_links = $this->controller->exportValue('page2', 'int_links');

        if ($cat_id > 0) {
            $category = new pdCategory();
            $result = $category->dbLoad($db, $cat_id);
            assert('$result');

            $this->addElement('static', 'categoryinfo',
                              'Additional category information', null);

            if ($category->info != null) {
                foreach (array_values($category->info) as $name) {
                    $this->addElement('text', $name, ucfirst($name) . ':',
                                      array('size' => 50, 'maxlength' => 250));
                }
            }
        }

        if ($add_paper == 'yes') {
            $this->addElement('header', null, 'Attachments');
            $this->addElement('file', 'uploadpaper', 'Paper:',
                              array('size' => 45));
        }

        for ($i = 0; $i < $other_attachments; $i++) {
            $this->addElement('file', 'other_attachments' . $i,
                              'Attachment ' . ($i + 1). ':',
                              array('size' => 45, 'maxlength' => 250));
        }

        if ($extra_info_list == "yes") {
            $this->addElement('header', null, 'Select Extra Information');
            $extra_info = new pdExtraInfoList($db);

            $c = 0;
            foreach ($extra_info->list as $info) {
                $this->addElement('advcheckbox',
                                  'extra_info_from_list[' . $c . ']',
                                  null, $info, null, array('', $info));
                $c++;
            }
        }

        if ($web_links > 0) {
            $this->addElement('header', null, 'Web Links');

            for ($i = 0; $i < $web_links; $i++) {
                unset($web_link_group);
                $web_link_group [] =& HTML_QuickForm::createElement(
                    'text', 'web_link_text' . $i, 'Link Text',
                    array('size' => 12, 'maxlength' => 250));
                $web_link_group [] =& HTML_QuickForm::createElement(
                    'static', 'web_links_help', null, ':');
                $web_link_group [] =& HTML_QuickForm::createElement(
                    'text', 'web_link_url' . $i, 'Link URL',
                    array('size' => 25, 'maxlength' => 250));

                $this->addGroup($web_link_group, 'web_link_group' . $i,
                                $masterPage->helpTooltip(
                                    'Web Link ' . ($i + 1),
                                    'webLinkHelp') . ':',
                                '&nbsp;', false);
            }
        }

        if ($int_links > 0) {
            $this->addElement('header', null, 'Publication Links');
            $pub_list = new pdPubList($db);
            $options[''] = '--- select publication --';
            foreach ($pub_list->list as $p) {
                if (strlen($p->title) > 70)
                    $options[$p->pub_id] = substr($p->title, 0, 67) . '...';
                else
                    $options[$p->pub_id] = $p->title;
            }

            for ($i = 0; $i < $int_links; $i++) {
                $this->addElement('select', 'int_link' . $i,
                                  'Publication Link ' . ($i + 1) . ':',
                                  $options);
            }
        }

        $buttons[0] =& $this->createElement(
            'submit', $this->getButtonName('back'), '<< Previous step');
        $buttons[1] =& HTML_QuickForm::createElement(
            'submit', $this->getButtonName('next'), 'Finish');
        $this->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->setDefaultAction('upload');
    }
}

class ActionDisplay extends HTML_QuickForm_Action_Display {
    function _renderForm(&$page) {
        $data =& $page->controller->container();
        $masterPage = $data['masterPage'];
        assert('$masterPage != null');

        $renderer =& $page->defaultRenderer();

        $page->setRequiredNote(
            '<font color="#FF0000">*</font> shows the required fields.');

        $renderer->setFormTemplate(
            '<table bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');
        $renderer->setGroupTemplate(
            '<table><tr>{content}</tr></table>', 'name');

        $renderer->setElementTemplate(
            '<tr><td colspan="2">{label}</td></tr>',
            'categoryinfo');

        $page->accept($renderer);

        $masterPage->renderer =& $renderer;
        $masterPage->javascript();

        $data =& $page->controller->container();
        $masterPage->contentPost .= '<pre>' . print_r($data, true) . '</pre>';

        echo $masterPage->toHtml();
    }
}

class ActionProcess extends HTML_QuickForm_Action {
    function perform(&$page, $actionName) {
        $data =& $page->controller->container();
        $db =& $data['db'];
        assert('$db != null');
        $masterPage =& $data['masterPage'];
        assert('$masterPage != null');

        $values = $page->controller->exportValues();
        if (count($values['authors']) > 0)
            foreach ($values['authors'] as $index => $author) {
                $pos = strpos($author, ':');
                if ($pos !== false) {
                    $values['authors'][$index] = substr($author, $pos + 1);
                }
            }

        $masterPage->contentPre
            .= '<pre>' . print_r($values, true) . '</pre>';

        if ($pub != null) {
            $pub->load($values);
            $pub->addVenue($db, $values['venue_id']);
            $pub->addCategory($db, $values['cat_id']);
        }
        else {
            $pub = new pdPublication();
            $pub->load($values);
            if ($values['venue_id'] > 0)
                $pub->addVenue($db, $values['venue_id']);
            if ($values['cat_id'] > 0)
                $pub->addCategory($db, $values['cat_id']);

            $pub->submit = $_SESSION['user']->name;
        }

        if (count($values['authors']) > 0)
            foreach ($values['authors'] as $author_id)
                $pub->addAuthor($db, $author_id);

        if (count($pub->info) > 0) {
            foreach (array_keys($pub->info) as $name) {
                $pub->info[$name] = $values[$name];
            }
        }

        $pub->published = $values['date_published'];

        for ($e = 0; $e < $values['web_links']; $e++) {
            $pub->addExtPointer($db, $values['web_link_text'.$e],
                                $values['web_link_url'.$e]);
        }

        for ($e = 0; $e < $values['int_links']; $e++) {
            $pub->addIntPointer($db, $values['int_link'.$e]);
        }

        $extra_info_arr = array($values['extra_info']);
        if (isset($values['extra_info_from_list']))
            foreach ($values['extra_info_from_list'] as $info) {
                if ($info != '')
                    $extra_info_arr[] = $info;
            }

        $pub->extra_info = implode('; ', $extra_info_arr);

        $pub->dbSave($db);

        // copy files here - get the element containing the upload
        $path = FS_PATH . '/uploaded_files/' . $pub->pub_id;
        $element =& $page->getElement('uploadpaper');

        if ($element->isUploadedFile()) {
            $basename = 'paper_' . $_FILES['uploadpaper']['name'];
            $filename = $path . '/' . $basename;
            $relativename = $pub->pub_id . '/'. $basename;

            if (!file_exists($path)) {
                mkdir($path, 0777);
                // mkdir permissions with 0777 does not seem to work
                chmod($path, 0777);
            }

            $element->moveUploadedFile($path, $basename);
            chmod($filename, 0777);
            $pub->dbUpdatePaper($db, $relativename);
        }

        if ($values['other_attachments'] > 0) {
            for ($i = 0; $i < $values['other_attachments']; $i++) {
                $element =& $page->getElement('other_attachments' . $i);

                if ($element->isUploadedFile()) {
                    $basename = 'additional_'
                        . $_FILES['other_attachments' . $i]['name'];
                    $filename = $path . '/' . $basename;
                    $relativename = $pub->pub_id . '/'. $basename;

                    if (!file_exists($path)) {
                        mkdir($path, 0777);
                        // mkdir permissions with 0777 does not seem to work
                        chmod($path, 0777);
                    }

                    $element->moveUploadedFile($path, $basename);
                    chmod($filename, 0777);
                    $pub->dbUpdateAdditional($db, $relativename);
                }
            }
        }

        $masterPage->contentPre
            .= 'The following publication was submitted successfully:<p/>'
            . '<a href="../view_publication?pub_id=' . $pub->pub_id
            . '">' . $pub->title . '</a>';

        echo $masterPage->toHtml();
    }
}

class ActionReset extends HTML_QuickForm_Action {
    function perform(&$page, $actionName) {
        if ($actionName == 'reset') {
            $pageName = $page->getAttribute('id');
            $page->loadValues(null);
            $page->controller->applyDefaults($pageName);
            return $page->handle('display');
        }
    }
}

session_start();

$logged_in = check_login();

$db =& dbCreate();

$wizard = new HTML_QuickForm_Controller('pubWizard', true);
$wizard->addPage(new pubStep1Page('page1'));
$wizard->addPage(new pubStep2Page('page2'));
$wizard->addPage(new pubStep3Page('page3'));

$wizard->addAction('display', new ActionDisplay());
$wizard->addAction('process', new ActionProcess());
$wizard->addAction('reset', new ActionReset());

$data =& $wizard->container();

$data['db'] =& $db;

$pub = null;
if (isset($_GET['pub_id']) && ($_GET['pub_id'] != '')) {
    $pub = new pdPublication();
    $result = $pub->dbLoad($db, $_GET['pub_id']);
    assert('$result');
    $data['pub'] =& $pub;
}

$masterPage = new add_publication($pub);
$data['masterPage'] =& $masterPage;

$wizard->run();

$db->close();

?>
