<?php ;

// $Id: advanced_search.php,v 1.12 2006/05/19 22:05:25 aicmltec Exp $

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

ini_set("include_path", ini_get("include_path") . ":.");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdAuthorList.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/Table.php';

global $additionalInfo;

makePage();

/**
 * Generates all the HTML for the page.
 */
function makePage() {
    $cat_id = strval($_GET['cat_id']);
    isValid($cat_id);

$db =& dbCreate();

    $form = new HTML_QuickForm('pubForm', 'post', 'search_publication_db.php',
                               '_self', 'multipart/form-data');
    // get our render
    $renderer =& new HTML_QuickForm_Renderer_QuickHtml();

    $additionalInfo = additionalInfoGet($db, $cat_id);
    createFormElements($form, $db);
    setFormValues($form);

    // Do the magic of creating the form.  NOTE: order is important here: this
    // must be called after creating the form elements, but before rendering
    // them.
    $form->accept($renderer);
    $table = createTable($db, $renderer);

    htmlHeader('Search Publication');
    printJavascript();
    pageHeader();
    navigationMenu();

    print "<div id='content'>\n"
        . "<h2><b><u>Search</u></b></h2>\n";

    $data = '';
    if($_GET['expand'] == "true") {
        $data .= $renderer->elementToHtml('expand') . "\n";
    }
    else {
        $data .= $renderer->elementToHtml('titlecheck') . "\n"
            . $renderer->elementToHtml('authorcheck') . "\n"
            . $renderer->elementToHtml('halfabstractcheck') . "\n"
            . $renderer->elementToHtml('datecheck') . "\n";
    }

    // Wrap the form and any remaining elements (i.e. hidden elements) into the
    // form tags.
    print $renderer->toHtml($data . $table->toHtml()) . "</div>";

    $db->close();

    pageFooter();

    echo "</body>\n</html>\n";
}

/**
 * Outputs the java script used by the page.
 */
function printJavascript() {
    echo <<<END

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
        var form = document.forms["pubForm"];

        for (i = 0; i < form.elements.length; i++) {
            if ((form.elements[i].value != "")
                && (form.elements[i].value != null)) {
                if (info_counter > 0) {
                    temp_qs = temp_qs + "&";
                }

                temp_qs = temp_qs + form.elements[i].name + "="
                    + form.elements[i].value;

                info_counter++;
            }
        }
        if(num == 1) {
            temp_qs = temp_qs + "&expand=true";
        }
        temp_qs = temp_qs.replace("\"", "?");
        temp_qs = temp_qs.replace(" ", "%20");
        location.href = "http://"

END;

        echo "+ \"" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] ."?\""
            . "+ temp_qs;";


        echo <<<END

            }
    </script>
          <body>

END;
}

/**
 * Retrieves the additional information for the selected category.
 */
function additionalInfoGet(&$db, $cat_id) {
    $q = $db->select(array('info', 'category', 'cat_info'), 'info.name',
                     array('category.cat_id=cat_info.cat_id',
                           'info.info_id=cat_info.info_id',
                           'category.cat_id' => quote_smart($cat_id)));
    $r = $db->fetchObject($q);
    while ($r) {
        $varname = $r->name;
        if ($varname != "") {
            $varname = str_replace(" ", "", $varname);

            if (isset($_GET[strtolower($varname)]))
                $additionalInfo[$varname] = $_GET[strtolower($varname)];
            else
                $additionalInfo[$varname] = '';
        }
        $r = $db->fetchObject($q);
    }

    return $additionalInfo;
}

/**
 * Creates the from used on this page. The renderer is then used to
 * display the form correctly on the page (see createTable).
 *
 * Note: calendar.js is used as a shorcut way of entering date values.
 */
function createFormElements(&$form, &$db) {
    global $additionalInfo;

    $form->addElement('text', 'search', null,
                      array('size' => 50, 'maxlength' => 250));
    $form->addElement('submit', 'Quick', 'Search');

    $cat_list = new pdCatList();
    $cat_list->dbLoad($db);

    $options = array('' => 'All Categories');
    foreach ($cat_list->list as $cat) {
        $options[$cat->cat_id] = $cat->category;
    }
    $form->addElement('select', 'cat_id', null, $options,
                      array('onChange' => 'dataKeep(0);'));
    $form->addElement('text', 'title', null,
                      array('size' => 60, 'maxlength' => 250));
    $form->addElement('text', 'authortyped', null,
                      array('size' => 20, 'maxlength' => 250));

    $auth_list = new pdAuthorList();
    $auth_list->dbLoad($db);
    unset($options);
    $options = array('' => 'All Authors');
    foreach($auth_list->list as $auth) {
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

    if ($_GET['cat_id'] && is_array($additionalInfo)) {
        foreach ($additionalInfo as $name => $value) {
            $form->addElement('text', strtolower($name), null,
                              array('size' => 60, 'maxlength' => 250));
        }
    }

    $form->addElement('text', 'startdate', null,
                      array('size' => 10, 'maxlength' => 10));
    $form->addElement('text', 'enddate', null,
                      array('size' => 10, 'maxlength' => 10));

    if ($_GET['expand']) {
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

        foreach ($options as $name => $text) {
            $form->addElement('advcheckbox', $name, null, $text, null,
                              array('no', 'yes'));
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
}

/**
 * Assigns the form's values as per the HTTP GET string.
 */
function setFormValues(&$form) {
    global $additionalInfo;

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


    if ($_GET['cat_id'] && is_array($additionalInfo)) {
        foreach ($additionalInfo as $name => $value) {
            $defaultValues[strtolower($name)] = $value;
        }
    }

    $form->setDefaults($defaultValues);
}

/**
 * Creates the table displaying the form fields.
 */
function createTable(&$db, &$renderer) {
    global $additionalInfo;

    $tableAttrs = array('width' => '100%',
                        'border' => '0',
                        'cellpadding' => '6',
                        'cellspacing' => '0');
    $table = new HTML_Table($tableAttrs);
    $table->setAutoGrow(true);

    $table->addRow(array('Search:',
                         $renderer->elementToHtml('search')
                         . ' ' . $renderer->elementToHtml('Quick')));

    // horizontal line
    $table->addRow(array('<hr/>'), array('colspan' => '2'));

    $table->addRow(array('<h3>Advanced Search</h3>'), array('colspan' => '2'));
    $table->addRow(array('<h4>Search within:</h4>'), array('colspan' => '2'));

    // Category
    $cat_list = new pdCatList();
    $cat_list->dbLoad($db);

    $options = array('' => 'All Categories');
    foreach ($cat_list->list as $cat) {
        $options[$cat->cat_id] = $cat->category;
    }
    $table->addRow(array('Category:', $renderer->elementToHtml('cat_id')));

    // Title
    $table->addRow(array('Title:', $renderer->elementToHtml('title')));

    // Authors
    $auth_list = new pdAuthorList();
    $auth_list->dbLoad($db);
    unset($options);
    $options = array('' => 'All Authors');
    foreach($auth_list->list as $auth) {
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

    if ($_GET['cat_id'] && is_array($additionalInfo)) {
        foreach ($additionalInfo as $name => $value) {
            $table->addRow(array($name . ':',
                                 $renderer->elementToHtml(strtolower($name))));
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

    $table->updateColAttributes(0, array('width' => '25%'));

    return $table;
}

?>


