<?php ;

// $Id: aicml_publications.php,v 1.4 2008/01/15 21:21:07 loyola Exp $

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
    	'Szepesvari, C' => array('2006-09-01', '2008-03-31'),
        'Schuurmans, D' => array('2003-07-01', '2008-03-31'),
        'Schaeffer, J'  => array('2002-09-01', '2008-03-31'),
        'Bowling, M'    => array('2003-07-01', '2008-03-31'),
        'Goebel, R'     => array('2002-09-01', '2008-03-31'),
        'Sutton, R'     => array('2003-09-01', '2008-03-31'),
        'Holte, R'      => array('2002-09-01', '2008-03-31'),
        'Greiner, R'    => array('2002-09-01', '2008-03-31'));

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
    
    protected $format = 0;
    protected $format_label;
    static $button_labels = array(
    	'Display as Formatted Text',
    	'Display as HTML');

    public function __construct() {
        parent::__construct('aicml_publications');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);
        
        if (isset($this->format_label)) {
        	foreach (self::$button_labels as $key => $label)
        	if (strncmp($this->format_label, $label, strlen($label)) == 0)
        		$this->format = $key;
        }

        echo '<h2>AICML Publications</h2>';

        $pubs = array();
        // first get publications by PIs
        foreach ($this->pi_authors as $pi_author => $dates) {
            $author_pubs
                = pdPubList::create($this->db,
                                    array('author_name' => $pi_author,
                                          'date_start' => $dates[0],
                                          'date_end' => $dates[1],
                                          'pub_id_keys' => true));
            $pubs = $this->pubs_array_merge($pubs, $author_pubs);
     	}

     	// now get publications by other AICM members
     	$other_authors = array_merge($this->pdf_authors,
                                     $this->student_authors,
                                     $this->staff_authors);

        foreach ($other_authors as $author) {
            $author_pubs
                = pdPubList::create($this->db,
                                    array('author_name' => $author,
                                          'date_start' => $this->fiscal_years[4][0],
                                          'date_end' => $this->fiscal_years[0][1],
                                          'pub_id_keys' => true));
            $pubs = $this->pubs_array_merge($pubs, $author_pubs);
        }

        $pubs = $this->pubs_sort($pubs);
        krsort($pubs);
        
        // now display the page
        $buttons = array();
        $form = new HTML_QuickForm('aicml_pubs', 'get', 'aicml_publications.php');
        
       	$buttons[] = HTML_QuickForm::createElement(                    
       		'submit', 'format_label', self::$button_labels[1 - $this->format]);
         
        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        // create a new renderer because $form->defaultRenderer() creates
        // a single copy
        $renderer = new HTML_QuickForm_Renderer_Default();
        $form->accept($renderer);

        echo $renderer->toHtml();
        
        if ($this->format == 1)
        	$result = '';
        
        foreach ($pubs as $year => $year_pubs) {
        	foreach ($year_pubs as $pub) {
        		$citation = utf8_encode($this->getCitationHtml($pub));
        		if ($this->format == 0)
        			echo $citation . '<p/>';
        		else
        			$result .= format80($citation) . "<p/>\n\n";
        	}
        }
        
        if ($this->format == 1)
        	echo '<pre style="font-size:medium">' . htmlentities($result) . '</pre>';
    }

    // adds the publications in $pubs2 that are not already in $pubs1
    private function pubs_array_merge($pubs1, $pubs2) {
    	$result = $pubs1;
    	$diffs = array_diff(array_keys($pubs2), array_keys($pubs1));
    	foreach ($diffs as $pub_id) {
            $result[$pub_id] = $pubs2[$pub_id];
    	}
    	return $result;
    }

    // sort the publications by year
    private function pubs_sort($pubs) {
    	$sorted_pubs = array();
    	foreach ($pubs as $pub_id => $pub) {
            $publishedSplit = split('-', $pub->published);
            assert('count($publishedSplit) == 3');
            $sorted_pubs[$publishedSplit[0]][$pub_id] = $pub;
    	}
    	return $sorted_pubs;
    }

    private function getCitationHtml($pub) {
    	$pub->dbLoad($this->db, $pub->pub_id);
        $citation = '';

        if (count($pub->authors) > 0) {      
        	$authors = array();             
        	foreach ($pub->authors as $auth) {
                $authors[] = $auth->firstname[0] . '. '	. $auth->lastname;
            }
            $citation .= '<i>' . implode(', ', $authors) . '</i>. ';
        }

        // Title
        $citation .= '<b>&quot;' . $pub->title. '&quot;</b>. ';

        // Additional Information - Outputs the category specific information
        // if it exists
        $info = $pub->getInfoForCitation();

        if (strpos($pub->published, '-') !== false)
            $pub_date = split('-', $pub->published);

        //  Venue
        $v = '';

        // category -> if not conference, journal, or workshop, book or in book
        if (is_object($pub->category)
            && !empty($pub->category->category)
            && (!in_array($pub->category->category,
                          array('In Conference', 'In Journal', 'In Workshop',
                                'In Book', 'Book')))) {
            $v .= $pub->category->category;
        }

        if (is_object($pub->venue)) {
            if (!empty($v))
                $v .= ', ';

            if (isset($pub_date))
                $url = $pub->venue->urlGet($pub_date[0]);
            else
                $url = $pub->venue->urlGet();

            $vname = $pub->venue->nameGet();

            if ($vname != '')
                $v .= $vname;
            else
                $v .= $pub->venue->title;

            if (!empty($pub->venue->data)
                && ($pub->venue->categoryGet() == 'Workshop'))
                $v .= ' (within ' . $pub->venue->data. ')';

            if (isset($pub_date))
                $location = $pub->venue->locationGet($pub_date[0]);
            else
                $location = $pub->venue->locationGet();

            if ($location != '')
                $v .= ', ' . $location;
        }

        $date_str = '';

        if (isset($pub_date)) {
            if ($pub_date[1] != 0)
                $date_str .= date('F', mktime (0, 0, 0, $pub_date[1])) . ' ';
            if ($pub_date[0] != 0)
                $date_str .= $pub_date[0];
        }
        
        $citation .= '<span style="font-size:x-small">';

        if (($v != '') && ($info != '') && ($date_str != ''))
            $citation .= $v . ', ' . $info . ', ' . $date_str . '.';
        else if (($v != '') && ($info == '') && ($date_str != ''))
            $citation .= $v . ', ' . $date_str . '.';
        else if (($v != '') && ($info == '') && ($date_str == ''))
            $citation .= $v . '.';
        else if (($v == '') && ($info != '') && ($date_str != ''))
            $citation .= $info . ', ' . $date_str . '.';
        else if (($v == '') && ($info == '') && ($date_str != ''))
            $citation .= $date_str . '.';
            
        $citation .= '</span>';

        return $citation;
    }

}

$page = new author_report();
echo $page->toHtml();

?>
