<?php ;

// $Id: delete_venue.php,v 1.12 2006/09/25 19:59:09 aicmltec Exp $

/**
 * This page confirms that the user would like to delete the selected
 * venue, and then removes it from the database.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';
require_once 'includes/pdPublication.php';


/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_venue extends pdHtmlPage {
    function delete_venue() {
        global $access_level;

        pubSessionInit();
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

        $q = $db->select('publication', 'pub_id',
                         array('venue_id' => $venue_id),
                         "delete_venue::delete_venue");

        if ($db->numRows($q) > 0) {
            $this->contentPre .= 'Cannot delete venue <b>'
                . $venue->name . '</b>.<p/>'
                . 'The venue is used by the following '
                . 'publications:' . "\n"
                . '<ul>';

            $r = $db->fetchObject($q);
            while ($r) {
                $pub = new pdPublication();
                $pub->dbLoad($db, $r->pub_id);
                $this->contentPre
                    .= '<li>' . $pub->getCitationHtml() . '</li>';
                $r = $db->fetchObject($q);
            }
            $this->contentPre .= '</ul>';
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
            if ($venue->title != '')
                $disp_name = $venue->title;
            else
                $disp_name = $venue->name;

            $this->contentPre .= '<h3>Confirm</h3><p/>'
                . 'Delete Venue <b>' . $disp_name
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