<?php ;

// $Id: index.php,v 1.35 2007/03/12 05:25:45 loyola Exp $

/**
 * Main page for PapersDB.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class indexPage extends pdHtmlPage {
    function indexPage() {
        session_start();
        pubSessionInit();

        parent::pdHtmlPage('home');

        $this->recentAdditions();
        $this->pubByYears();

        $this->db->close();
    }

    function recentAdditions() {
        $pub_list = new pdPubList($this->db, array('sort_by_updated' => true));

        if (!isset($pub_list->list)) return;

        $this->contentPre = '<h2>Recent Additions:</h2><ul>';
        $stringlength = 0;
        foreach ($pub_list->list as $pub) {
            if ($stringlength > 5000) break;

            // get all info for this pub
            $pub->dbload($this->db, $pub->pub_id);

            $citation = '<li class="wide">' . $pub->getCitationHtml();

            // Show Paper
            if ($pub->paper != 'No paper') {
                $citation .= '<a href="' . $pub->paperAttGetUrl() . '">';

                if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                    $citation .= '<img src="images/pdf.gif" alt="PDF" '
                        . 'height="18" width="17" border="0" align="middle">';
                }

                if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                    $citation .= '<img src="images/ppt.gif" alt="PPT" height="18" '
                        . 'width="17" border="0" align="middle">';
                }

                if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                    $citation .= '<img src="images/ps.gif" alt="PS" height="18" '
                        . 'width="17" border="0" align="middle">';
                }
                $citation .= '</a>';
            }

            $citation .= '<a href="view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="images/viewmag.png" title="view" alt="view" '
                . ' height="16" width="16" border="0" align="middle" /></a>';

            if ($this->access_level > 0)
                $citation .= '<a href="Admin/add_pub1.php?pub_id='
                    . $pub->pub_id . '">'
                    . '<img src="images/pencil.png" title="edit" alt="edit" '
                    . ' height="16" width="16" border="0" align="middle" />'
                    . '</a>';

            $citation .= '</li>';

            $stringlength += strlen($citation);

            $this->contentPre .= $citation;

        }
        $this->contentPre .= '</ul>';
    }

    function pubByYears() {
        $pub_years = new pdPubList($this->db, array('year_list' => true));

        if (!isset($pub_years->list)) return;

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '60%'));

        $text = '';
        foreach (array_values($pub_years->list) as $item) {
            $text .= '<a href="list_publication.php?year=' . $item['year']
                . '">' . $item['year'] . '</a> ';
        }

        $table->addRow(array($text));

        $this->contentPre .= '<h2>Publications by Year:</h2><ul>'
            . $table->toHtml();
    }
}

$page = new indexPage();
echo $page->toHtml();

?>
