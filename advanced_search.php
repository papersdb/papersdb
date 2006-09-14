<?php ;

// $Id: advanced_search.php,v 1.47 2006/09/14 20:28:49 aicmltec Exp $

/**
 * \file
 *
 * \brief Performs advanced searches on publication information in the
 * database.
 *
 * It is mainly only forms, with little data being read from the database. It
 * sends the users input to search_publication_db.php.
 *
 * Uses the Pear library's HTML_QuickForm and HTML_Table to create and
 * display the content.
 *
 * \note Follows coding standards from
 * http://pear.php.net/manual/en/standards.php.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdSearchParams.php';
require_once 'includes/jscalendar.php';

/**
 * Renders the whole page.
 */
class advanced_search extends pdHtmlPage {
    var $form_name = 'pubForm';
    var $db;
    var $cat_list;
    var $category;
    var $search;
    var $cat_id;
    var $title;
    var $authortyped;
    var $paper;
    var $abstract;
    var $venue;
    var $keywords;
    var $authorselect;
    var $selected_authors;

    function advanced_search() {
        pubSessionInit();
        parent::pdHtmlPage('advanced_search');

        if(isset($_GET['search']) && ($_GET['search'] != ''))
            $this->search = stripslashes($_GET['search']);

        $options = array('search', 'cat_id', 'title', 'authortyped',
                         'paper', 'abstract', 'venue', 'keywords');

        foreach ($options as $opt)
            if(isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $this->$opt = stripslashes($_GET[$opt]);

        if (isset($_GET['authorselect']) && (count($_GET['authorselect']) > 0))
            $this->authorselect = $_GET['authorselect'];

        $db =& dbCreate();
        $this->db =& $db;

        $this->cat_list = new pdCatList($db);

        $this->category = new pdCategory();
        $this->category->dbLoad($db, $this->cat_id);

        $form =& $this->createForm();
        $this->form =& $form;
        $this->setFormValues();

        if (isset($_SESSION['search_params']))
            $this->selected_authors = ':'
                . implode(':', $_SESSION['search_params']->authorselect)
                . ':';


        // NOTE: order is important here: this must be called after creating
        // the form elements, but before rendering them.
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
        $db->close();
    }

    /**
     * Creates the from used on this page. The renderer is then used to
     * display the form correctly on the page.
     *
     * Note: jscalendar.php is used as a shorcut way of entering date values.
     */
    function createForm() {
        global $access_level;

        $db =& $this->db;

        $form = new HTML_QuickForm($this->form_name, 'get',
                                   'search_publication_db.php',
                                   '_self', 'multipart/form-data');

        $form->addElement('header', null, 'Advanced Search');
        $form->addElement('text', 'title', 'Title:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'venue', 'Venue:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('select', 'cat_id', 'Category:',
                          array('' => '-- All Categories --')
                          + $this->cat_list->list,
                          array('onChange' => 'dataKeep(0);'));

        $auth_list = new pdAuthorList($db);

        if ($access_level > 0) {
            $user =& $_SESSION['user'];

            $this->contentPost .= '<pre>' . print_r($user, true) . '</pre>';

            $authElements[] =& HTML_QuickForm::createElement(
                'advcheckbox', 'author_myself',
                null, 'myself', null, array('', $user->author_id));

            unset($auth_list->list[$user->author_id]);
        }

        $authElements[] =& HTML_QuickForm::createElement(
            'text', 'authortyped', null,
            array('size' => 60, 'maxlength' => 250));
        $authElements[] =& HTML_QuickForm::createElement(
            'static', 'auth_label', null, 'or select from list');
        $authElements[] =& HTML_QuickForm::createElement(
            'select', 'authorselect', null, $auth_list->list,
            array('multiple' => 'multiple', 'size' => 10));

        $form->addGroup($authElements, 'authors', 'Authors:', '<br/>',
                        false);

        $form->addElement('text', 'paper', 'Paper filename:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'abstract', 'Abstract:',
                          array('size' => 60, 'maxlength' => 250));

        $kwElement[0] =& HTML_QuickForm::createElement(
            'text', 'keywords', null,
            array('size' => 60, 'maxlength' => 250));
        $kwElement[1] =& HTML_QuickForm::createElement(
            'static', 'auth_label', null,
            '<span id="small">seperate using semi-colon (;)</span>');
        $form->addGroup($kwElement, 'keywordsGroup', 'Keywords:', '<br/>',
                        false);

        if (($this->category != null) && ($this->category->info != null)) {
            foreach ($this->category->info as $info => $name) {
                $form->addElement('text', strtolower($name), $name . ':',
                                  array('size' => 60, 'maxlength' => 250));
            }
        }

        $startdate_options = array(
            'baseURL' => 'includes/',
            'styleCss' => 'calendar.css',
            'language' => 'en',
            'image' => array(
                'src' => 'images/calendar.gif',
                'border' => 0
                ),
            'setup' => array(
                'inputField' => 'startdate',
                'ifFormat' => '%Y-%m-%d',
                'showsTime' => false,
                'time24' => true,
                'weekNumbers' => false,
                'showOthers' => true
                )
            );

        $enddate_options = array(
            'baseURL' => 'includes/',
            'styleCss' => 'calendar.css',
            'language' => 'en',
            'image' => array(
                'src' => 'images/calendar.gif',
                'border' => 0
                ),
            'setup' => array(
                'inputField' => 'enddate',
                'ifFormat' => '%Y-%m-%d',
                'showsTime' => false,
                'time24' => true,
                'weekNumbers' => false,
                'showOthers' => true
                )
            );


        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'startdate', null,
                    array('readonly' => '1', 'id' => 'startdate', 'size' => 10)),
                HTML_QuickForm::createElement(
                    'jscalendar', 'startdate_calendar', null,
                    $startdate_options),
                HTML_QuickForm::createElement(
                    'static', 'date_label', null, 'and'),
                HTML_QuickForm::createElement(
                    'text', 'enddate', null,
                    array('readonly' => '1', 'id' => 'enddate', 'size' => 10)),
                HTML_QuickForm::createElement(
                    'jscalendar', 'enddate_calendar', null, $enddate_options)),
            null, 'Published between:', '&nbsp;');

