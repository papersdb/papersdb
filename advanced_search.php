<?php

  // $Id: advanced_search.php,v 1.2 2006/05/16 21:21:33 aicmltec Exp $

  /**
   * \file
   *
   * \brief Performs advanced searches on publication information in the
   * database.
   *
   * It is mainly only forms, with little data being read from the database. It
   * sends the users input to search_publication_db.php.
   */

include_once('functions.php');
include_once('check_login.php');
require_once('includes/pdCatList.php');
require_once('includes/pdAuthorList.php');
require_once('HTML/Form.php');
require_once('HTML/Table.php');

htmlHeader('Search Publication');

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

$cat_id = strval($_GET['cat_id']);
isValid($cat_id);

$db =& dbCreate();

$form = new HTML_Form('search_publication_db.php','post','pubForm',
                      null,'multipart/form-data');

$tableAttrs = array('width' => '100%',
                    'border' => '0',
                    'cellpadding' => '6',
                    'cellspacing' => '0');
$table = new HTML_Table($tableAttrs);
$table->setAutoGrow(true);

$table->addRow(array('Search:',
                     $form->returnText('search', stripslashes($search),
                                       50, 250)
                     . $form->returnSubmit('Search', 'Quick')));

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
$table->addRow(array('Category:',
                     $form->returnSelect('cat_id', $options, intval($cat_id),
                                         1, '', false,
                                         'onChange="dataKeep(0);"')));

// Title
$table->addRow(array('Title:', $form->returnText('title', $title, 60, 250)));

// Authors
$auth_list = new pdAuthorList();
$auth_list->dbLoad($db);
unset($options);
$options = array('' => 'All Authors');
foreach($auth_list->list as $auth) {
    $options[$auth->author_id] = $auth->name;
}
$table->addRow(array('Authors:',
                     $form->returnText('authortyped',
                                       stripslashes($authortyped), 18,
                                       250)
                     . ' or select from list '
                     . $form->returnSelect('authorselect', $options,
                                         null, 1, false)));

//
$table->addRow(array('Paper Filename:',
                     $form->returnText('paper', $paper, 60, 250)));
//
$table->addRow(array('Abstract:',
                     $form->returnText('abstract', $abstract, 60, 250)));
//
$table->addRow(array('Publication Venue:',
                     $form->returnText('venue', $venue, 60, 250)));
//
$table->addRow(array('Keywords:',
                     $form->returnText('keywords', $keywords, 60, 250)));

if ($cat_id) {
    $q = $db->select(array('info', 'category', 'cat_info'), 'info.name',
                     array('category.cat_id=cat_info.cat_id',
                           'info.info_id=cat_info.info_id',
                           'category.cat_id' => quote_smart($cat_id)));
    $r = $db->fetchObject($q);
    while ($r) {
        $varname = strtolower($r->name);
        if ($varname != "") {
            $varname = str_replace(" ", "", $varname);

            // This code copied from release version,
            // but there is no $category_id variable defined!
            //
            // If the user didn't enter anything into the form,
            // use the value we pulled from the database
            //if ($$varname == "") {
            //    $infoID = get_info_id($category_id, $info[$i]);
            //    $$varname = get_info_field_value($pub_id, $category_id,
            //                                     $infoID);
            //}

            $table->addRow(array($r->name . ':',
                                 $form->returnText($varname, $$varname,
                                                   60, 250)));
        }
        $r = $db->fetchObject($q);
    }
}

// date published
$table->addRow(array('Published between:',
                     $form->returnText('startdate', '', 10, 10)
                     . '<a href="javascript:doNothing()" '
                     . 'onClick="setDateField(document.pubForm.startdate);'
                     . 'top.newWin=window.open(\'calendar.html\', \'cal\','
                     . '\'dependent=yes,width=230,height=250,screenX=200,'
                     . 'screenY=300,titlebar=yes\')">'
                     . ' <img src="calendar.gif" border=0></a> (mm/dd/yyyy) '
                     . 'and '
                     . $form->returnText('enddate', '', 10, 10)
                     . '<a href="javascript:doNothing()" '
                     . 'onClick="setDateField(document.pubForm.enddate);'
                     . 'top.newWin=window.open(\'calendar.html\', \'cal\','
                     . '\'dependent=yes,width=230,height=250,screenX=200,'
                     . 'screenY=300,titlebar=yes\')">'
                     . ' <img src="calendar.gif" border=0></a> (mm/dd/yyyy) '));

if ($_GET['expand']) {
    $table->addRow(array('<hr/>'), array('colspan' => '2'));

    $prefsTable = new HTML_Table();

    $prefsTable->addRow(array('<br/>'
                              . $form->returnCheckbox('titlecheck', true)
                              . 'Title'
                              . '<br/>'
                              . $form->returnCheckbox('authorcheck', true)
                              . 'Author'
                              . '<br/>'
                              . $form->returnCheckbox('categorycheck', false)
                              . 'Category'
                              . '<br/>'
                              . $form->returnCheckbox('extracheck', false)
                              . 'Category Related Information'
                              . '<br/>'
                              . $form->returnCheckbox('papercheck', false)
                              . 'Link to Paper'
                              . '<br/>'
                              . $form->returnCheckbox('additionalcheck', true)
                              . 'Link to Additional Material',
                              $form->returnCheckbox('halfabstractcheck', true)
                              . 'Short Abstract'
                              . '<br/>'
                              . $form->returnCheckbox('venuecheck', false)
                              . 'Publication Venue'
                              . '<br/>'
                              . $form->returnCheckbox('keywordscheck', false)
                              . 'Keywords'
                              . '<br/>'
                              . $form->returnCheckbox('datecheck', true)
                              . 'Date Published'
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
                     $form->returnSubmit('Search', 'Submit')
                     . $form->returnSubmit('Clear', 'Clear'), ''));

$table->updateColAttributes(0, array('width' => '25%'));

pageHeader();
navigationMenu();

print "<div id='content'>\n"
. "<a name='Start'></a>\n";

print $form->returnStart(true);

if ($_GET['edit'])
    print $form->retrunHidden('pub_id', $pub_id);
if($_GET['expand'] == "true")
    print $form->returnHidden('expand', 'true');
if($logged_in == "true")
    print $form->returnHidden('admin', 'true');

if (!$_GET['expand']) {
    print $form->returnHidden('titlecheck', 'true');
    print $form->returnHidden('authorcheck', 'true');
    print $form->returnHidden('halfabstractcheck', 'true');
    print $form->returnHidden('datecheck', 'true');
}

print "<h2><b><u>Search</u></b></h2>\n";
print $table->toHtml();
print $form->returnEnd();

?>

</div>
</body>
</html>

