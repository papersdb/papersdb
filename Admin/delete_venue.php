<?php ;

// $Id: delete_venue.php,v 1.3 2006/06/07 23:15:47 aicmltec Exp $

/**
 * \file
 *
 * \brief This page confirms that the user would like to delete the selected
 * venue, and then removes it from the database.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pageConfig.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';

require_once 'HTML/QuickForm.php';

htmlHeader('delete_venue', 'Delete a Venue');
pageHeader();
navMenu('delete_venue');
echo '<body>';
echo "<div id='content'>\n";

if (!$logged_in) {
    loginErrorMessage();
}

$db =& dbCreate();

if (($_GET['confirm'] == 'yes') || ($_GET['confirm'] == 'check')) {
    // get the venue id from POST or GET array
    if (isset($_POST['venue_id']) && ($_POST['venue_id'] != ''))
        $venue_id = intval($_POST['venue_id']);
    if (!isset($venue_id) && isset($_GET['venue_id'])
        && ($_GET['venue_id'] != ''))
        $venue_id = intval($_GET['venue_id']);

    if (!isset($venue_id))
        errorMessage();

    $venue = new pdVenue();
    $venue->dbLoad($db, $venue_id);
}

if ($_GET['confirm'] == 'yes') {
    $venue->dbDelete($db);

    echo 'You have successfully removed the following venue from the '
        . 'database: <br/><b>' . $venue->title . '</b>';
}
else if ($_GET['confirm'] == 'check')	{
    echo '<br/><h3>Are you sure you want to delete the venue titled:<br/>'
        . '<b>' . $venue->title . "</b>?</h3><br/>";

    $form = new HTML_QuickForm('delete', 'post',
                               './delete_venue.php?confirm=yes', '_self',
                               'multipart/form-data');
    $form->addElement('submit', 'Submit', 'Yes');
    $form->addElement('submit', 'Cancel', 'Cancel',
                      array('onClick' => 'history.back();'));
    $form->addElement('hidden', 'venue_id', $venue->venue_id);
    $form->display();
}
else {
    $form = new HTML_QuickForm('deleter', 'post',
                               './delete_venue.php?confirm=check', '_self',
                               'multipart/form-data');

    $venue_list = new pdVenueList();
    $venue_list->dbLoad($db);
    assert('is_array($venue_list->list)');
    foreach ($venue_list->list as $v) {
        $options[$v->venue_id] = $v->title;
    }
    $form->addElement('select', 'venue_id', 'Select a venue to delete:',
                      $options, array('onChange' => "dataKeep();"));

    $form->addElement('submit', 'Submit', 'Delete');
    $form->addElement('submit', 'Cancel', 'Cancel',
                      array('onClick' => 'history.back();'));

    $form->display();
}

echo '</div>';
pageFooter();
$db->close();

?>