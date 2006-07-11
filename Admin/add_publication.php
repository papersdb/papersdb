<?php ;

// $Id: add_publication.php,v 1.44 2006/07/11 22:01:03 aicmltec Exp $

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

$db = null;

class pubStep1Page extends HTML_QuickForm_Page {
    var $masterPage;

    function pubStep1Page($name, &$masterPage) {
        parent::HTML_QuickForm_Page($name);
        $this->masterPage =& $masterPage;
    }

    function buildForm() {
        global $db;

        $this->_formBuilt = true;

        $this->addElement('header', null, 'Add Publication: Step 1');

        // Venue
        $venue_list = new pdVenueList($db);
        $options = array(''   => '--- Select a Venue ---',
                         -2 => 'No Venue',
                         -3 => 'Unique Venue');
        $options += $venue_list->list;
        $this->addElement('select', 'venue_id',
                          $this->masterPage->helpTooltip('Publication Venue',
                                                         'venueHelp') . ':',
                          $options);

        // category
        $category_list = new pdCatList($db);
        $options = array('' => '--- Please Select a Category ---')
            + $category_list->list;
        $catElement = $this->addElement(
            'select', 'cat_id',
            $this->masterPage->helpTooltip('Category', 'categoryHelp') . ':',
            $options);

        // title
        $this->addElement('text', 'title',
                          $this->masterPage->helpTooltip('Title',
                                                         'titleHelp') . ':',
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
                          $this->masterPage->helpTooltip('Author(s)',
                                                         'authorsHelp') . ':',

                          array('form_name' => $this->_attributes['name'],
                                'author_list' => $all_authors,
                                'favorite_authors' => $user->collaborators,
                                'most_used_authors' => $most_used_authors),
                          array('class' => 'pool',
                                'style' => 'width:150px;'));

        $this->addElement('textarea', 'abstract',
                          $this->masterPage->helpTooltip('Abstract',
                                                         'abstractHelp')
                          . ':<br/><div id="small">HTML Enabled</div>',
                          array('cols' => 60, 'rows' => 10));

        $kwGroup[] =& HTML_QuickForm::createElement(
            'text', 'keywords', null, array('size' => 55, 'maxlength' => 250));
        $kwGroup[] =& HTML_QuickForm::createElement(
            'static', 'kwgroup_help', null,
            '<span style="font-size:10px;">seperate using semi-colon (;)</span>');
        $this->addGroup($kwGroup, 'kwgroup',
                        $this->masterPage->helpTooltip('Keywords',
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
                        $this->masterPage->helpTooltip('Date Published',
                                                       'datePublishedHelp')
                        . ':',
                        '&nbsp;', false);

        $this->addRule('dategroup', 'please enter a publication date',
                       'required', null, 'client');

        $buttons[0] =& HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "javascript:location.href='"
                  . $_SERVER['PHP_SELF'] . "';"));
        $buttons[1] =& HTML_QuickForm::createElement(
            'submit', $this->getButtonName('next'), 'Next step >>');
        $this->addGroup($buttons, 'buttons', '', '&nbsp', false);
    }
}

class pubStep2Page extends HTML_QuickForm_Page {
    var $masterPage;

    function pubStep2Page($name, &$masterPage) {
        parent::HTML_QuickForm_Page($name);
        $this->masterPage =& $masterPage;
    }

    function buildForm() {
        global $db;

        $this->addElement('header', null, 'Add Publication: Step 2');

        $venue_id = $this->controller->exportValue('page1', 'venue_id');

        if ($venue_id == -3) {
            $this->addElement('textarea', 'venue_name', 'Unique Venue Name:',
                              array('cols' => 60, 'rows' => 5));
        }

        $this->addElement('advcheckbox', 'add_paper',
                          $this->masterPage->helpTooltip('Attach Paper',
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
                        $this->masterPage->helpTooltip('Other Attachments',
                                                       'otherAtt') . ':',
                        '&nbsp;', false);

        $this->addElement('header', 'other_info',
                          'Other information', null);

        $this->addElement('textarea', 'extra_info',
                          $this->masterPage->helpTooltip('Extra Information',
                                                         'extraInfoHelp')
                          . ':',
                          array('cols' => 60, 'rows' => 5));

        $this->addElement('advcheckbox', 'extra_info_list',
                          $this->masterPage->helpTooltip(
                              'Extra Info From List', 'extraInfoListHelp')
                          . ':',
                          'check this box to select extra info from a list',
                          null, array('no', 'yes'));

        $this->addElement('select', 'web_links',
                          $this->masterPage->helpTooltip('Web Links',
                                                         'extLinks') . ':',
                          $numOptions);
        $this->addElement('select', 'int_links',
                          $this->masterPage->helpTooltip('Publication Links',
                                                         'intLinks') . ':',
                          $numOptions);

        $buttons[0] =& $this->createElement(
            'submit', $this->getButtonName('back'), '<< Previous step');
        $buttons[1] =& HTML_QuickForm::createElement(
            'submit', $this->getButtonName('next'), 'Next step >>');
        $this->addGroup($buttons, 'buttons', '', '&nbsp', false);
    }
}


class pubStep3Page extends HTML_QuickForm_Page {
    var $masterPage;

