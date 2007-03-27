<?php ;

// $Id: list_venues.php,v 1.26 2007/03/27 17:19:33 aicmltec Exp $

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
    var $tab;

    function list_venues() {
        parent::pdHtmlPage('all_venues');

        if ($this->loginError) return;

        if (!isset($this->tab))
            $this->tab = 'A';
        else if ((strlen($this->tab) != 1) || (ord($this->tab) < ord('A'))
                 || (ord($this->tab) > ord('Z'))) {
            $this->pageError = true;
            return;
        }

        $this->loadHttpVars(true, false);

        $venue_list = new pdVenueList($this->db,
                                      array('starting_with' => $this->tab));

        echo $this->alphaSelMenu($this->tab, get_class($this) . '.php');

        echo '<h2>Publication Venues</h2>';

        if (!isset($venue_list->list) || (count($venue_list->list) == 0)) {
            echo 'No venues with name starting with ' . $this->tab
                . '<br/>';
            return;
        }

        $this->table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '12',
                                            'cellspacing' => '0'));
        $table =& $this->table;
        $table->setAutoGrow(true);

        foreach ($venue_list->list as $venue) {
            // only show global venues
            if ($venue->v_usage == 'single') continue;

            unset($cells);
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

            if ($this->access_level > 0) {
                $cells[] = $this->getVenueIcons($venue);
            }

            $table->addRow($cells, array('class' => 'venuelist'));
        }

        // now assign table attributes including highlighting for even and odd
        // rows
        for ($i = 0; $i < $table->getRowCount(); $i++) {
            if ($i & 1)
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            else
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
        }
    }
}

$page = new list_venues();
echo $page->toHtml();

?>
