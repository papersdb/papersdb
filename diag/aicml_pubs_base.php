<?php

 /**
  * $Id: aicml_pubs_base.php,v 1.3 2008/02/02 23:02:23 loyola Exp $
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
 * Displays various sets of statistics for the machine learning papers
 * published by AICML PIs, PDFs, students and staff.
 *
 * @package PapersDB
 */
class aicml_pubs_base extends pdHtmlPage {
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
    
    protected $aicml_pdf_studens_staff_authors;

    public function __construct($page_id, $title = null, $relative_url = null,
                                $login_level = pdNavMenuItem::MENU_NEVER) {
        parent::__construct($page_id, $title, $relative_url, $login_level);

        $this->fiscal_year_ts = array();
        foreach (self::$fiscal_years as $key => $fy) {
            $this->fiscal_year_ts[$key] = array(pubDate2Timestamp($fy[0]),
                                                pubDate2Timestamp($fy[1]));
        }
    }
    
    protected function getMachineLearningPapers() {
        $pubs = array();
        // first get publications by PIs
        foreach (self::$aicml_authors['pi'] as $name) {
            $author_pubs = pdPubList::create($this->db,                                    
                array('author_name' => $name,                                                        
                	  'date_start' => self::$author_dates[$name][0],
                      'date_end' => self::$author_dates[$name][1],
                      'pub_id_keys' => true,
                      'keyword' => 'machine learning'));
            $pubs = $this->pubsArrayMerge($pubs, $author_pubs);
        }

        // now get publications by AICML PDFs, students and staff members
     	$this->getPdfStudentsAndStaff();
        foreach ($this->aicml_pdf_studens_staff_authors as $author) {
            $author_pubs
                = pdPubList::create($this->db,
                                    array('author_name' => $author,
                                          'date_start' => self::$fiscal_years[4][0],
                                          'date_end' => self::$fiscal_years[0][1],
                                          'pub_id_keys' => true));
            $pubs = $this->pubsArrayMerge($pubs, $author_pubs);
        }
        
        foreach ($pubs as $pub_id => $pub) {
            $pub->dbLoad($this->db, $pub_id);

            // only consider machine learning papers
            if (!isset($pub->keywords)
                || (strpos(strtolower($pub->keywords), 'machine learning') === false))
                unset($pubs[$pub_id]);

            // publication must have the category assigned and
            // category must be either 'In Journal' or 'In Conference'
            if (!isset($pub->category)
                || (($pub->category->cat_id != 1) 
                    && ($pub->category->cat_id != 3)))
                unset($pubs[$pub_id]);
        }

        uasort($pubs, array('pdPublication', 'pubsDateSortDesc'));
        return $pubs;
    }    

    // adds the publications in $pubs2 that are not already in $pubs1
    protected function pubsArrayMerge($pubs1, $pubs2) {
        assert('is_array($pubs1)');
        assert('is_array($pubs2)');
 
        $result = $pubs1;
        $diffs = array_diff(array_keys($pubs2), array_keys($pubs1));
        foreach ($diffs as $pub_id) {
            $result[$pub_id] = $pubs2[$pub_id];
        }
        return $result;
    }

    /*
     * Use array_diff because some people appear as both students and staff 
     * (i.e. studends were later hired as staff).
     */
    public function getPdfStudentsAndStaff() {
        if (!isset($this->aicml_pdf_studens_staff_authors)) {
            $this->aicml_pdf_studens_staff_authors = array();
            foreach (self::$aicml_authors as $group => $arr)
                if ($group != 'pi')
                    $this->aicml_pdf_studens_staff_authors 
                        = array_merge($this->aicml_pdf_studens_staff_authors,
                                      array_diff($arr, $this->aicml_pdf_studens_staff_authors));
        }
    }

    /* date has to be in YYYY-MM-DD format */
    protected function getFiscalYearKey($date) {
        $datestamp = pubDate2Timestamp($date);
        foreach ($this->fiscal_year_ts as $key => $fyts) {
            if (($fyts[0] <= $datestamp) && ($fyts[1] >= $datestamp))
                return $key;
        }
        throw new Exception("date not within fiscal years: " + date);
        return false;
    }
}

?>
