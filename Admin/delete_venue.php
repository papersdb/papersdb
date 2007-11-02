<?php ;

// $Id: delete_venue.php,v 1.27 2007/11/02 16:36:28 loyola Exp $

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
require_once 'includes/pdVenue.php';
require_once 'includes/pdPublication.php';


/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class delete_venue extends pdHtmlPage {
    public $venue_id;

    public function __construct() {
        parent::__construct('delete_venue', 'Delete Venue',
                           'Admin/delete_venue.php');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (!isset($this->venue_id) || !is_numeric($this->venue_id)) {
            $this->pageError = true;
            return;
        }

        $venue = new pdVenue();
        $result = $venue->dbLoad($this->db, $this->venue_id);
        if (!$result) {
            $this->pageError = true;
            return;
        }

        $pub_list = new pdPubList($this->db,
                                  array('venue_id' => $this->venue_id));

        if (isset($pub_list->list) && (count($pub_list->list) > 0)) {
            echo 'Cannot delete venue <b>', $venue->nameGet(), '</b>.<p/>', 
            	'The venue is used by the following ', 'publications:', "\n", 
            	$this->displayPubList($pub_list);
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'venue_id', $venue->venue_id);

        if ($form->validate()) {
            $venue->dbDelete($this->db);

            echo 'Venue <b>', $venue->title, '</b> successfully removed from database.';
        }
        else {
            $renderer =& $form->defaultRenderer();
            $form->accept($renderer);

            if ($venue->title != '')
                $disp_name = $venue->title;
            else
                $disp_name = $venue->nameGet();

            echo '<h3>Confirm</h3><p/>', 'Delete Venue <b>', $disp_name, 
            	'</b> from the database?';

            $this->form =& $form;
            $this->renderer =& $renderer;
        }
    }
}

$page = new delete_venue();
echo $page->toHtml();

?>