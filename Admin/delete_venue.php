<?php ;

// $Id: delete_venue.php,v 1.16 2007/03/12 05:25:45 loyola Exp $

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

        if ($this->access_level <= 0) {
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

        $venue = new pdVenue();
        $result = $venue->dbLoad($this->db, $venue_id);
        if (!$result) {
            $this->pageError = true;
            $this->db->close();
            return;
        }

        $q = $this->db->select('publication', 'pub_id',
                               array('venue_id' => $venue_id),
                               "delete_venue::delete_venue");

        if ($this->db->numRows($q) > 0) {
            $this->contentPre .= 'Cannot delete venue <b>'
                . $venue->nameGet() . '</b>.<p/>'
                . 'The venue is used by the following publications:' . "\n"
                . '<ul>';

            $r = $this->db->fetchObject($q);
            while ($r) {
                $pub = new pdPublication();
                $pub->dbLoad($this->db, $r->pub_id);
                $this->contentPre
                    .= '<li>' . $pub->getCitationHtml()
                    . '&nbsp;<a href="../view_publication.php?pub_id=' . $pub->pub_id
                    . '"><img src="../images/viewmag.png" title="view" alt="view" '
                    . 'height="16" width="16" border="0" align="middle" /></a>'
                    . '&nbsp;<a href="add_pub1.php?pub_id=' . $pub->pub_id . '">'
                    . '<img src="../images/pencil.png" title="edit" alt="edit" '
                    . 'height="16" width="16" border="0" align="middle" /></a>'
                    . '</li>';
                $r = $this->db->fetchObject($q);
            }
            $this->contentPre .= '</ul>';
            $this->db->close();
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'venue_id', $venue->venue_id);

        if ($form->validate()) {
            $venue->dbDelete($this->db);

            $this->contentPre .= 'Venue <b>' . $venue->title
                . '</b> successfully removed from database.';
        }
        else {
            $renderer =& $form->defaultRenderer();
            $form->accept($renderer);

            if ($venue->title != '')
                $disp_name = $venue->title;
            else
                $disp_name = $venue->nameGet();

            $this->contentPre .= '<h3>Confirm</h3><p/>'
                . 'Delete Venue <b>' . $disp_name
                . '</b> from the database?';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }

        $this->db->close();
    }
}

$page = new delete_venue();
echo $page->toHtml();

?>