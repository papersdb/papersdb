<?php ;

// $Id: aicml_publications.php,v 1.1 2008/01/15 02:26:36 loyola Exp $

/**
 * Script that reports the publications with two PI's and also one PI and one
 * PDF.
 *
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class author_report extends pdHtmlPage {
	protected $fiscal_years = array(
        array('2007-04-01', '2008-03-31'),
        array('2006-04-01', '2007-03-31'),
		array('2004-09-01', '2006-03-31'),
		array('2003-09-01', '2004-08-31'),        
		array('2002-09-01', '2003-08-31'));
        
    protected $pi_authors = array(
    	'Szepesv.ri, C' => array('2006-09-01', '2010-01-01'),
        'Schuurmans, D' => array('2003-07-01', '2010-01-01'),
        'Schaeffer, J'  => array('2002-09-01', '2010-01-01'),
        'Bowling, M'    => array('2003-07-01', '2010-01-01'),
        'Goebel, R'     => array('2002-09-01', '2010-01-01'),
        'Sutton, R'     => array('2003-09-01', '2010-01-01'),
        'Holte, R'      => array('2002-09-01', '2010-01-01'),
        'Greiner, R'    => array('2002-09-01', '2010-01-01'));

    protected $pdf_authors = array('Engel, Y',
                             'Kirshner, S',
                             'Price, R',
                             'Ringlstetter, C',
                             'Wang, Shaojun',
                             'Zheng, T',
                             'Zinkevich, M',
                             'Cheng, L',
                             'Southey, F');
    
    protected $student_authors = array('Antonie, M',
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
                       'Zhu, T');

	protected $staff_authors = array('Arthur, R',
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
                     'Zhu, T');

    public function __construct() {
        parent::__construct('aicml_publications');
        
		$non_pi_authors = array_merge($this->pdf_authors, 
									  $this->student_authors,
									  $this->staff_authors);

        if ($this->loginError) return;

        echo '<h2>AICML Publications</h2>';

        foreach ($this->fiscal_years as $fiscal_year) {
        	$pubs = pdPubList::create($this->db, 
         							  array('date_start' => $fiscal_year[0],
        			  						'date_end' => $fiscal_year[1]));
         							  
     		foreach ($pubs as $pub) {
     			$is_aicml_pub = false;
     		
     			// check if the author is a principal investigator
     			$pub->dbLoad($this->db, $pub->pub_id);
     			$author_str = $pub->authorsToString();
     			foreach ($this->pi_authors as $pi_author => $member) {
     				if (preg_match('/'.$pi_author.'/', $author_str)
     					&& $this->piPubDateValid($pi_author, $pub->published)) {
     					$aicml_pubs[$pub->pub_id] = $pub;
     					$is_aicml_pub = true;
     				}
     			}
     		}
        		
        	echo $this->displayPubList($aicml_pubs, false);
        	return;
        }
    }
    
    private function piPubDateValid($piName, $pubDate) {
    	assert('isset($this->pi_authors[$piName])');
    	
    	$pubDateSplit = split('-', $pubDate);
    	assert('count($pubDateSplit) == 3');
    	$pubDateStamp = mktime(0, 0, 0, 0, $pubDateSplit[1], $pubDateSplit[2], 
    		$pubDateSplit[0]);
    	
    	$piStartDateSplit = split('-', $this->pi_authors[$piName][0]);
    	assert('count($piStartDateSplit) == 3');
    	$piStartDateStamp = mktime(0, 0, 0, 0, $piStartDateSplit[1], 
    		$piStartDateSplit[2], $piStartDateSplit[0]);
    	
    	$piEndDateSplit = split('-', $this->pi_authors[$piName][1]);
    	assert('count($piEndDateSplit) == 3');
    	$piEndDateStamp = mktime(0, 0, 0, 0, $piEndDateSplit[1], 
    		$piEndDateSplit[2], $piEndDateSplit[0]);
    		
    	return (($pubDateSplit >= $piStartDateSplit) 
    			 && ($pubDateSplit <= $piEndDateSplit));
    }
}

$page = new author_report();
echo $page->toHtml();

?>