        $form->addGroup(
            array(
                HTML_QuickForm::createElement('submit', 'Submit', 'Search'),
                HTML_QuickForm::createElement('reset', 'Clear', 'Clear'),
                HTML_QuickForm::createElement(
                    'button', 'fill_last', 'Use Previous Search Terms',
                    array('onClick' => 'lastSearchUse();'))),
            'buttonsGroup', '', '&nbsp;', false);
        return $form;
    }

    /**
     * Assigns the form's values as per the HTTP GET string.
     */
    function setFormValues() {
        $defaultValues = array(
            'search'            => $this->search,
            'cat_id'            => $this->cat_id,
            'title'             => $this->title,
            'authortyped'       => $this->authortyped,
            'paper'             => $this->paper,
            'abstract'          => $this->abstract,
            'venue'             => $this->venue,
            'keywords'          => $this->keywords,
            'startdate'         => $this->startdate,
            'enddate'           => $this->enddate);

        if (is_object($this->category)
            && is_array($this->category->info)) {
            foreach ($this->category->info as $info) {
                $defaultValues[strtolower($info->name)]
                    = $_GET[$info->name];
            }
        }

        if (count($this->authorselect) > 0)
            $defaultValues['authorselect'] =& $this->authorselect;

        $this->form->setConstants($defaultValues);
    }

    /**
     * Outputs the java script used by the page.
     */
    function javascript() {
        $this->js = <<<END

            <script language="JavaScript" type="text/JavaScript">
            window.name="search_publication.php";

        function dataKeep(num) {
            var form = document.forms["{$this->form_name}"];
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < form.elements.length; i++) {
                var element = form.elements[i];
                if ((element.value != "") && (element.value != null)
                    && (element.type != "button")
                    && (element.type != "submit")) {

                    if (element.type == "checkbox") {
                        if (element.checked) {
                            qsArray.push(element.name + "=" + element.value);
                        }
                    } else if (element.type == "select-multiple"){
                        var select_name = element.name;
                        if (select_name.indexOf("[]") > 0) {
                            select_name = select_name.substr(0, select_name.length - 2);
                        }

                        var count = 0;
                        for (i=0; i < element.length; i++) {
                            if (element.options[i].selected) {
                                qsArray.push(select_name + "[" + count + "]=" + element.options[i].value);
                                count++;
                            }
                        }
                    } else {
                        qsArray.push(element.name + "=" + element.value);
                    }
                }
            }
            if (qsArray.length > 0) {
                qsString = qsArray.join("&");
                qsString.replace(" ", "%20");
            }
            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + qsString;
        }

        function lastSearchUse() {
            var form = document.forms["{$this->form_name}"];
            var authorselect = form.elements["authorselect[]"];
            var selected_authors = "{$this->selected_authors}";

            form.cat_id.value      = "{$_SESSION['search_params']->cat_id}";
            form.title.value       = "{$_SESSION['search_params']->title}";
            form.authortyped.value = "{$_SESSION['search_params']->authortyped}";
            form.paper.value       = "{$_SESSION['search_params']->paper}";
            form.abstract.value    = "{$_SESSION['search_params']->abstract}";
            form.venue.value       = "{$_SESSION['search_params']->venue}";
            form.keywords.value    = "{$_SESSION['search_params']->keywords}";
            form.startdate.value   = "{$_SESSION['search_params']->startdate}";
            form.enddate.value     = "{$_SESSION['search_params']->enddate}";

            for (var i =0; i < authorselect.length; i++) {
                authorselect.options[i].selected = false;
                if (selected_authors.indexOf(":" + authorselect.options[i].value + ":") >= 0) {
                    authorselect.options[i].selected = true;
                }
            }
            dataKeep(0);
        }
        </script>
END;
    }
}

session_start();
$access_level = check_login();
$page = new advanced_search();
echo $page->toHtml();

?>


