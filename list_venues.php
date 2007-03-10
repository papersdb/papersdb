<?php ;

// $Id: list_venues.php,v 1.18 2007/03/10 01:23:05 aicmltec Exp $

/**
 * This page displays all venues.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
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
        $db = dbCreate();

        $venue_list = new pdVenueList($db, null, true);

        $this->table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '12',
                                            'cellspacing' => '0'));
        $table =& $this->table;
        $table->setAutoGrow(true);

        foreach (array_keys($venue_list->list) as $venue_id) {
            unset($cells);
            unset($venue);
            $venue = new pdVenue();
            $venue->dbLoad($db, $venue_id);
            $text = '';
            if ($venue->title != '')
                $text .= '<b>' . $venue->title . '</b>';
            if ($venue->type != '')
                $text .= '<br/><b>' . ucfirst($venue->type) . '</b>:&nbsp;';

            $url = $venue->urlGet();

            if ($url != null) {
                $text .= '<a href="' . $url . '" target="_blank">';
            }

            $text .= $venue->nameGet();

            if ($url != null)
                $text .= '</a>';

            if ($venue->data != '') {
                $text .= '<br/>';
                if($venue->type == 'Conference')
                    $text .= '<b>Location:&nbsp;</b>';
                else if($venue->type == 'Journal')
                    $text .= '<b>Publisher:&nbsp;</b>';
                else if($venue->type == 'Workshop')
                    $text .= '<b>Associated Conference:&nbsp;</b>';
                $text .= $venue->data;
            }

            if ($venue->editor != '')
                $text .= "<br/><b>Editor:&nbsp;</b>" . $venue->editor;

            // display occurrences
            if (count($venue->occurrences) > 0) {
                foreach ($venue->occurrences as $occ) {
                    $text .= '<br/>';

                    $date = explode('-', $occ->date);

                    if ($occ->url != '') {
                        $text .= '<a href="' . $occ->url . '" target="_blank">';
                    }

                    $text .= $date[0];

                    if ($occ->url != '') {
                        $text .= '</a>';
                    }

                    if ($occ->location != '')
                        $text .= ', ' . $occ->location;
                }
            }
            else {
                if (($venue->date != '') && ($venue->date != '0000-00-00')) {
                    $date = explode('-', $venue->date);
                    $text .= "<br/><b>Date:&nbsp;</b>" . $date[0] . '-'
                        . $date[1];
                }
            }

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

        $this->contentPre = '<h1>Publication Venues</h1>';
        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new list_venues();
echo $page->toHtml();

?>
