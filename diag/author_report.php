<?php ;

// $Id: author_report.php,v 1.19 2007/10/31 23:17:34 loyola Exp $

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
class author_report extends pdHtmlPage {
    var $pi_authors = array('Szepesvari, C',
                            'Schuurmans, D',
                            'Schaeffer, J',
                            'Bowling, M',
                            'Goebel, R',
                            'Sutton, R',
                            'Holte, R',
                            'Greiner, R');

    var $pdf_authors = array('Engel, Y',
                             'Kirshner, S',
                             'Price, R',
                             'Ringlstetter, C',
                             'Wang, Shaojun',
                             'Zheng, T',
                             'Zinkevich, M',
                             'Cheng, L',
                             'Southey, F');

    var $pi_pubs;
    var $pi_pdf_pubs;

    public function __construct() {
        parent::__construct('author_report', 'Author Report',
                           'diag/author_report.php');

        if ($this->loginError) return;

        echo '<h2>AICML Author Report</h2>';

        for ($i = 0, $n = count($this->pi_authors); $i < $n - 1; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {

                $q = $this->db->query(
                    'SELECT publication.pub_id FROM '
                    . '(SELECT pub_id, name FROM pub_author, author '
                    . 'WHERE name like \'%' . $this->pi_authors[$i]
                    . '%\' AND pub_author.author_id=author.author_id) as c, '
                    . '(SELECT pub_id, name FROM pub_author, author '
                    . 'WHERE name like \'%' . $this->pi_authors[$j]
                    . '%\' AND pub_author.author_id=author.author_id) as f, '
                    . 'publication '
                    . 'WHERE c.pub_id=publication.pub_id '
                    . 'AND f.pub_id=publication.pub_id');
                $r = $this->db->fetchObject($q);
                while ($r) {
                    if (isset($pi_pubs[$r->pub_id]))
                        $pi_pubs[$r->pub_id] .= '<br/>' . $this->pi_authors[$i]
                            . ' and ' . $this->pi_authors[$j];
                    else
                        $pi_pubs[$r->pub_id] = $this->pi_authors[$i] . ' and '
                            . $this->pi_authors[$j];
                    $r = $this->db->fetchObject($q);
                }
            }
        }

        echo '<h3>Two PIs</h3>';
        echo 'Number of publications: ', count($pi_pubs), '<p/>';

        $c = 0;
        foreach ($pi_pubs as $pub_id => $authors) {
            $pub = new pdPublication();
            $pub->dbLoad($this->db, $pub_id);
            echo ($c + 1), '. ', $pub->getCitationHtml('..'), '&nbsp;', $this->getPubIcons($pub), '<br/><span class="small">', $authors, '</span><p/>';
            $c++;
            unset($pub);
        }

        for ($i = 0, $n = count($this->pi_authors); $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {

                $q = $this->db->query(
                    'SELECT publication.pub_id FROM '
                    . '(SELECT pub_id, name FROM pub_author, author '
                    . 'WHERE name like \'%' . $this->pi_authors[$i]
                    . '%\' AND pub_author.author_id=author.author_id) as c, '
                    . '(SELECT pub_id, name FROM pub_author, author '
                    . 'WHERE name like \'%' . $this->pdf_authors[$j]
                    . '%\' AND pub_author.author_id=author.author_id) as f, '
                    . 'publication '
                    . 'WHERE c.pub_id=publication.pub_id '
                    . 'AND f.pub_id=publication.pub_id');
                $r = $this->db->fetchObject($q);
                while ($r) {
                    // skip if already included in report
                    if (!isset($pi_pubs[$r->pub_id])) {
                        if (isset($pi_pdf_pubs[$r->pub_id]))
                            $pi_pdf_pubs[$r->pub_id] .= '<br/>'
                                . $this->pi_authors[$i]
                                . ' and ' . $this->pdf_authors[$j];
                        else
                            $pi_pdf_pubs[$r->pub_id] = $this->pi_authors[$i]
                                . ' and ' . $this->pdf_authors[$j];
                    }
                    $r = $this->db->fetchObject($q);
                }
            }
        }

        echo '<h3>One PI and one PDF</h3>';
        echo 'Number of publications: ', count($pi_pdf_pubs), '<p/>';

        $c = 0;
        foreach ($pi_pdf_pubs as $pub_id => $authors) {
            $pub = new pdPublication();
            $pub->dbLoad($this->db, $pub_id);
            echo ($c + 1), '. ', $pub->getCitationHtml('..'), '&nbsp;', $this->getPubIcons($pub), '<br/><span class="small">', $authors, '</span><p/>';
            $c++;
            unset($pub);
        }
    }
}

$page = new author_report();
echo $page->toHtml();

?>
