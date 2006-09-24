<?php ;

// $Id: list_venues.php,v 1.10 2006/09/24 21:21:42 aicmltec Exp $

/**
 * This page displays all venues.
 *
 * @package PapersDB
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class list_venues extends pdHtmlPage {
    function list_venues() {
        global $access_level;

        pubSessionInit();
        parent::pdHtmlPage('all_venues');
        $db =& dbCreate();

        $venue_list = new pdVenueList($db);
        $venue = new pdVenue();

        $this->table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table =& $this->table;
        $table->setAutoGrow(true);

        foreach (array_keys($venue_list->list) as $venue_id) {
            unset($cells);
            $venue->dbLoad($db, $venue_id);
            $text = '<b>' . $venue->title . '</b><br/><b>'
                . ucfirst($venue->type) . '</b>:&nbsp;';
            if ($venue->url != '')
                $text .= '<a href="' . $venue->url . '" target="_blank">';
            $text .= $venue->name;
            if ($venue->url != '')
                $text .= '</a>';
            if ($venue->data != '') {
                $text .= '<br/>';
                if($venue->type == 'conference')
                    $text .= '<b>Location:&nbsp;</b>';
                else if($venue->type == 'journal')
                    $text .= '<b>Publisher:&nbsp;</b>';
                else if($venue->type == 'workshop')
                    $text .= '<b>Associated Conference:&nbsp;</b>';
                $text .= $venue->data;
            }
            if ($venue->editor != '')
                $text .= "<br><b>Editor:&nbsp;</b>" . $venue->editor;

            $cells[] = $text;

            if ($access_level > 0) {
                $cells[] = '<a href="Admin/add_venue.php?venue_id='
                    . $venue->venue_id . '">'
                    . '<img src="images/pencil.png" title="edit" alt="edit" '
                    . 'height="16" width="16" border="0" align="middle" /></a>';
                $cells[] = '<a href="Admin/delete_venue.php?venue_id='
                    . $venue->venue_id . '">'
                    . '<img src="images/kill.png" title="delete" alt="delete" '
                    . 'height="16" width="16" border="0" align="middle" /></a>';
            }

            $table->addRow($cells);
        }

        // now assign table attributes including highlighting for even and odd
        // rows
        for ($i = 0; $i < $table->getRowCount(); $i++) {
            $table->updateCellAttributes($i, 0, array('class' => 'standard'));

            if ($i & 1) {
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            }
            else {
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
            }

            if ($access_level > 0) {
                $table->updateCellAttributes($i, 1, array('id' => 'emph',
                                                          'class' => 'small'));
                $table->updateCellAttributes($i, 2, array('id' => 'emph',
                                                          'class' => 'small'));
            }
        }

        $this->contentPre = '<h2><b><u>Publication Venues</u></b></h2>';
        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new list_venues();
echo $page->toHtml();

?>
