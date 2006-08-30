<?php ;

// $Id: author_report.php,v 1.3 2006/08/30 22:42:20 aicmltec Exp $

/**
 * \file
 *
 * \brief Script that reports the publications whose attachments are not
 * on the file server.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
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

    function author_report() {
        global $access_level;

        parent::pdHtmlPage('author_report');

        if ($access_level <= 1) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();

        $this->contentPre .= '<h2>AICML Author Report</h2>';

        for ($i = 0; $i < count($this->pi_authors) - 1; $i++) {
            for ($j = $i + 1; $j < count($this->pi_authors); $j++) {

                $q = $db->query(
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
                $r = $db->fetchObject($q);
                while ($r) {
                    if (isset($pi_pubs[$r->pub_id]))
                        $pi_pubs[$r->pub_id] .= '<br/>' . $this->pi_authors[$i]
                            . ' and ' . $this->pi_authors[$j];
                    else
                        $pi_pubs[$r->pub_id] = $this->pi_authors[$i] . ' and '
                            . $this->pi_authors[$j];
                    $r = $db->fetchObject($q);
                }
            }
        }

        $this->contentPre .= '<h3>Two PIs</h3>';
        $this->contentPre .= 'Number of publications: ' . count($pi_pubs)
            . '<p/>';

        $c = 0;
        foreach ($pi_pubs as $pub_id => $authors) {
            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);
            $this->contentPre .= ($c + 1) . '. ' . $pub->getCitationHtml()
                . '&nbsp;<a href="../view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="../images/viewmag.png" title="view" alt="view" height="16" '
                . 'width="16" border="0" align="middle" /></a>'
                . '<br/>' . '<span id="small">' . $authors . '</span><p/>';
            $c++;
        }

        for ($i = 0; $i < count($this->pi_authors); $i++) {
            for ($j = 0; $j < count($this->pdf_authors); $j++) {

                $q = $db->query(
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
                $r = $db->fetchObject($q);
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
                    $r = $db->fetchObject($q);
                }
            }
        }

        $this->contentPre .= '<h3>One PI and one PDF</h3>';
        $this->contentPre .= 'Number of publications: ' . count($pi_pdf_pubs)
            . '<p/>';

        $c = 0;
        foreach ($pi_pdf_pubs as $pub_id => $authors) {
            $pub = new pdPublication();
            $pub->dbLoad($db, $pub_id);
            $this->contentPre .= ($c + 1) . '. ' . $pub->getCitationHtml()
                . '&nbsp;<a href="../view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="../images/viewmag.png" title="view" alt="view" height="16" '
                . 'width="16" border="0" align="middle" /></a>'
                . '<br/>' . '<span id="small">' . $authors . '</span><p/>';
            $c++;
        }

        $db->close();
    }
}

session_start();
$access_level = check_login();
$page = new author_report();
echo $page->toHtml();

?>