    function pubStep3Page($name, &$masterPage) {
        parent::HTML_QuickForm_Page($name);
        $this->masterPage =& $masterPage;
    }

    function buildForm() {
        global $db;

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
                                $this->masterPage->helpTooltip(
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
    var $masterPage;

    function ActionDisplay(&$masterPage) {
        $this->masterPage =& $masterPage;
    }

    function _renderForm(&$page) {
        $renderer =& $page->defaultRenderer();

        $container = $page->controller->container();

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

        $this->masterPage->renderer =& $renderer;
        $this->masterPage->javascript();

        echo $this->masterPage->toHtml();
    }
}

class ActionProcess extends HTML_QuickForm_Action {
    var $masterPage;

    function ActionProcess($masterPage) {
        $this->masterPage = $masterPage;
    }

    function perform(&$page, $actionName) {
        global $db;

        $values = $page->controller->exportValues();
        if (count($values['authors']) > 0)
            foreach ($values['authors'] as $index => $author) {
                $pos = strpos($author, ':');
                if ($pos !== false) {
                    $values['authors'][$index] = substr($author, $pos + 1);
                }
            }

        $this->masterPage->contentPre
            .= 'values<pre>' . print_r($values, true) . '</pre>';

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
        foreach ($values['extra_info_from_list'] as $info) {
            if ($info != '')
                $extra_info_arr[] = $info;
        }

        $this->masterPage->contentPre .= 'extra_info<br/><pre>'
            . print_r($extra_info_arr, true) . '</pre>';
        $pub->extra_info = implode('; ', $extra_info_arr);

        $pub->dbSave($db);

        $this->masterPage->contentPre .= 'pub<br/><pre>'
            . print_r($pub, true) . '</pre>';

        // copy files here - get the element containing the upload
        $path = FS_PATH . '/uploaded_files/' . $pub->pub_id;
        $element =& $page->getElement('uploadpaper');

        if ($element->isUploadedFile()) {
            $basename = 'paper_' . $_FILES['uploadpaper']['name'];
            $filename = $path . '/' . $basename;
            $relativename = $pub->pub_id . '/'. $basename;

            $vars = array('path', 'basename', 'filename', 'relativename');

            foreach ($vars as $v)
                $this->masterPage->contentPre .= $v . '<br/><pre>'
                . print_r($$v, true) . '</pre>';

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

                    $vars = array('basename', 'filename', 'relativename');

                    foreach ($vars as $v)
                        $this->masterPage->contentPre .= $v . '<br/><pre>'
                        . print_r($$v, true) . '</pre>';

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

        echo $this->masterPage->toHtml();
        //$page->controller->container(true);
    }
}

class add_publication extends pdHtmlPage {
    var $pub;
    var $venue;
    var $category;
    var $pub_id;
    var $cat_id;
    var $venue_id;
    var $ext;
    var $intpoint;
    var $nummaterials;

    function add_publication() {
        global $db, $logged_in;

        $options = array('pub_id');
        foreach ($options as $opt) {
            if (isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $this->$opt = stripslashes($_GET[$opt]);
            else if (isset($_POST[$opt]) && ($_POST[$opt] != ''))
                $this->$opt = stripslashes($_POST[$opt]);
            else
                $this->$opt = null;
        }

        if ($this->pub_id != null)
            parent::pdHtmlPage('edit_publication');
        else
            parent::pdHtmlPage('add_publication');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        $this->db =& $db;
        $this->form_controller
            = new HTML_QuickForm_Controller('pubWizard', true);
        $this->form_controller->addPage(new pubStep1Page('page1', $this));
        $this->form_controller->addPage(new pubStep2Page('page2', $this));
        $this->form_controller->addPage(new pubStep3Page('page3', $this));


        $this->form_controller->addAction(
            'display', new ActionDisplay($this));
        $this->form_controller->addAction('process', new ActionProcess($this));
    }

    function setDefaults() {
        $this->form->setDefaults($_GET);

        $element = $this->form->getElement('uploadpaper');
        $element->_value = $_GET['uploadpaper'];

        $this->contentPre .= 'element<pre>' . print_r($element, true) . '</pre>';

        $defaults = array();

        if ($this->category != null) {
            $defaults['cat_id'] = $this->category->cat_id;
        }

        if ($this->nummaterials > 0) {
            for ($i = 1; $i <= $this->nummaterials; $i++) {
                if (!isset($_GET['type' . $i])
                    || ($_GET['type' . $i] = '')) {
                    $defaults['type' . $i] = 'Additional Material ' . $i;
                }
            }
       }

        $this->contentPre .= '_GET<pre>' . print_r($_GET, true) . '</pre>';

        if ($this->pub != null) {
            $defaults += array('cat_id'     => $this->pub->category->cat_id,
                               'title'      => $this->pub->title,
                               'abstract'   => $this->pub->abstract,
                               'extra_info' => $this->pub->extra_info,
                               'keywords'   => $this->pub->keywords,
                               'date_published' => $this->pub->published
                );

            if ($this->pub->venue != null)
                $defaults['venue_id'] = $this->pub->venue_id;
            else
                $defaults['venue_id'] = -3;

            if ((count($_GET['authors']) == 0)
                && (count($this->pub->authors) > 0)) {
                foreach ($this->pub->authors as $author)
                    $defaults['authors'][] = $author->author_id;
            }

            if (($this->category != null) && ($this->category->info != null)) {
                foreach (array_values($this->category->info) as $name) {
                    $defaults[$name] = $this->pub->info[$name];
                }
            }

            if (count($this->pub->extPointer) > 0) {
                $c = 1;
                foreach ($this->pub->extPointer as $name => $value) {
                    $defaults['extname' . $c] = $name;
                    $defaults['extvalue' . $c] = $value;
                    $c++;
                }
            }

            if (count($this->pub->intPointer) > 0) {
                $c = 1;
                foreach ($this->pub->intPointer as $i) {
                    $defaults['intpointer' . $c] = $i->value;
                    $c++;
                }
            }

            if ($this->pub->paper == 'No paper')
                $defaults['nopaper'] = 'false';
            else
                $defaults['nopaper'] = 'true';

            if (count($this->pub->additional_info) > 0) {
                $c = 1;
                foreach ($this->pub->additional_info as $info) {
                    $defaults['type' . $c] = $info->type;
                    $defaults['uploadadditional' . $c] = $info->location;
                    $c++;
                }
            }
        }

        if ($this->ext > 0) {
            for ($e = 1; $e <= $this->ext; $e++) {
                if (!isset($_GET['extname'.$e])
                    || $_GET['extname'.$e] == '')
                    $defaults['extname'.$e] = 'Pointer Type';
                if (!isset($_GET['extvalue'.$e])
                    || $_GET['extvalue'.$e] == '')
                    $defaults['extvalue'.$e] = 'http://';
                if (!isset($_GET['extlink'.$e])
                    || $_GET['extlink'.$e] == '')
                    $defaults['extlink'.$e] = 'Title of link';
            }
        }

        if (count($_GET['authors']) > 0) {
            foreach ($_GET['authors'] as $author_id) {
                $defaults['authors'][] = $author_id;
            }
        }

        $this->form->setDefaults($defaults);
    }

    function processForm() {
        $values = $this->form->exportValues();

        if (count($values['authors']) > 0)
            foreach ($values['authors'] as $index => $author) {
                $pos = strpos($author, ':');
                if ($pos !== false) {
                    $values['authors'][$index] = substr($author, $pos + 1);
                }
            }

        $this->contentPre .= 'values<pre>' . print_r($values, true) . '</pre>';
        $this->contentPre .= '_FILES<pre>' . print_r($_FILES, true) . '</pre>';

        if ($pub != null) {
            $pub->load($values);
            $pub->addVenue($this->db, $values['venue_id']);
            $pub->addCategory($this->db, $values['cat_id']);
        }
        else {
            $pub = new pdPublication();
            $pub->load($values);
            if ($this->venue != null)
                $pub->addVenue($this->db, $this->venue);
            if ($this->category != null)
                $pub->addCategory($this->db, $this->category);

            $pub->submit = $_SESSION['user']->name;
        }

        foreach ($values['authors'] as $author_id)
            $pub->addAuthor($this->db, $author_id);

        if (count($pub->info) > 0) {
            foreach (array_keys($pub->info) as $name) {
                $pub->info[$name] = $values[$name];
            }
        }

        $extra_info_arr = array($values['extra_info']);
        foreach ($values['extra_info_from_list'] as $info) {
            if ($info != '')
                $extra_info_arr[] = $info;
        }

        $this->contentPre .= 'extra_infobr/><pre>'
            . print_r($extra_info_arr, true) . '</pre>';

        $pub->extra_info = explode(';', $extra_info_arr);

        $pub->published = $values['date_published'];

        for ($e = 1; $e <= $values['ext']; $e++) {
            $url = '<a href="' . $values['extvalue'.$e] . '">'
                . $values['extlink'.$e] . '</a>';
            $pub->addExtPointer($db, $values['extname'.$e], $url);
        }

        for ($e = 1; $e <= $values['intpoint']; $e++) {
            $pub->addIntPointer($db, $values['intpointer'.$e]);
        }

        $pub->dbSave($this->db);

        $this->contentPre .= 'pub<br/><pre>'
            . print_r($pub, true) . '</pre>';

        // copy files here
        $element = $this->form->getElement('uploadpaper');
        if ($element->isUploadedFile()) {
            $path = FS_PATH . '/uploaded_files/' . $pub->pub_id;
            $basename = 'paper_' . $_FILES['uploadpaper']['name'];
            $filename = $path . '/' . $basename;
            $relativename = $pub->pub_id . '/'. $basename;

            if (!file_exists($path))
                mkdir($path, 0777);
            // mkdir with 0777 does not seem to work
            chmod($path, 0777);

            $element->moveUploadedFile($path, $basename);
            chmod($filename, 0777);
            $pub->dbUpdatePaper($this->db, $relativename);
        }

        if ($values['nummaterials'] > 0) {
        }
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

            window.name="add_publication.php";
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

        function dataKeep(tab) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["page1"].elements.length; i++) {
                var element = document.forms["page1"].elements[i];
                if ((element.type != "submit") && (element.type != "reset")
                    && (element.type != "button") && (element.type != "checkbox")
                    && (element.value != "") && (element.value != null)) {

                    if (element.name == "authors[]") {
                        var author_count = 0;

                        for (j = 0; j < element.length; j++) {
                            if (element[j].selected) {
                                // strip out list name from author_id value
                                qsArray.push("authors[" + author_count
                                             + "]="
                                             + element[j].value.replace(/.+:/,""));
                                author_count++;
                            }
                        }
                    }
                    else if (element.name == "comments")
                        qsArray.push(element.name + "="
                                     + element.value.replace("\"","'"));

                    else if (element.name == "nopaper") {
                        if (element.checked)
                            qsArray.push(element.name + "=" + element.value);
                    }
                    else if (element.name == "ext"){
                        if (tab == "addext")
                            qsArray.push(element.name + "={$ext_next}");
                        else if (tab == "remext")
                            qsArray.push(element.name + "={$ext_prev}");
                        else
                            qsArray.push(element.name + "={$this->ext}");
                    }
                    else if (element.name == "intpoint"){
                        if (tab == "addint")
                            qsArray.push(element.name + "={$intpoint_next}");
                        else if (tab == "remint")
                            qsArray.push(element.name + "={$intpoint_prev}");
                        else
                            qsArray.push(element.name + "={$this->intpoint}");
                    }
                    else if (element.name == "nummaterials"){
                        if (tab == "addnum")
                            qsArray.push(element.name
                                         + "={$nummaterials_next}");
                        else if (tab == "remnum")
                            qsArray.push(element.name
                                         + "={$nummaterials_prev}");
                    }
                    else
                        qsArray.push(element.name + "=" + element.value);
                }
            }

            if ((tab == "addnum") || (tab == "remnum"))
                qsArray.push("#step2");
            else if (((tab == "addext") || (tab == "remext"))
                     || ((tab == "addint") || (tab == "remint")))
                qsArray.push("#pointers");
            else if (tab != "none")
                qsArray.push("#" + tab);

            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace("\"", "?");
                qsString.replace(" ", "%20");
            }

            location.href
                = "http://" + "{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        function verify(num) {
            var pubform = document.forms["page1"];
            if (pubform.elements["category"].value == "") {
                alert("Please select a category for the publication.");
                return false;
            }
            else if (pubform.elements["title"].value == "") {
                alert("Please enter a title for the publication.");
                return false;
            }
            else if ((pubform.elements["nopaper"].value == "false")
                     && (pubform.elements["uploadpaper"].value == "")) {
                alert("Please choose a paper to upload or select \"No paper\".");
                return false;
            }
            else if (pubform.elements["selected_authors"].value == "") {
                alert("Please select the author(s) of this publication.");
                return false;
            }
            else if (pubform.elements["abstract"].value == "") {
                alert("Please enter the abstract for this publication.");
                return false;
            }
            else if (pubform.elements["keywords"].value == "") {
                alert("Please enter the keywords for this publication.");
                return false;
            }

            return true;
        }

        function resetAll() {
            location.href="./add_publication.php";
        }
        function refresher() { window.location.reload(true);}

        </script>
JS_END;
    }
}

$db =& dbCreate();
$page = new add_publication();
$page->run();
$db->close();

?>
