<?php ;

// $Id: index.php,v 1.36 2007/03/12 23:05:43 aicmltec Exp $

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
class index extends pdHtmlPage {
    function index() {
        session_start();
        pubSessionInit();

        parent::pdHtmlPage('home');

        if ($this->loginError) return;

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

            $citation = '<li class="wide">' . $pub->getCitationHtml()
                . '&nbsp;' . $this->getPubIcons($pub);

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

$page = new index();
echo $page->toHtml();

?>
