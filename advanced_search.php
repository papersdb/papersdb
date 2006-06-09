<?php ;

// $Id: advanced_search.php,v 1.23 2006/06/09 22:39:36 aicmltec Exp $

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

    function advanced_search() {
        parent::pdHtmlPage('advanced_search');

        $cat_id = intval($_GET['cat_id']);
        isValid($cat_id);

        $this->db =& dbCreate();

        $this->cat_list = new pdCatList($this->db);
        $this->auth_list = new pdAuthorList($this->db);

        $this->category = new pdCategory();
        $this->category->dbLoad($this->db, $cat_id);

        $this->createForm();
        $this->setFormValues();

        // NOTE: order is important here: this must be called after creating
        // the form elements, but before rendering them.
        $this->renderer = new HTML_QuickForm_Renderer_QuickHtml();
        $renderer =& $this->renderer;
        $this->form->accept($renderer);
        $this->createTable($renderer);
        $this->javascript();

        $this->contentPre = '<h2><b><u>Search</u></b></h2>';

        if($_GET['expand'] == "true") {
            $this->contentPost = $renderer->elementToHtml('expand');
        }
        else {
            $this->contentPost = $renderer->elementToHtml('titlecheck')
                . $renderer->elementToHtml('authorcheck')
                . $renderer->elementToHtml('halfabstractcheck')
                . $renderer->elementToHtml('datecheck');
        }

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
        function resetAll() {
            location.href="advanced_search.php";
        }
        function refresher() { window.location.reload(true);}

        function dataKeep(num) {
            var temp_qs = "";
            var info_counter = 0;

            for (i = 0; i < document.forms["pubForm"].elements.length; i++) {
                var element = document.forms["pubForm"].elements;
                if ((element.value != "") && (element.value != null)) {
                    if (info_counter > 0) {
                        temp_qs = temp_qs + "&";
                    }

                    temp_qs += element.name + "=" + element.value;
                    info_counter++;
                }
            }
            if(num == 1) {
                if (info_counter > 0)
                    temp_qs += "&";
                temp_qs += "expand=true";
            }
            temp_qs.replace("\"", "?");
            temp_qs.replace(" ", "%20");
            location.href
                = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?"
                + temp_qs;
        }
        </script>
END;
    }

    /**
     * Creates the from used on this page. The renderer is then used to
     * display the form correctly on the page (see createTable).
     *
     * Note: calendar.js is used as a shorcut way of entering date values.
     */
    function createForm() {
        $form = new HTML_QuickForm('pubForm', 'post',
                                   'search_publication_db.php',
                                   '_self', 'multipart/form-data');

        $form->addElement('text', 'search', null,
                          array('size' => 50, 'maxlength' => 250));
        $form->addElement('submit', 'Quick', 'Search');

        $options[''] = 'All Categories';
        foreach ($this->cat_list->list as $cat) {
            $options[$cat->cat_id] = $cat->category;
        }
        $form->addElement('select', 'cat_id', null, $options,
                          array('onChange' => 'dataKeep(0);'));
        $form->addElement('text', 'title', null,
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'authortyped', null,
                          array('size' => 20, 'maxlength' => 250));

        unset($options);
        $options = array('' => 'All Authors');
        foreach($this->auth_list->list as $auth) {
            $options[$auth->author_id] = $auth->name;
        }
        $form->addElement('select', 'authorselect', null, $options,
                          array('multiple' => 'multiple', 'size' => 4));
        $form->addElement('text', 'paper', null,
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'abstract', null,
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'venue', null,
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'keywords', null,
                          array('size' => 60, 'maxlength' => 250));

        if (is_object($this->category) && is_array($this->category->info)) {
            foreach ($this->category->info as $info) {
                $form->addElement('text', strtolower($info->name), null,
                                  array('size' => 60, 'maxlength' => 250));
            }
        }

        $form->addElement('text', 'startdate', null,
                          array('size' => 10, 'maxlength' => 10));
        $form->addElement('text', 'enddate', null,
                          array('size' => 10, 'maxlength' => 10));

        unset($options);
        $options = array('titlecheck'        => 'Title',
                         'authorcheck'       => 'Author(s)',
                         'categorycheck'     => 'Category',
                         'extracheck'        => 'Category Related Information',
                         'papercheck'        => 'Link to Paper',
                         'additionalcheck'   => 'Link to Additional Material',
                         'halfabstractcheck' => 'Short Abstract',
                         'venuecheck'        => 'Publication Venue',
                         'keywordscheck'     => 'Keywords',
                         'datecheck'         => 'Date Published');
        if ($_GET['expand']) {
            foreach ($options as $name => $text) {
                $form->addElement('advcheckbox', $name, null, $text, null,
                                  array('no', 'yes'));
            }
        }
        else {
            foreach ($options as $name => $text) {
                $form->addElement('hidden', $name, false);
            }
        }

        $form->addElement('submit', 'Submit', 'Search');
        $form->addElement('submit', 'Clear', 'Clear');

        if($_GET['expand'] == "true")
            $form->addElement('hidden', 'expand', 'true');
        else {
            $form->addElement('hidden', 'titlecheck', 'true');
            $form->addElement('hidden', 'authorcheck', 'true');
            $form->addElement('hidden', 'halfabstractcheck', 'true');
            $form->addElement('hidden', 'datecheck', 'true');
        }

        $this->form =& $form;
    }

    /**
     * Assigns the form's values as per the HTTP GET string.
     */
    function setFormValues() {
        $defaultValues['search']            = stripslashes($_GET['search']);
        $defaultValues['cat_id']            = $_GET['cat_id'];
        $defaultValues['title']             = $_GET['title'];
        $defaultValues['authortyped']       = stripslashes($_GET['authortyped']);
        $defaultValues['paper']             = $_GET['paper'];
        $defaultValues['abstract']          = $_GET['abstract'];
        $defaultValues['venue']             = $_GET['venue'];
        $defaultValues['keywords']          = $_GET['keywords'];
        $defaultValues['titlecheck']        = 'yes';
        $defaultValues['authorcheck']       = 'yes';
        $defaultValues['additionalcheck']   = 'yes';
        $defaultValues['halfabstractcheck'] = 'yes';
        $defaultValues['datecheck']         = 'yes';

        if (is_object($this->category) && is_array($this->category->info)) {
            foreach ($this->category->info as $info) {
                $defaultValues[strtolower($info->name)] = $_GET[$info->name];
            }
        }

        $this->form->setDefaults($defaultValues);
    }

    /**
     * Creates the table displaying the form fields.
     */
    function createTable(&$renderer) {
        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        $table->addRow(array('Search:',
                             $renderer->elementToHtml('search')
                             . ' ' . $renderer->elementToHtml('Quick')));

        // horizontal line
        $table->addRow(array('<hr/>'), array('colspan' => '2'));

        $table->addRow(array('<h3>Advanced Search</h3>'), array('colspan' => '2'));
        $table->addRow(array('<h4>Search within:</h4>'), array('colspan' => '2'));

        // Category
        $options = array('' => 'All Categories');
        foreach ($this->cat_list->list as $cat) {
            $options[$cat->cat_id] = $cat->category;
        }
        $table->addRow(array('Category:', $renderer->elementToHtml('cat_id')));

        // Title
        $table->addRow(array('Title:', $renderer->elementToHtml('title')));

        // Authors
        unset($options);
        $options = array('' => 'All Authors');
        foreach($this->auth_list->list as $auth) {
            $options[$auth->author_id] = $auth->name;
        }
        $table->addRow(array('Authors:',
                             $renderer->elementToHtml('authortyped')
                             . ' or select from list '
                             . $renderer->elementToHtml('authorselect')));

        $table->addRow(array('Paper Filename:',
                             $renderer->elementToHtml('paper')));
        $table->addRow(array('Abstract:',
                             $renderer->elementToHtml('abstract')));
        $table->addRow(array('Publication Venue:',
                             $renderer->elementToHtml('venue')));
        $table->addRow(array('Keywords:',
                             $renderer->elementToHtml('keywords')));

        if (is_object($this->category) && is_array($this->category->info)) {
            foreach ($this->category->info as $info) {
                $table->addRow(array($info->name . ':',
                                     $renderer->elementToHtml(
                                         strtolower($info->name))));
            }
        }

        // date published - uses jscal (http://sourceforge.net/projects/jscal/)
        // to enter dates.
        $table->addRow(array('Published between:',
                             $renderer->elementToHtml('startdate')
                             . '<a href="javascript:doNothing()" '
                             . 'onClick="setDateField(document.pubForm.startdate);'
                             . 'top.newWin=window.open(\'calendar.html\', \'cal\','
                             . '\'dependent=yes,width=230,height=250,screenX=200,'
                             . 'screenY=300,titlebar=yes\')">'
                             . '<img src="calendar.gif" border=0></a> (yyyy-mm-dd) '
                             . 'and '
                             . $renderer->elementToHtml('enddate')
                             . '<a href="javascript:doNothing()" '
                             . 'onClick="setDateField(document.pubForm.enddate);'
                             . 'top.newWin=window.open(\'calendar.html\', \'cal\','
                             . '\'dependent=yes,width=230,height=250,screenX=200,'
                             . 'screenY=300,titlebar=yes\')">'
                             . '<img src="calendar.gif" border=0></a> (yyyy-mm-dd) '
                           ));

        if ($_GET['expand']) {
            $table->addRow(array('<hr/>'), array('colspan' => '2'));

            $prefsTable = new HTML_Table();

            $prefsTable->addRow(array('<br/>'
                                      . $renderer->elementToHtml('titlecheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('authorcheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('categorycheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('extracheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('papercheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('additionalcheck'),
                                      $renderer->elementToHtml('halfabstractcheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('venuecheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('keywordscheck')
                                      . '<br/>'
                                      . $renderer->elementToHtml('datecheck')
                                    ));
            $prefsTable->updateRowAttributes($prefsTable->getRowCount() - 1,
                                             array('id' => 'middle'));
            $table->addRow(array('Search Preferences',
                                 'Show the following in search results:'
                                 . $prefsTable->toHtml()));
            $table->addRow(array('<hr/>'), array('colspan' => '2'));
        }
        else {
            $table->addRow(array('<a href="javascript:dataKeep(1);">'
                                 . 'Search Preferences</a>'),
                           array('colspan' => '2'));
        }

        $table->addRow(array('',
                             $renderer->elementToHtml('Submit')
                             . ' ' . $renderer->elementToHtml('Clear')));

        $table->updateColAttributes(0, array('id' => 'emph', 'width' => '25%'));

        $this->table =& $table;
    }
}

$page = new advanced_search();
echo $page->toHtml();

?>


