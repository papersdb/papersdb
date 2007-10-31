<?php ;

// $Id: list_publication.php,v 1.41 2007/10/31 23:17:34 loyola Exp $

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
    var $year;
    var $author_id;
    var $venue_id;
    var $cat_id;
    var $keyword;
    var $by;

    public function __construct() {
        parent::__construct('view_publications');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (isset($this->year)) {
            $pub_list = new pdPubList(
                $this->db, array('year_cat' => $this->year));
            $title = '<h1>Publications in ' .$this->year . '</h1>';
        }
        else if (isset($this->venue_id)) {
            $vl = new pdVenueList($this->db);

            if (!array_key_exists($this->venue_id, $vl->list)) {
                $this->pageError = true;
                return;
            }

            $pub_list = new pdPubList(
                $this->db, array('venue_id' => $this->venue_id));
            $title = '<h1>Publications in Venue "'
                . $vl->list[$this->venue_id] . '"</h1>';
        }
        else if (isset($this->cat_id)) {
            $cl = new pdCatList($this->db);

            if (!array_key_exists($this->cat_id, $cl->list)) {
                $this->pageError = true;
                return;
            }

            $pub_list = new pdPubList(
                $this->db, array('cat_id' => $this->cat_id));
            $title = '<h1>Publications in Category "'
                . $cl->list[$this->cat_id] . '"</h1>';
        }
        else if (isset($this->keyword)) {
            $pub_list = new pdPubList(
                $this->db, array('keyword' => $this->keyword));
            $title = '<h1>Publications with keyword "' .$this->keyword
                . '"</h1>';
        }
        else if (isset($this->keyword)) {
            $pub_list = new pdPubList(
                $this->db, array('keyword' => $this->keyword));
            $title = '<h1>Publications with keyword "' .$this->keyword
                . '"</h1>';
        }
        else if (isset($this->author_id)) {
            // If there exists an author_id, only extract the publications for
            // that author
            //
            // This is used when viewing an author.
            $pub_list = new pdPubList(
                $this->db, array('author_id_cat' => $this->author_id));

            $auth = new pdAuthor();
            $auth->dbLoad($this->db, $this->author_id,
                          PD_AUTHOR_DB_LOAD_BASIC);

            $title = '<h1>Publications by ' . $auth->name . '</h1>';
        }
        else if (isset($this->by) || (!isset($_GET) == 0)) {
            if (count($_GET) == 0)
                $viewCat = 'year';
            else
                $viewCat = $this->by;
            $this->pubSelect($viewCat);
            return;
        }
        else {
            $this->pageError = true;
        }

        echo $this->pubSelMenu(), "<br/>\n", $title;
        echo $this->displayPubList($pub_list);
    }

    function pubSelect($viewCat = null) {
        assert('is_object($this->db)');
        echo $this->pubSelMenu($viewCat), '<br/>';
        $text = '';

        switch ($viewCat) {
            case "year":
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));
                $pub_years = new pdPubList($this->db, array('year_list' => true));

                $table->addRow(array('Year', 'Num. Publications'),
                               array('class' => 'emph'));

                foreach (array_values($pub_years->list) as $item) {
                    $cells = array();
                    $cells[] = '<a href="list_publication.php?year='
                        . $item['year'] . '">' . $item['year'] . '</a>';
                    $cells[] = $item['count'];
                    $table->addRow($cells);
                }

                echo '<h2>Publications by Year:</h2>', $table->toHtml();
                break;

            case 'author':
                echo '<h2>Publications by Author:</h2>';

                $al = new pdAuthorList($this->db);

                for ($c = 65; $c <= 90; ++$c) {
                    $table = new HTML_Table(array('class' => 'publist'));

                    $text = '';
                    foreach ($al->list as $auth_id => $name) {
                        if (substr($name, 0, 1) == chr($c))
                            $text .= '<a href="list_publication.php?author_id='
                                . $auth_id . '">' . $name . '</a>&nbsp;&nbsp; ';
                    }
                    $table->addRow(array(chr($c), $text));
                    $table->updateColAttributes(
                        0, array('class' => 'item'), NULL);
                    echo $table->toHtml();
                }
                break;

            case 'venue':
                // publications by keyword
                unset($table);

                $vl = new pdVenueList($this->db);

                echo '<h2>Publications by Venue:</h2>';

                for ($c = 65; $c <= 90; ++$c) {
                    $table = new HTML_Table(array('class' => 'publist'));

                    $text = '';
                    foreach ($vl->list as $vid => $v) {
                        if (substr($v, 0, 1) == chr($c))
                            $text .= '<a href="list_publication.php?venue_id='
                                . $vid . '">' . $v . '</a>&nbsp;&nbsp; ';
                    }
                    $table->addRow(array(chr($c), $text));
                    $table->updateColAttributes(
                        0, array('class' => 'item'), NULL);
                    echo $table->toHtml();
                }
                break;

            case 'category':
                $table = new HTML_Table(array('class' => 'nomargins',
                                              'width' => '100%'));
                $cl = new pdCatList($this->db);

                $table->addRow(array('Category', 'Num. Publications'),
                               array('class' => 'emph'));

                foreach ($cl->list as $cat_id => $category) {
                    $cells = array();
                    $cells[] = '<a href="list_publication.php?cat_id='
                        . $cat_id . '">' . $category . '</a><br/>';
                    $cells[] = $cl->catNumPubs($this->db, $cat_id);
                    $table->addRow($cells);
                }

                echo '<h2>Publications by Category:</h2>', $table->toHtml();
                break;

            case 'keywords':
                // publications by keyword
                unset($table);

                $kl = new pdPubList($this->db, array('keywords_list' => true));

                echo '<h2>Publications by Keyword:</h2>';

                for ($c = 65; $c <= 90; ++$c) {
                    $table = new HTML_Table(array('class' => 'publist'));
                    $text = '';
                    foreach ($kl->list as $kw) {
                        if (substr($kw, 0, 1) == chr($c))
                            $text .= '<a href="list_publication.php?keyword='
                                . $kw . '">' . $kw . '</a>&nbsp;&nbsp; ';
                    }
                    $table->addRow(array(chr($c), $text));
                    $table->updateColAttributes(
                        0, array('class' => 'item'), NULL);
                    echo $table->toHtml();
                }
                break;

            default:
                $this->pageError = true;
        }
    }

    function tableHighlits(&$table) {
        // now assign table attributes including highlighting for even and odd
        // rows
        for ($i = 0; $i < $table->getRowCount(); $i++) {
            if ($i & 1)
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            else
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
            $table->updateCellAttributes($i, 1, array('id' => 'publist'), true);
        }
        $table->updateColAttributes(0, array('class' => 'publist'), true);
    }

    function pubSelMenu($viewCat = null) {
        $pubShowCats = array('year', 'author', 'venue', 'category',
                             'keywords');
        $text = '<div id="sel"><ul>';
        foreach($pubShowCats as $pcat) {
            if ($pcat == $viewCat)
                $text .= '<li><a href="#" class="selected">By '
                    . ucwords($pcat) . '</a></li>';
            else
                $text .= '<li><a href="list_publication.php?by='. $pcat
                    . '">By ' . ucwords($pcat) . '</a></li>';
        }
        $text .= '</ul></div><br/>';

        return $text;
    }
}

$page = new list_publication();
echo $page->toHtml();

?>


