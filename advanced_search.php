<?php ;

// $Id: advanced_search.php,v 1.29 2006/07/10 14:21:36 aicmltec Exp $

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

/**
 * Renders the whole page.
 */
class advanced_search extends pdHtmlPage {
    var $cat_list;
    var $category;
    var $auth_list;
    var $expand;
    var $search;
    var $cat_id;
    var $title;
    var $authortyped;
    var $paper;
    var $abstract;
    var $venue;
    var $keywords;

    function advanced_search() {
        parent::pdHtmlPage('advanced_search');

        if(isset($_GET['expand']) && ($_GET['expand'] == 'true')) {
            $this->expand = true;
        }

        if(isset($_GET['search']) && ($_GET['search'] != ''))
            $this->search = stripslashes($_GET['search']);

        $options = array('search', 'cat_id', 'title', 'authortyped',
                         'paper', 'abstract', 'venue', 'keywords');
        foreach ($options as $opt)
            if(isset($_GET[$opt]) && ($_GET[$opt] != ''))
                $this->$opt = stripslashes($_GET[$opt]);

        $this->db =& dbCreate();

        $this->cat_list = new pdCatList($this->db);
        $this->auth_list = new pdAuthorList($this->db);

        $this->category = new pdCategory();
        $this->category->dbLoad($this->db, $this->cat_id);

        $this->createForm();
        $this->setFormValues();

        // NOTE: order is important here: this must be called after creating
        // the form elements, but before rendering them.
        $renderer =& $this->renderer;

        $renderer =& $this->form->defaultRenderer();

        $renderer->setFormTemplate('<table border="0" cellpadding="3" cellspacing="2" bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate('<tr><td style="white-space:nowrap;background:#996;color:#ffc;" align="left" colspan="2"><b>{header}</b></td></tr>');
        $renderer->setGroupTemplate('<table><tr>{content}</tr></table>', 'name');

        $renderer->setElementTemplate(
            '<tr><td><b>{label}</b></td><td>{element}'
            . '<br/><span style="font-size:10px;">seperate using semi-colon (;)</span>'
            . '</td></tr>',
            'keywords');

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

            <script language="JavaScript" src="calendar.js"></script>

            <script language="JavaScript" type="text/JavaScript">
            window.name="search_publication.php";

        function dataKeep(num) {
            var qsArray = new Array();
            var qsString = "";

            for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
                var element = document.forms["pubForm"].elements;
                if ((element.value != "") && (element.value != null)) {
                    temp_qs += element.name + "=" + element.value;
                        qsArray.push(element.name + "=" + element.value);
                }
            }
            if (num == 1) {
                qsArray.push("expand=true");
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
     * Note: calendar.js is used as a shorcut way of entering date values.
     */
    function createForm() {
        $form = new HTML_QuickForm('pubForm', 'post',
                                   'search_publication_db.php',
                                   '_self', 'multipart/form-data');

        $form->addElement('header', null, 'Quick Search');
        $qsElement[0] = HTML_QuickForm::createElement(
            'text', 'search', null, array('size' => 50, 'maxlength' => 250));
        $qsElement[1] = HTML_QuickForm::createElement(
            'submit', 'Quick', 'Search');
        $form->addGroup($qsElement, 'quicksearch', 'Search for:', '&nbsp;',
                        false);

        $form->addElement('header', null, 'Advanced Search');
        $form->addElement('select', 'cat_id', 'Category:',
                          $this->cat_list->list,
                          array('onChange' => 'dataKeep(0);'));
        $form->addElement('text', 'title', 'Title:',
                          array('size' => 60, 'maxlength' => 250));

        $authElement[0] = HTML_QuickForm::createElement(
            'text', 'authortyped', null,
            array('size' => 20, 'maxlength' => 250));
        $authElement[1] = HTML_QuickForm::createElement(
            'static', 'auth_label', null, 'or select from list');
        $authElement[2] = HTML_QuickForm::createElement(
            'select', 'authorselect', null, $this->auth_list->list,
            array('multiple' => 'multiple', 'size' => 4));
        $form->addGroup($authElement, 'authors', 'Authors:', '&nbsp;',
                        false);

        $form->addElement('text', 'paper', 'Paper filename:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'abstract', 'Abstract:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'venue', 'Venue:',
                          array('size' => 60, 'maxlength' => 250));

        $kwElement[0] = HTML_QuickForm::createElement(
            'text', 'keywords', null,
            array('size' => 60, 'maxlength' => 250));
        $kwElement[1] = HTML_QuickForm::createElement(
            'static', 'auth_label', null, 'seperate using semi-colon (;)');
        $form->addGroup($kwElement, 'keywordsGroup', 'Keywords:', '<br/>',
                        false);

        if (($this->category != null) && ($this->category->info != null)) {
            foreach ($this->category->info as $info) {
                $form->addElement('text', strtolower($info->name), null,
                                  array('size' => 60, 'maxlength' => 250));
            }
        }

        $dates[0] = HTML_QuickForm::createElement(
            'text', 'startdate', null,
            array('size' => 10, 'maxlength' => 10));
        $dates[1] = HTML_QuickForm::createElement(
            'static', 'date_js', null,
            '<a href="javascript:doNothing()" '
            . 'onClick="setDateField(document.pubForm.startdate);'
            . 'top.newWin=window.open(\'calendar.html\', \'cal\','
            . '\'dependent=yes,width=230,height=250,screenX=200,'
            . 'screenY=300,titlebar=yes\')">'
            . '<img src="calendar.gif" border=0></a> (yyyy-mm-dd) and ');
        $dates[2] = HTML_QuickForm::createElement(
            'text', 'enddate', null,
            array('size' => 10, 'maxlength' => 10));
        $dates[3] = HTML_QuickForm::createElement(
            'static', 'date_js', null,
            '<a href="javascript:doNothing()" '
            . 'onClick="setDateField(document.pubForm.enddate);'
            . 'top.newWin=window.open(\'calendar.html\', \'cal\','
            . '\'dependent=yes,width=230,height=250,screenX=200,'
            . 'screenY=300,titlebar=yes\')">'
            . '<img src="calendar.gif" border=0></a> (yyyy-mm-dd)');
        $form->addGroup($dates, 'datesGroup', 'Published between:', '&nbsp;',
                        false);

        $form->addElement('header', null, 'Advanced Search Preferences');
        unset($searchPrefs);
        $searchPrefs = array(
            'titlecheck'        => 'Title',
            'authorcheck'       => 'Author(s)',
            'categorycheck'     => 'Category',
            'extracheck'        => 'Category Related Information',
            'papercheck'        => 'Link to Paper',
            'additionalcheck'   => 'Link to Additional Material',
            'halfabstractcheck' => 'Short Abstract',
            'venuecheck'        => 'Publication Venue',
            'keywordscheck'     => 'Keywords',
            'datecheck'         => 'Date Published');

        $c = 0;
        $label = 'Select Preferences:';
        foreach ($searchPrefs as $name => $text) {
            $prefElements[] = HTML_QuickForm::createElement(
                'advcheckbox', $name, null, $text, array('size' => 10),
                array('no', 'yes'));
        }
        $form->addGroup($prefElements, 'prefsGroup'.$c, null, '<br/>',
                        false);

        $buttons[0] = HTML_QuickForm::createElement(
            'submit', 'Submit', 'Search');
        $buttons[1] = HTML_QuickForm::createElement(
            'submit', 'Clear', 'Clear');
        $form->addGroup($buttons, 'buttonsGroup', '', '&nbsp;', false);

        if($this->expand)
            $form->addElement('hidden', 'expand', 'true');

        $form->setRequiredNote('<font color="#FF0000">*'
                               . '</font> shows the required fields.');
        $form->setJsWarnings('Those fields have errors :',
                             'Thanks for correcting them.');

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
            'titlecheck'        => 'yes',
            'authorcheck'       => 'yes',
            'additionalcheck'   => 'yes',
            'halfabstractcheck' => 'yes',
            'datecheck'         => 'yes');

        if (is_object($this->category) && is_array($this->category->info)) {
            foreach ($this->category->info as $info) {
                $defaultValues[strtolower($info->name)] = $_GET[$info->name];
            }
        }

        $this->form->setDefaults($defaultValues);
    }

}

$page = new advanced_search();
echo $page->toHtml();

?>


