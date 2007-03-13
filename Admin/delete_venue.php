<?php ;

// $Id: delete_venue.php,v 1.19 2007/03/13 22:06:11 aicmltec Exp $

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
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('delete_venue');

        if ($this->loginError) return;

        if (isset($_GET['venue_id']) && ($_GET['venue_id'] != ''))
            $venue_id = intval($_GET['venue_id']);
        else if (isset($_POST['venue_id']) && ($_POST['venue_id'] != ''))
            $venue_id = intval($_POST['venue_id']);
        else {
            echo 'No venue id defined';
            $this->pageError = true;
            return;
        }

        $venue = new pdVenue();
        $result = $venue->dbLoad($this->db, $venue_id);
        if (!$result) {
            $this->pageError = true;
            return;
        }

        $q = $this->db->select('publication', 'pub_id',
                               array('venue_id' => $venue_id),
                               "delete_venue::delete_venue");

        if ($this->db->numRows($q) > 0) {
            echo 'Cannot delete venue <b>'
                . $venue->nameGet() . '</b>.<p/>'
                . 'The venue is used by the following publications:<p/>' . "\n";

            $r = $this->db->fetchObject($q);
            while ($r) {
                $pub = new pdPublication();
                $pub->dbLoad($this->db, $r->pub_id);
                echo $pub->getCitationHtml()
                    . '&nbsp;' . $this->getPubIcons($pub, 0xe) . '<p/>';
                $r = $this->db->fetchObject($q);
            }
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'venue_id', $venue->venue_id);

        if ($form->validate()) {
            $venue->dbDelete($this->db);

            echo 'Venue <b>' . $venue->title
                . '</b> successfully removed from database.';
        }
        else {
            $renderer =& $form->defaultRenderer();
            $form->accept($renderer);

            if ($venue->title != '')
                $disp_name = $venue->title;
            else
                $disp_name = $venue->nameGet();

            echo '<h3>Confirm</h3><p/>'
                . 'Delete Venue <b>' . $disp_name
                . '</b> from the database?';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }
    }
}

$page = new delete_venue();
echo $page->toHtml();

?>