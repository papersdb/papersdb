<?php ;

// $Id: advanced_search.php,v 1.37 2006/07/27 21:40:26 aicmltec Exp $

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
require_once 'includes/jscalendar.php';

/**
 * Renders the whole page.
 */
class advanced_search extends pdHtmlPage {
    var $cat_list;
    var $category;
    var $auth_list;
    var $search;
    var $cat_id;
    var $title;
    var $authortyped;
    var $paper;
    var $abstract;
    var $venue;
    var $keywords;
    var $authorselect;

    function advanced_search() {
        parent::pdHtmlPage('advanced_search');

        $this->papercheck        = '1';
        $this->halfabstractcheck = '1';
        $this->datecheck         = '1';
        $this->venuecheck        = '1';

        if(isset($_GET['search']) && ($_GET['search'] != ''))
            $this->search = stripslashes($_GET['search']);

        $options = array('search', 'cat_id', 'title', 'authortyped',
                         'paper', 'abstract', 'venue', 'keywords',
                         'categorycheck',
                         'extracheck',
                         'papercheck',
                         'additionalcheck',
                         'halfabstractcheck',
                         'venuecheck',
                         'keywordscheck',
                         'datecheck');

        foreach ($options as $opt)
            if(isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $this->$opt = stripslashes($_GET[$opt]);

        if (isset($_GET['authorselect']) && (count($_GET['authorselect']) > 0))
            $this->authorselect = $_GET['authorselect'];

        if (isset($_GET['datesGroup']) && (count($_GET['datesGroup']) > 0))
            $this->datesGroup = $_GET['datesGroup'];

        $this->db =& dbCreate();

        $this->cat_list = new pdCatList($this->db);
        $this->auth_list = new pdAuthorList($this->db);

        $this->category = new pdCategory();
        $this->category->dbLoad($this->db, $this->cat_id);

        $this->createForm();
        $this->setFormValues();

        // NOTE: order is important here: this must be called after creating
        // the form elements, but before rendering them.
        $renderer =& $this->form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $this->renderer =& $renderer;
        $this->form->accept($renderer);
        $this->javascript();
        $this->db->close();
    }

    /**
     * Outputs the java script used by the page.
     */
    function javascript() {
        $this->js = <<<END

            <script language="JavaScript" type="text/JavaScript">
            window.name="search_publication.php";

        function dataKeep(num) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
                var element = document.forms["pubForm"].elements[i];
                if ((element.value != "") && (element.value != null)
                    && (element.type != "submit")) {

                    if (element.type == "checkbox") {
                        if (element.checked) {
                            qsArray.push(element.name + "=" + element.value);
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
        </script>
END;
    }

    /**
     * Creates the from used on this page. The renderer is then used to
     * display the form correctly on the page.
     *
     * Note: jscalendar.php is used as a shorcut way of entering date values.
     */
    function createForm() {
        $form = new HTML_QuickForm('pubForm', 'post',
                                   'search_publication_db.php',
                                   '_self', 'multipart/form-data');

        $form->addElement('header', null, 'Quick Search');
        $qsElement[0] =& HTML_QuickForm::createElement(
            'text', 'search', null, array('size' => 50, 'maxlength' => 250));
        $qsElement[1] =& HTML_QuickForm::createElement(
            'submit', 'Quick', 'Search');
        $form->addGroup($qsElement, 'quicksearch', 'Search for:', '&nbsp;',
                        false);

        $form->addElement('header', null, 'Advanced Search');
        $form->addElement('select', 'cat_id', 'Category:',
                          array('' => '-- All Categories --')
                          + $this->cat_list->list,
                          array('onChange' => 'dataKeep(0);'));
        $form->addElement('text', 'title', 'Title:',
                          array('size' => 60, 'maxlength' => 250));

        $authElement[0] =& HTML_QuickForm::createElement(
            'text', 'authortyped', null,
            array('size' => 60, 'maxlength' => 250));
        $authElement[1] =& HTML_QuickForm::createElement(
            'static', 'auth_label', null, 'or select from list');
        $authElement[2] =& HTML_QuickForm::createElement(
            'select', 'authorselect', null, $this->auth_list->list,
            array('multiple' => 'multiple', 'size' => 10));
        $form->addGroup($authElement, 'authors', 'Authors:', '<br/>',
                        false);

        $form->addElement('text', 'paper', 'Paper filename:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'abstract', 'Abstract:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'venue', 'Venue:',
                          array('size' => 60, 'maxlength' => 250));

        $kwElement[0] =& HTML_QuickForm::createElement(
            'text', 'keywords', null,
            array('size' => 60, 'maxlength' => 250));
        $kwElement[1] =& HTML_QuickForm::createElement(
            'static', 'auth_label', null, 'seperate using semi-colon (;)');
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
                'src' => 'calendar.gif',
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
                'src' => 'calendar.gif',
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

        $datesGroup[] = HTML_QuickForm::createElement(
            'text', 'startdate', null,
            array('readonly' => '1', 'id' => 'startdate', 'size' => 10));
        $datesGroup[] = HTML_QuickForm::createElement(
            'jscalendar', 'startdate_calendar', null, $startdate_options);
        $datesGroup[] = HTML_QuickForm::createElement(
            'static', 'date_label', null, 'and');
        $datesGroup[] = HTML_QuickForm::createElement(
            'text', 'enddate', null,
            array('readonly' => '1', 'id' => 'enddate', 'size' => 10));
        $datesGroup[] = HTML_QuickForm::createElement(
            'jscalendar', 'enddate_calendar', null, $enddate_options);

        $form->addGroup($datesGroup, 'datesGroup', 'Published between:',
                        '&nbsp;');

        $form->addElement('header', null, 'Show in Results');
        unset($searchPrefs);
        $searchPrefs = array(
            'categorycheck'     => 'Category',
            'extracheck'        => 'Category Related Information',
            'papercheck'        => 'Link to Paper',
            'additionalcheck'   => 'Link to Additional Material',
            'halfabstractcheck' => 'Short Abstract',
            'venuecheck'        => 'Publication Venue',
            'keywordscheck'     => 'Keywords',
            'datecheck'         => 'Date Published');

        foreach ($searchPrefs as $name => $text) {
            $prefElements[] =& HTML_QuickForm::createElement(
                'checkbox', $name, null, $text, array('size' => 10),
                array('no', 'yes'));
        }
        $form->addGroup($prefElements, 'prefsGroup', null, "<br/>\n",
                        false);

        $buttons[0] =& HTML_QuickForm::createElement(
            'submit', 'Submit', 'Search');
        $buttons[1] =& HTML_QuickForm::createElement(
            'submit', 'Clear', 'Clear');
        $form->addGroup($buttons, 'buttonsGroup', '', '&nbsp;', false);
        $this->form =& $form;
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
            'categorycheck'     => ($this->categorycheck != ''),
            'extracheck'        => ($this->extracheck != ''),
            'papercheck'        => ($this->papercheck != ''),
            'additionalcheck'   => ($this->additionalcheck != ''),
            'halfabstractcheck' => ($this->halfabstractcheck != ''),
            'venuecheck'        => ($this->venuecheck != ''),
            'keywordscheck'     => ($this->keywordscheck != ''),
            'datecheck'         => ($this->datecheck != ''));

        $defaultValues['datesGroup']['startdate']
            = $this->datesGroup['startdate'];
        $defaultValues['datesGroup']['enddate'] = $this->datesGroup['enddate'];

        if (is_object($this->category) && is_array($this->category->info)) {
            foreach ($this->category->info as $info) {
                $defaultValues[strtolower($info->name)] = $_GET[$info->name];
            }
        }

        if (count($this->authorselect) > 0)
            $defaultValues['authorselect'] =& $this->authorselect;

        $this->form->setDefaults($defaultValues);
    }
}

session_start();
$logged_in = check_login();
$page = new advanced_search();
echo $page->toHtml();

?>


