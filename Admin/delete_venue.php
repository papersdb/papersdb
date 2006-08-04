<?php ;

// $Id: delete_venue.php,v 1.6 2006/08/04 18:00:33 aicmltec Exp $

/**
 * \file
 *
 * \brief This page confirms that the user would like to delete the selected
 * venue, and then removes it from the database.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';


/**
 * Renders the whole page.
 */
class delete_venue extends pdHtmlPage {
    function delete_venue() {
        global $access_level;

        parent::pdHtmlPage('delete_category');

        if ($access_level <= 0) {
            $this->loginError = true;
            return;
        }

        if (isset($_GET['venue_id']) && ($_GET['venue_id'] != ''))
            $venue_id = intval($_GET['venue_id']);
        else if (isset($_POST['venue_id']) && ($_POST['venue_id'] != ''))
            $venue_id = intval($_POST['venue_id']);
        else {
            $this->contentPre .= 'No venue id defined';
            $this->pageError = true;
            return;
        }

        $db =& dbCreate();

        $venue = new pdVenue();
        $result = $venue->dbLoad($db, $venue_id);
        if (!$result) {
            $this->pageError = true;
            $db->close();
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'venue_id', $venue->venue_id);

        $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
        $form->accept($renderer);

        if ($form->validate()) {
            $venue->dbDelete($db);

            $this->contentPre .= 'Venue <b>' . $venue->title
                . '</b> successfully removed from database.';
        }
        else {
            $this->contentPre .= '<h3>Confirm</h3><p/>'
                . 'Delete Venue <b>' . $venue->title
                . '</b> from the database?';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }

        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new delete_venue();
echo $page->toHtml();

?>