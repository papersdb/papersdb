<?php

 /**
  * $Id: pub_report.php,v 1.1 2008/01/31 22:02:23 loyola Exp $
  *
  * Script that reports statistics for thepublications made by AICML PIs, PDFs,
  * students and staff.
  *
  * @package PapersDB
  */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the page.
 *
 * @package PapersDB
 */
class author_report extends pdHtmlPage {
    protected static $tier1_venues = array(
    	'AAAI', 'AIJ', 'CCR', 'ICML', 'IJCAI', 'JAIR', 'JMLR', 'MLJ', 'NAR',
    	'NIPS', 'UAI');

    protected static $fiscal_years = array(
        array('2002-09-01', '2003-08-31'),
        array('2003-09-01', '2004-08-31'),
        array('2004-09-01', '2006-03-31'),
        array('2006-04-01', '2007-03-31'),
        array('2007-04-01', '2008-03-31'));

    protected static $aicml_authors = array(
        'pi' => array(
            'Bowling, M',
            'Goebel, R',
            'Greiner, R',
            'Holte, R',
            'Schaeffer, J',
            'Schuurmans, D',
            'Sutton, R',
            'Szepesvari, C'),
        'pdf' => array(
            'Engel, Y',
            'Kirshner, S',
            'Price, R',
            'Ringlstetter, C',
            'Wang, Shaojun',
            'Zheng, T',
            'Zinkevich, M',
            'Cheng, L',
            'Southey, F'),
        'student' => array(
            'Antonie, M',
            'Asgarian, N',
            'Bard, N',
            'Billings, D',
            'Botea, A',
            'Chen, J',
            'Coulthard, E',
            'Davison, K',
            'Dwyer, K',
            'Farahmand, A',
            'Fraser, B',
            'Geramifard, A',
            'Ghodsi, A',
            'Guo, Y',
            'Guo, Z',
            'Heydari, M',
            'Hlynka, M',
            'Hoehn, B',
            'Huang, J',
            'Jiao, F',
            'Johanson, M',
            'Joyce, B',
            'Kaboli, A',
            'Kan, M',
            'Kapoor, A',
            'Koop, A',
            'Lee, C',
            'Lee, M',
            'Levner, I',
            'Li, L',
            'Lizotte, D',
            'Lu, Z',
            'McCracken, P',
            'Milstein, A',
            'Morris, M',
            'Neufeld, J',
            'Newton, J',
            'Niu, Y',
            'Paduraru, C',
            'Poulin, B',
            'Rafols, E',
            'Schauenberg, T',
            'Schmidt, M',
            'Silver, D',
            'Singh, A',
            'Tanner, B',
            'Wang, P',
            'Wang, Q',
            'Wang, T',
            'Wang, Y',
            'White, A',
            'Wilkinson, D',
            'Wu, J',
            'Wu, X',
            'Xiao, G',
            'Xu, L',
            'Zhang, Q',
            'Zheng, T',
            'Zhu, T'),
        'staff' => array(
            'Arthur, R',
            'Asgarian, N',
            'Baich, T',
            'Block, D',
            'Coghlan, B',
            'Coulthard, E',
            'Coulthard, E',
            'Dacyk, V',
            'DeMarco, M',
            'Duguid, L',
            'Eisner, R',
            'Farhangfar, A',
            'Flatt, A',
            'Fraser, S',
            'Grajkowski, J',
            'Harrison, E',
            'Hiew, A',
            'Hoehn, B',
            'Homaeian, L',
            'Huntley, D',
            'Jewell, K',
            'Koop, A',
            'Larson, B',
            'Loh, W',
            'Loyola, N',
            'Ma, G',
            'McMillan, K',
            'Melanson, A',
            'Morris, M',
            'Neufeld, J',
            'Newton, J',
            'Nicotra, L',
            'Pareek, P',
            'Parker, D',
            'Paulsen, J',
            'Poulin, B',
            'Radkie, M',
            'Roberts, J',
            'Shergill, A',
            'Smith, C',
            'Sokolsky, M',
            'Stephure, M',
            'Thorne, W',
            'Trommelen, M',
            'Upright, C',
            'Vicentijevic, M',
            'Vincent, S',
            'Walsh, S',
            'White, T',
            'Woloschuk, D',
            'Young, A',
            'Zheng, T',
            'Zhu, T'));

