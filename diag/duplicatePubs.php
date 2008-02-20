<?php ;

// $Id: duplicatePubs.php,v 1.15 2008/02/20 21:10:27 loyola Exp $

/**
 * Script that reports the publications with two PI's and also one PI and one
 * PDF.
 *
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class duplicatePubs extends pdHtmlPage {
    public function __construct() {
        parent::__construct('duplicatePubs', 'Duplicate Publications',
                           'diag/duplicatePubs.php');

        if ($this->loginError) return;

        echo '<h1>Publications with same title</h1>', 'Note that some publications may exist both in a conference ', 'and later in time in a journal.';

        $all_pubs = pdPubList::create($this->db);
        $titles = array();

        foreach ($all_pubs as $pub) {
            $titles[] = array($pub,
                              preg_replace('/\s\s+/', ' ', 
                                           strtolower($pub->title)));
        }

        //$this->debugVar('titles', $titles);

        $count = 1;
        for ($i = 0, $n = count($titles); $i < $n - 1; ++$i) {
            for ($j = $i + 1; $j < $n; ++$j) {
                if ($titles[$i][1] == $titles[$j][1]) {
                    echo '<h2>Match ', $count, '</h2>';

                    $titles[$i][0]->dbLoad($this->db, $titles[$i][0]->pub_id);
                    $titles[$j][0]->dbLoad($this->db, $titles[$j][0]->pub_id);

                    echo $this->citationGet( $titles[$i][0]), '<br/>', $this->citationGet( $titles[$j][0]);

                    ++$count;
                }
            }
        }
    }

    public function citationGet($pub) {
        assert('is_object($pub)');

        $citation = $pub->getCitationHtml('..') . getPubIcons($pub);

        return $citation;
    }
}

$page = new duplicatePubs();
echo $page->toHtml();

?>