<?php ;

// $Id: list_venues.php,v 1.30 2007/10/26 22:03:15 aicmltec Exp $

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
        parent::__construct('all_venues');

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

        foreach ($venue_list->list as $venue) {
            // only show global venues
            if ($venue->v_usage == 'single') continue;

            $venue->dbLoad($this->db, $venue->venue_id);

            $table = new HTML_Table(array('class' => 'publist'));
            $cells = array();
            $text = '';

            if ($venue->title != '')
                $text .= '<b>' . $venue->title . '</b><br/>';
            $v_cat = $venue->categoryGet();
            if (!empty($v_cat))
                $text .= '<b>' . ucfirst($v_cat) . '</b>:&nbsp;';

            $url = $venue->urlGet();

            if ($url != null) {
                $text .= '<a href="' . $url . '" target="_blank">';
            }

            $text .= $venue->nameGet();

            if ($url != null)
                $text .= '</a>';

            if ($venue->data != '') {
                $text .= '<br/>';
                if ($v_cat == 'Conference')
                    $text .= '<b>Location:&nbsp;</b>';
                else if ($v_cat == 'Journal')
                    $text .= '<b>Publisher:&nbsp;</b>';
                else if ($v_cat == 'Workshop')
                    $text .= '<b>Associated Conference:&nbsp;</b>';
                $text .= $venue->data;
            }

            if ($venue->editor != '')
                $text .= "<br/><b>Editor:&nbsp;</b>" . $venue->editor;

            if (isset($venue->ranking))
                $text .= '<br/><b>Ranking</b>: ' . $venue->ranking;

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

            $table->addRow($cells);
            $table->updateColAttributes(1, array('class' => 'icons'), NULL);

            echo $table->toHtml();
        }
    }
}

$page = new list_venues();
echo $page->toHtml();

?>
