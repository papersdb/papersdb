<?php ;

// $Id: list_publication.php,v 1.30 2007/03/12 05:25:45 loyola Exp $

/**
 * Lists all the publications in database.
 *
 * Makes each publication a link to it's own seperate page.  If a user is
 * logged in, he/she has the option of adding a new publication, editing any of
 * the publications and deleting any of the publications.
 *
 * Pretty much identical to list_author.php
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requires base class and class that build publication lists. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class list_publication extends pdHtmlPage {
    function list_publication() {
        session_start();
        pubSessionInit();
        parent::pdHtmlPage('view_publications');

        if (isset($_GET['year'])) {
            $pub_list = new pdPubList(
                $this->db, array('year' => $_GET['year']));
            $title = '<h1>Publications in ' .$_GET['year'] . '</h1>';
        }
        else if (isset($_GET['venue_id'])) {
            $vl = new pdVenueList($this->db);

            if (!array_key_exists($_GET['venue_id'], $vl->list)) {
                $this->db->close();
                $this->pageError = true;
                return;
            }

            $pub_list = new pdPubList(
                $this->db, array('venue_id' => $_GET['venue_id']));
            $title = '<h1>Publications in Venue "'
                . $vl->list[$_GET['venue_id']] . '"</h1>';
        }
        else if (isset($_GET['cat_id'])) {
            $cl = new pdCatList($this->db);

            if (!array_key_exists($_GET['cat_id'], $cl->list)) {
                $this->db->close();
                $this->pageError = true;
                return;
            }

            $pub_list = new pdPubList(
                $this->db, array('cat_id' => $_GET['cat_id']));
            $title = '<h1>Publications in Category "'
                . $cl->list[$_GET['cat_id']] . '"</h1>';
        }
        else if (isset($_GET['keyword'])) {
            $pub_list = new pdPubList(
                $this->db, array('keyword' => $_GET['keyword']));
            $title = '<h1>Publications with keyword "' .$_GET['keyword']
                . '"</h1>';
        }
        else if (isset($_GET['keyword'])) {
            $pub_list = new pdPubList(
                $this->db, array('keyword' => $_GET['keyword']));
            $title = '<h1>Publications with keyword "' .$_GET['keyword']
                . '"</h1>';
        }
        else if (isset($_GET['author_id'])) {
            // If there exists an author_id, only extract the publications for
            // that author
            //
            // This is used when viewing an author.
            $pub_list = new pdPubList(
                $this->db, array('author_id' => $_GET['author_id']));

            $auth = new pdAuthor();
            $auth->dbLoad($this->db, $_GET['author_id'],
                          PD_AUTHOR_DB_LOAD_BASIC);

            $title = '<h1>Publications by ' . $auth->name . '</h1>';
        }
        else if (isset($_GET['by']) || (count($_GET) == 0)) {
            if (count($_GET) == 0)
                $viewCat = 'year';
            else
                $viewCat = $_GET['by'];
            $this->pubSelect($viewCat);
            $this->db->close();
            return;
        }
        else {
            $this->pageError = true;
        }

        $this->contentPre .= $this->pubSelMenu() . "<br/>\n" . $title;

        $this->table = new HTML_Table(array('width' => '100%',
                                            'border' => '0',
                                            'cellpadding' => '6',
                                            'cellspacing' => '0'));
        $table =& $this->table;
        $table->setAutoGrow(true);

        if (count($pub_list->list) > 0) {
            $count = 0;
            foreach ($pub_list->list as $pub) {
                ++$count;
                $pub->dbload($this->db, $pub->pub_id);

                $citation = $pub->getCitationHtml();

                // Show Paper
                if ($pub->paper != 'No paper') {
                    $citation .= '<a href="' . $pub->paperAttGetUrl() . '">';

                    if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                        $citation .= '<img src="images/pdf.gif" alt="PDF" '
                            . 'height="18" width="17" border="0" '
                            . 'align="middle">';
                    }

                    if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                        $citation .= '<img src="images/ppt.gif" alt="PPT" '
                            . 'height="18" width="17" border="0" '
                            . 'align="middle">';
                    }

                    if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                        $citation .= '<img src="images/ps.gif" alt="PS" '
                            . 'height="18" width="17" border="0" '
                            . 'align="middle">';
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

                $table->addRow(array($count, $citation));
            }
        }
        else {
            $table->addRow(array('No Publications'));
        }

        $this->tableHighlits($table);

        $this->db->close();
    }

    function pubSelect($viewCat = null) {
        assert('is_object($this->db)');
        $this->contentPre .= $this->pubSelMenu($viewCat) . '<br/>';
        $text = '';

        switch ($viewCat) {
            case "year":
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));
                $pub_years = new pdPubList($this->db, array('year_list' => true));

                $table->addRow(array('Year', 'Num. Publications'),
                               array('id' => 'emph'));

                foreach (array_values($pub_years->list) as $item) {
                    $cells = array();
                    $cells[] = '<a href="list_publication.php?year='
                        . $item['year'] . '">' . $item['year'] . '</a>';
                    $cells[] = $item['count'];
                    $table->addRow($cells);
                }

                $this->contentPre .= '<h2>Publications by Year:</h2>'
                    . $table->toHtml();
                break;

            case 'author':
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));

                $al = new pdAuthorList($this->db);

                for ($c = 65; $c <= 90; ++$c) {
                    $text = '';
                    foreach ($al->list as $auth_id => $name) {
                        if (substr($name, 0, 1) == chr($c))
                            $text .= '<a href="list_publication.php?author_id='
                                . $auth_id . '">' . $name . '</a>&nbsp;&nbsp; ';
                    }
                    $table->addRow(array(chr($c), $text));
                }

                $this->tableHighlits($table);

                $this->contentPre .= '<h2>Publications by Author:</h2>'
                    . $table->toHtml();
                break;

            case 'venue':
                // publications by keyword
                unset($table);
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));

                $vl = new pdVenueList($this->db);

                for ($c = 65; $c <= 90; ++$c) {
                    $text = '';
                    foreach ($vl->list as $vid => $v) {
                        if (substr($v, 0, 1) == chr($c))
                            $text .= '<a href="list_publication.php?venue_id='
                                . $vid . '">' . $v . '</a>&nbsp;&nbsp; ';
                    }
                    $table->addRow(array(chr($c), $text));
                }

                $this->tableHighlits($table);

                $this->contentPre .= '<h2>Publications by Venue:</h2>'
                    . $table->toHtml();
                break;

            case 'category':
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));
                $cl = new pdCatList($this->db);

                $table->addRow(array('Category', 'Num. Publications'),
                               array('id' => 'emph'));

                foreach ($cl->list as $cat_id => $category) {
                    $cells = array();
                    $cells[] = '<a href="list_publication.php?cat_id='
                        . $cat_id . '">' . $category . '</a><br/>';
                    $cells[] = $cl->catNumPubs($this->db, $cat_id);
                    $table->addRow($cells);
                }

                $this->contentPre .= '<h2>Publications by Category:</h2>'
                    . $table->toHtml();
                break;

            case 'keywords':
                // publications by keyword
                unset($table);
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));

                $kl = new pdPubList($this->db, array('keywords_list' => true));

                for ($c = 65; $c <= 90; ++$c) {
                    $text = '';
                    foreach ($kl->list as $kw) {
                        if (substr($kw, 0, 1) == chr($c))
                            $text .= '<a href="list_publication.php?keyword='
                                . $kw . '">' . $kw . '</a>&nbsp;&nbsp; ';
                    }
                    $table->addRow(array(chr($c), $text));
                }

                $this->tableHighlits($table);

                $this->contentPre .= '<h2>Publications by Keyword:</h2>'
                    . $table->toHtml();
                break;

            default:
                $this->pageError = true;
        }
    }

    function tableHighlits(&$table) {
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

            if ($this->access_level > 0) {
                $table->updateCellAttributes($i, 0, array('id' => 'emph',
                                                          'class' => 'small'));
            }
        }
    }

    function pubSelMenu($viewCat = null) {
        $pubShowCats = array('year', 'author', 'venue', 'category',
                             'keywords');
        $text = '<div id="sel"><ul>';
        foreach($pubShowCats as $pcat) {
            if ($pcat == $viewCat)
                $text .= '<li class="selected">By ' . ucwords($pcat) . '</li>';
            else
            $text .= '<li><a href="list_publication.php?by='. $pcat
                . '">By ' . ucwords($pcat) . '</a></li>';
        }
        $text .= '</ul></div>';

        return $text;
    }
}

$page = new list_publication();
echo $page->toHtml();

?>


