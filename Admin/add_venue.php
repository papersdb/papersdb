<?php ;

// $Id: add_venue.php,v 1.2 2006/06/06 21:11:12 aicmltec Exp $

/**
 * \file
 *
 * \brief This page, displays, edits and adds venues.
 *
 * Depending on a the varaible passed is what segment of the page is used.  If
 * $status == view then, it displays a list of all the venues and some
 * information about them. It links to whether you would like to add a new
 * venue, or edit/delete an existing one.
 *
 * If there is no variable passed, it displays the form to add a new venue.
 *
 * If $status == change, then it displays the same form, but with the values
 * already filled in, as the user is editing a venue.
 *
 * If $submit == true then it takes the input it passed itself and adds it to
 * the database. If it is passed a venue_id then it replaces the information,
 * if it is not passed a venue_id, it adds a new id to the database and puts
 * the information there.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/Table.php';

htmlHeader('add_venue', 'Add or Edit Publication');

if (!$logged_in) {
    echo '<body>';
    pageHeader();
    echo "<div id='content'>\n";
    loginErrorMessage();
}

$db =& dbCreate();

if ($_GET['submit'] == 'true') {
    $venue = new pdVenue();
    $venue->load($_POST);

    //add http:// to webpage address if needed
    if (strpos($venue->url, 'http') === false) {
        $venue->url = "http://" . $venue->url;
    }
    $venue->title = str_replace("\"","'", $venue->title);

    if(!isset($venue->venue_id) || ($venue->venue_id == '')) {
        $venue->dbSaveNew($db);

        echo '<body onLoad="window.opener.location.reload(); window.close();">';
        pageHeader();
        navMenu('add_venue');
        echo "<div id='content'>\n";

        echo 'You have successfully added the venue "' .  $venue->title . '".';
        echo '<br><a href="./add_venue.php">Add another venue</a>';
    }
    else {
        $venue->dbSave($db);
        echo '<body>';
        pageHeader();
        navMenu('add_venue');
        echo "<div id='content'>\n";
        echo 'You have successfully edited the venue "' . $venue->title . '".';
        echo '<br><a href="./add_venue.php?status=edit">Edit another venue</a>';
    }
    exit();
}
?>

<script language="JavaScript" src="../calendar.js"></script>
<script language="JavaScript" type="text/JavaScript">

    function verify() {
	if (document.forms["venueForm"].elements["title"].value == "") {
		alert("Please enter a title for this venue.");
		return false;
	}

	if (document.forms["venueForm"].elements["name"].value == "") {
		alert("Please enter information for this venue.");
		return false;
	}

	return true;
}
function closewindow(){ window.close();}
function dataKeep() {
	var temp_qs = "";
	var info_counter = 0;
	var is_edit = 0;

	for (i = 0; i < document.forms["venueForm"].elements.length; i++) {
        var element = document.forms["venueForm"].elements[i];
		if ((element.type != "submit") && (element.type != "reset")
            && (element.value != "") && (element.value != null)) {

			if(element.name == "venue_id")
				is_edit = 1;

			if(element.name == "type"){
                if(element.checked == true) {
                    if (info_counter > 0) {
                        temp_qs = temp_qs + "&";
                    }
                    temp_qs = temp_qs + element.name + "="
                        + element.value.replace("\"","'");
                    info_counter++;
                }
			}
			else {
                if (info_counter > 0) {
                    temp_qs = temp_qs + "&";
                }
				temp_qs = temp_qs + element.name + "=" + element.value;
                info_counter++;
            }
		}
	}
	temp_qs = temp_qs.replace("\"", "?");
	temp_qs = temp_qs.replace(" ", "%20");
	if(is_edit == 1)
		location.href = "http://"
            + "<? echo $_SERVER["HTTP_HOST"]; echo $_SERVER['PHP_SELF']; ?>"
            + "?status=change&" + temp_qs;
	else
		location.href = "http://"
            + "<? echo $_SERVER["HTTP_HOST"]; echo $_SERVER['PHP_SELF']; ?>"
            + "?" + temp_qs;


}
</script>

<?

echo "<body>\n";

if (!isset($_GET['popup']) || ($_GET['popup'] != 'true')) {
    pageHeader();
    navMenu('add_venue');
    echo "<div id='content'>\n";
}

if($_GET['status'] == 'view') {
    $venue_list = new pdVenueList();
    $venue_list->dbLoad($db);
    $venue = new pdVenue();

    $tableAttrs = array('width' => '100%',
                        'border' => '0',
                        'cellpadding' => '6',
                        'cellspacing' => '0');
    $table = new HTML_Table($tableAttrs);
    $table->setAutoGrow(true);

    foreach ($venue_list->list as $v) {
        $venue->dbLoad($db, $v->venue_id);
        $cell1 = '<b>' . $venue->title . '</b><br/><b>'
            . $venue->type . '</b>:&nbsp;';
        if ($venue->url != '')
            $cell1 .= '<a href="' . $venue->url . '" target="_blank">';
        $cell1 .= $venue->name;
        if ($venue->url != '')
            $cell1 .= '</a>';
        if ($venue->data != '') {
            $cell1 .= '<br/>';
            if($venue->type == 'Conference')
                $cell1 .= '<b>Location:&nbsp;</b>';
            else if($venue->type == 'Journal')
                $cell1 .= '<b>Publisher:&nbsp;</b>';
            else if($venue->type == 'Workshop')
                $cell1 .= '<b>Associated Conference:&nbsp;</b>';
            $cell1 .= $venue->data;
        }
        if ($venue->editor != '')
            $cell1 .= "<br><b>Editor:&nbsp;</b>" . $venue->editor;

        $cell2 = '<a href="add_venue.php?status=change&venue_id='
            . $venue->venue_id . '">Edit</a><br/>'
            . '<a href="delete_venue.php?confirm=check&venue_id='
            . $venue->venue_id . '">Delete</a>';

        $table->addRow(array($cell1, $cell2));
	}

    /* now assign table attributes including highlighting for even and odd
     * rows */
    for ($i = 0; $i < $table->getRowCount(); $i++) {
        $table->updateCellAttributes($i, 0, array('class' => 'standard'));

        if ($i & 1) {
            $table->updateRowAttributes($i, array('class' => 'even'), true);
        }
        else {
            $table->updateRowAttributes($i, array('class' => 'odd'), true);
        }

        if ($logged_in) {
            $table->updateCellAttributes($i, 1, array('id' => 'emph',
                                                      'class' => 'small'));
            $table->updateCellAttributes($i, 2, array('id' => 'emph',
                                                      'class' => 'small'));
        }
    }

    echo '<h2><b><u>Publication Venues</u></b></h2>';
    echo $table->toHtml();
}
else {
    $venue = new pdVenue();
    if (isset($_GET['type']))
        $venue->type = $_GET['type'];

    if($_GET['status'] == "change")
        $venue->dbLoad($db, $_GET['venue_id']);

    $form = new HTML_QuickForm('venueForm', 'post',
                               './add_venue.php?submit=true');

    if(($_GET['status'] == "change")||($_GET['editmode'] == "true")) {
        $form->addElement('hidden', 'editmode', 'true');
        $form->addElement('hidden', 'venue_id', $_GET['venue_id']);
    }
    else {
        if($_GET['popup'] == 'false')
            $form->addElement('hidden', 'popup', 'false');
    }

    $form->addElement('radio', 'type', null, 'Journal', 'journal',
                      array('onClick' => 'javascript:dataKeep();'));
    $form->addElement('radio', 'type', null, 'Conference', 'conference',
                      array('onClick' => 'javascript:dataKeep();'));
    $form->addElement('radio', 'type', null, 'Workshop', 'workshop',
                      array('onClick' => 'javascript:dataKeep();'));
    $form->addElement('text', 'title', null,
                      array('size' => 50, 'maxlength' => 250));
    $form->addElement('text', 'name', null,
                      array('size' => 50, 'maxlength' => 250));
    $form->addElement('text', 'url', null,
                      array('size' => 50, 'maxlength' => 250));
    if (isset($venue) && ($venue->type != '')) {
        $form->addElement('text', 'data', null,
                          array('size' => 50, 'maxlength' => 250));
        if ($venue->type == 'workshop')
            $form->addElement('text', 'editor', null,
                              array('size' => 50, 'maxlength' => 250));
        if (($venue->type == 'conference') || ($venue->type == 'workshop'))
            $form->addElement('text', 'date', null,
                              array('size' => 10, 'maxlength' => 10));
    }

    if(($_GET['status'] == "change")||($_GET['editmode'] == "true")) {
        $form->addElement('hidden', 'id', 'true');
        $form->addElement('submit', 'Submit', 'Edit Venue',
                          array('onClick' => 'reurn verify();'));
    }
    else {
        $form->addElement('submit', 'Submit', 'Add Venue',
                          array('onClick' => 'reurn verify();'));
    }

    $form->addElement('reset', 'Reset', 'Reset');


    if($_GET['popup'] == 'false')
        $form->addElement('submit', 'Cancel', 'Cancel',
                          array('onClick' => 'history.back();'));
    else
        $form->addElement('submit', 'Cancel', 'Cancel',
                          array('onClick' => 'closewindow();'));

    if (isset($venue) && ($venue->venue_id != '')) {
        $defaults['venue_id'] = $venue->venue_id;
        $defaults['title']    = $venue->title;
        $defaults['name']     = $venue->name;
        $defaults['url']      = $venue->url;
        $defaults['type']     = $venue->type;
        $defaults['data']     = $venue->data;
        $defaults['editor']   = $venue->editor;
        $defaults['date']     = $venue->date;
        $form->setDefaults($defaults);
    }
    $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
    $form->accept($renderer);

    $table = new HTML_Table(array('width' => '100%',
                                  'border' => '0',
                                  'cellpadding' => '6',
                                  'cellspacing' => '0'));
    $table->setAutoGrow(true);

    $table->addRow(array('Type:',
                         $renderer->elementToHtml('type', 'journal')));
    $table->addRow(array('',
                         $renderer->elementToHtml('type', 'conference')));
    $table->addRow(array('',
                         $renderer->elementToHtml('type', 'workshop')));
    $table->addRow(array('Internal Title:',
                         $renderer->elementToHtml('title')));
    $table->addRow(array('Venue Name:',
                         $renderer->elementToHtml('name')));
    $table->addRow(array('Venue URL:',
                         $renderer->elementToHtml('url')));

    if (isset($venue) && ($venue->type != '')) {
        if ($venue->type == 'conference')
            $cell1 = 'Location';
        else if ($venue->type == 'journal')
            $cell1 = 'Publisher';
        else if ($venue->type == 'workshop')
            $cell1 = 'Associated Conference';
        $table->addRow(array($cell1 . ':',
                             $renderer->elementToHtml('data')));

        if ($venue->type == 'workshop')
            $table->addRow(array('Editor:',
                                 $renderer->elementToHtml('editor')));


        if (($venue->type == 'conference') || ($venue->type == 'workshop'))
            $table->addRow(array('Date:',
                                 $renderer->elementToHtml('date')
                                 . '<a href="javascript:doNothing()" '
                                 . 'onClick="setDateField('
                                 . 'document.venueForm.venue_date);'
                                 . 'top.newWin=window.open(\'../calendar.html\','
                                 . '\'cal\',\'dependent=yes,width=230,height=250,'
                                 . 'screenX=200,screenY=300,titlebar=yes\')">'
                                 . '<img src="../calendar.gif" border=0></a> '
                                 . '(yyyy-mm-dd) '));
    }


    if(($_GET['status'] == "change")||($_GET['editmode'] == "true"))
        echo '<h3>Edit Venue</h3>';
    else
        echo '<h3>Add Venue</h3>';
    echo $renderer->toHtml(($table->toHtml())) . '</div>';

}

if (!isset($_GET['popup']) || ($_GET['popup'] != 'true')) {
    echo '</div>';
}

pageFooter();
echo "</body>\n</html>\n";
$db->close();

?>