    protected static $author_dates = array(
        'Bowling, M'    => array('2003-07-01', '2008-03-31'),
        'Goebel, R'     => array('2002-09-01', '2008-03-31'),
        'Greiner, R'    => array('2002-09-01', '2008-03-31'),
        'Holte, R'      => array('2002-09-01', '2008-03-31'),
        'Schaeffer, J'  => array('2002-09-01', '2008-03-31'),
    	'Schuurmans, D' => array('2003-03-18', '2008-03-31'),
        'Sutton, R'     => array('2003-09-01', '2008-03-31'),
        'Szepesvari, C' => array('2006-09-01', '2008-03-31'));
    
    protected static $author_re = array(
        'Szepesvari, C' => '/Szepesv.+ri, C/');

    protected $fiscal_year_ts;
    protected $stats;

    public function __construct() {
        parent::__construct('aicml_publications');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        $this->stats = array();
        $this->fiscal_year_ts = array();
        foreach (self::$fiscal_years as $key => $fy) {
            $this->fiscal_year_ts[$key] = array(pubDate2Timestamp($fy[0]),
                                                pubDate2Timestamp($fy[1]));
            $this->stats[$key] = array('N' => array(),
                                       'Y' => array());
        }

        $pubs = array();
        // first get publications by PIs
        foreach (self::$aicml_authors['pi'] as $name) {
            $author_pubs = pdPubList::create($this->db,                                    
                array('author_name' => $name,                                                        
                	  'date_start' => self::$author_dates[$name][0],
                      'date_end' => self::$author_dates[$name][1],
                      'pub_id_keys' => true,
                      'keyword' => 'machine learning'));
            $pubs = $this->pubs_array_merge($pubs, $author_pubs);
        }

        // now get publications by other AICM members
        $other_authors = array();
        foreach (self::$aicml_authors as $group => $arr) 
            if ($group != 'pi')
                $other_authors = array_merge($other_authors, $arr);

        foreach ($other_authors as $author) {
            $author_pubs
                = pdPubList::create($this->db,
                                    array('author_name' => $author,
                                          'date_start' => self::$fiscal_years[4][0],
                                          'date_end' => self::$fiscal_years[0][1],
                                          'pub_id_keys' => true,
                                          'keyword' => 'machine learning'));
            $pubs = $this->pubs_array_merge($pubs, $author_pubs);
        }

        uasort($pubs, array('pdPublication', 'pubsDateSortDesc'));

        echo '<h2>Publications by Principal Investigator</h2>';

        // collect stats
        foreach ($pubs as $pub_id => $pub) {
            $pub->dbLoad($this->db, $pub_id);

            //if ($pub->pub_id == 370)
            //    debugVar('$pub', $pub);

            // publication must have the category assigned
            if (!isset($pub->category))
                continue;

            // category must be either 'In Journal' or 'In Conference'
            if (($pub->category->cat_id != 1) && ($pub->category->cat_id != 3))
                continue;
        
            // only consider machine learning papersd
            if (strpos(strtolower($pub->keywords), 'machine learning') === false)
                continue;

            $isT1 = $this->pubIsTier1($pub) ? 'Y' : 'N';
            $fy   = $this->getFiscalYearKey($pub->published);
            $pub_pi_authors = $this->getPubPiAuthors($pub);

            if (!isset($this->stats[$fy][$isT1][$pub_pi_authors]))
                $this->stats[$fy][$isT1][$pub_pi_authors] = array();
            array_push($this->stats[$fy][$isT1][$pub_pi_authors], $pub->pub_id);
        }
        krsort($this->stats);
        
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Fiscal Year', 'T1', 'Author(s)',
                             'Num Pubs', 'Pub Ids'));
        $table->setRowType(0, 'th');
        
        $class = 'stats_odd';
        $row_count = 0;
        foreach ($this->stats as $fy => $subarr1) {
            krsort($subarr1);
            foreach ($subarr1 as $t1 => $subarr2) {
                ksort($subarr2);
                foreach ($subarr2 as $authors => $pub_ids) {
                    $pub_links = array();
                    rsort($pub_ids);
                    foreach ($pub_ids as $pub_id) {
                        $pub_links[] = '<a href="../view_publication.php?pub_id='
                        	. $pub_id . '">' . $pub_id . '</a>';
                    }
                    
                    $table->addRow(array(implode(' - ', self::$fiscal_years[$fy]), 
                                         $t1, $authors, count($pub_ids),
                                         implode(', ', $pub_links)),
                                   array('class' => $class));
                    ++$row_count;
                    $table->updateCellAttributes($row_count, 4,
                        array('class' => $class . '_pub_id'), NULL);
                }
                if ($class == 'stats_odd')
                    $class = 'stats_even';
                else
                    $class = 'stats_odd';
            }
        }
        echo $table->toHtml();
        
        // show totals
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Fiscal Year', 'T1', 'Num Pubs'));
        $table->setRowType(0, 'th');
        $class = 'stats_odd';
        $pub_count_t1 = 0;
        $pub_count_non_t1 = 0;
        foreach ($this->stats as $fy => $subarr1) {
            krsort($subarr1);
            foreach ($subarr1 as $t1 => $subarr2) {
                ksort($subarr2);
                $pub_count = 0;
                foreach ($subarr2 as $authors => $pub_ids)
                    $pub_count += count($pub_ids);
                    
                if ($t1 == 'Y')
                    $pub_count_t1 += $pub_count;
                else
                    $pub_count_non_t1 += $pub_count;
                    
                $table->addRow(array(implode(' - ', self::$fiscal_years[$fy]),
                    $t1, $pub_count),
                    array('class' => $class));
                if ($class == 'stats_odd')
                    $class = 'stats_even';
                else
                    $class = 'stats_odd';
            }
        }
        $table->addRow(array('<b>TOTAL</b>', 'Y', $pub_count_t1),
                             array('class' => 'stats_odd'));
        $table->addRow(array('<b>TOTAL</b>', 'N', $pub_count_non_t1),
                             array('class' => 'stats_even'));
        echo $table->toHtml();
    }

    // adds the publications in $pubs2 that are not already in $pubs1
    private function pubs_array_merge($pubs1, $pubs2) {
        assert('is_array($pubs1)');
        assert('is_array($pubs2)');

        $result = $pubs1;
        $diffs = array_diff(array_keys($pubs2), array_keys($pubs1));
        foreach ($diffs as $pub_id) {
            $result[$pub_id] = $pubs2[$pub_id];
        }
        return $result;
    }

    /* date has to be in YYYY-MM-DD format */
    private function getFiscalYearKey($date) {
        $datestamp = pubDate2Timestamp($date);
        foreach ($this->fiscal_year_ts as $key => $fyts) {
            if (($fyts[0] <= $datestamp) && ($fyts[1] >= $datestamp))
                return $key;
        }
        throw new Exception("date not within fiscal years: " + date);
        return false;
    }

    private function pubIsTier1($pub) {
        assert('is_object($pub)');

        // check that pub has a venue assigned
        if (!is_object($pub->venue)) return false;

        // check that venue has a title (aka acronym) assigned
        if (!isset($pub->venue->title)) return false;

        return in_array($pub->venue->title, self::$tier1_venues);
    }

    private function getPubMatchinglAuthors($pub, $authors, $authors_re) {
        assert('is_object($pub)');
        assert('is_array($authors)');
        if (isset($authors_re))
            assert('is_array($authors_re)');
        
        $matching_authors = array();
        $pub_authors = $pub->authorsToString();
        foreach ($authors as $author) {
            if (isset($authors_re[$author])) {
                if (preg_match($authors_re[$author], $pub_authors) > 0)
                    $matching_authors[] = $author;
            }
            else {
                if (strpos($pub_authors, $author) !== false)
                    $matching_authors[] = $author;
            }
        }
        sort($matching_authors);
        return implode('; ', $matching_authors);
    }

    private function getPubPiAuthors($pub) {
        assert('is_object($pub)');
        return $this->getPubMatchinglAuthors($pub, self::$aicml_authors['pi'],
                                             self::$author_re);
    }

    private function getPubStaffAuthors($pub) {
        assert('is_object($pub)');
        if (!isset($this->aicml_all_authors)) {
            $this->aicml_all_authors = array();
            foreach (self::$aicml_authors as $group => $arr)
                $this->aicml_all_authors = array_merge($this->aicml_all_authors, $arr);
        }
        return $this->getPubMatchinglAuthors($pub, $this->aicml_all_authors,
                                             self::$author_re);
    }
}

$page = new author_report();
echo $page->toHtml();

?>
