<?php

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
    public function __construct() {
        parent::__construct('home');

        if ($this->loginError) return;

        $this->recentAdditions();
        $this->pubByYears();
    }

    private function recentAdditions() {
        $pub_list = pdPubList::create($this->db, array('sort_by_updated' => true));

        if (empty($pub_list) || (count($pub_list) == 0)) return;

        echo '<h2>Recent Additions:</h2>';

        echo displayPubList($this->db, $pub_list, false, 6);
    }

    private function pubByYears() {
        $pub_years = pdPubList::create($this->db, array('year_list' => true));

        if (empty($pub_years) || (count($pub_years) == 0)) return;

        $table = new HTML_Table(array('class' => 'nomargins',
                                      'width' => '60%'));

        $text = '';
        foreach (array_values($pub_years) as $item) {
            $text .= '<a href="list_publication.php?year=' . $item['year']
                . '">' . $item['year'] . '</a> ';
        }

        $table->addRow(array($text));

        echo '<h2>Publications by Year:</h2>', $table->toHtml();
    }
}

$page = new index();
echo $page->toHtml();

?>
