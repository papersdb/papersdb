<?php ;

// $Id: list_venues.php,v 1.1 2006/06/08 22:44:42 aicmltec Exp $

/**
 * \file
 *
 * \brief This page displays all venues.
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

htmlHeader('all_venues', 'Add or Edit Venue');
$db =& dbCreate();

echo "<body>\n";
pageHeader();
navMenu('all_venues');
echo "<div id='content'>\n";

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
        . ucfirst($venue->type) . '</b>:&nbsp;';
    if ($venue->url != '')
        $cell1 .= '<a href="' . $venue->url . '" target="_blank">';
    $cell1 .= $venue->name;
    if ($venue->url != '')
        $cell1 .= '</a>';
    if ($venue->data != '') {
        $cell1 .= '<br/>';
        if($venue->type == 'conference')
            $cell1 .= '<b>Location:&nbsp;</b>';
        else if($venue->type == 'journal')
            $cell1 .= '<b>Publisher:&nbsp;</b>';
        else if($venue->type == 'workshop')
            $cell1 .= '<b>Associated Conference:&nbsp;</b>';
        $cell1 .= $venue->data;
    }
    if ($venue->editor != '')
        $cell1 .= "<br><b>Editor:&nbsp;</b>" . $venue->editor;

    if ($logged_in) {
        $cell2 = '<a href="add_venue.php?status=change&venue_id='
            . $venue->venue_id . '">Edit</a><br/>'
            . '<a href="delete_venue.php?confirm=check&venue_id='
            . $venue->venue_id . '">Delete</a>';
    }
    else {
        $cell2 = '';
    }

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

echo '</div>';
pageFooter();
echo "</body>\n</html>\n";
$db->close();

?>
