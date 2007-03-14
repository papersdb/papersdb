<?php ;

// $Id: advanced_search.php,v 1.56 2007/03/14 02:58:47 loyola Exp $

/**
 * Performs advanced searches on publication information in the
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
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdSearchParams.php';
require_once 'includes/jscalendar.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class advanced_search extends pdHtmlPage {
    var $debug = 0;
    var $form_name = 'pubForm';
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
    var $startdate;
    var $enddate;

    function advanced_search() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('advanced_search');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        $this->cat_list = new pdCatList($this->db);

        $this->category = new pdCategory();
        $this->category->dbLoad($this->db, $this->cat_id);

        $form = $this->createForm();
        $this->form =& $form;
        $this->setFormValues();

        if (isset($_SESSION['search_params'])
            && (count($_SESSION['search_params']->authorselect) > 0))
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
    }

    /**
     * Creates the from used on this page. The renderer is then used to
     * display the form correctly on the page.
     *
     * Note: jscalendar.php is used as a shorcut way of entering date values.
     */
    function createForm() {
        $user = null;

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
                          + $this->cat_list->list);

        $auth_list = new pdAuthorList($this->db);

        if (($this->access_level > 0) && ($_SESSION['user']->author_id != '')) {
            $user =& $_SESSION['user'];
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

        if ($user != null) {
            $authElements[] =& HTML_QuickForm::createElement(
                'checkbox', 'author_myself',
                null, 'myself', null, array('', $user->author_id));
        }

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

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'date', 'startdate', 'Start Date:',
                    array('format' => 'YM', 'minYear' => '1985')),
                HTML_QuickForm::createElement('static', null, null, 'and'),
                HTML_QuickForm::createElement(
                    'date', 'enddate', 'End Date:',
                    array('format' => 'YM', 'minYear' => '1985')),
                ),
            null, 'Published Between:', '&nbsp;', false);

        $form->addGroup(
            array(
                HTML_QuickForm::createElement('reset', 'Clear', 'Clear'),
                HTML_QuickForm::createElement(
                    'button', 'fill_last', 'Load Previous Search Terms',
                    array('onClick' => 'lastSearchUse();')),
                HTML_QuickForm::createElement('submit', 'Submit', 'Search')
                ),
            'buttonsGroup', '', '&nbsp;', false);
        return $form;
    }

    /**
     * Assigns the form's values as per the HTTP GET string.
     */
    function setFormValues() {
        $defaults = array(
            'search'     => $this->search,
            'cat_id'     => $this->cat_id,
            'title'      => $this->title,
            'authortyped'=> $this->authortyped,
            'paper'      => $this->paper,
            'abstract'   => $this->abstract,
            'venue'      => $this->venue,
            'keywords'   => $this->keywords,
            'startdate'  => array('Y' => $this->startdate['Y'],
                                  'M' => $this->startdate['M']),
            'enddate'    => array('Y' => $this->enddate['Y'],
                                  'M' => $this->enddate['M']));

        if (count($this->authorselect) > 0)
            $defaults['authorselect'] =& $this->authorselect;

        $this->form->setConstants($defaults);
    }

    /**
     * Outputs the java script used by the page.
     */
    function javascript() {
        if (isset($_SESSION['search_params']))
            $sp = $_SESSION['search_params'];
        else
            $sp = new pdSearchParams();

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
                    && (element.type != "reset")
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

            form.cat_id.value      = "{$sp->cat_id}";
            form.title.value       = "{$sp->title}";
            form.authortyped.value = "{$sp->authortyped}";
            form.paper.value       = "{$sp->paper}";
            form.abstract.value    = "{$sp->abstract}";
            form.venue.value       = "{$sp->venue}";
            form.keywords.value    = "{$sp->keywords}";

            for (var i = 0; i < form.elements.length; i++) {
                if (form.elements[i].name == "startdate[Y]")
                    form.elements[i].value = "{$sp->startdate['Y']}";
                if (form.elements[i].name == "startdate[M]")
                    form.elements[i].value = "{$sp->startdate['M']}";
                if (form.elements[i].name == "enddate[Y]")
                    form.elements[i].value = "{$sp->enddate['Y']}";
                if (form.elements[i].name == "enddate[M]")
                    form.elements[i].value = "{$sp->enddate['M']}";
            }

            if ("{$sp->author_myself}" == "1") {
                form.author_myself.checked = true;
            }

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

$page = new advanced_search();
echo $page->toHtml();

?>


