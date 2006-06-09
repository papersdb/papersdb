<?php ;

// $Id: list_venues.php,v 1.2 2006/06/09 22:08:58 aicmltec Exp $

/**
 * \file
 *
 * \brief This page displays all venues.
 */

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdVenue.php';

/**
 * Renders the whole page.
 */
class list_venues extends pdHtmlPage {
    function list_venues() {
        parent::pdHtmlPage('all_venues', 'Add or Edit Venue');
        $db =& dbCreate();

        $venue_list = new pdVenueList();
        $venue_list->dbLoad($db);
        $venue = new pdVenue();

        $this->table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table =& $this->table;
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

            if ($logged_in) {
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

$page = new list_venues();
echo $page->toHtml();

?>
