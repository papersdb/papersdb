<?

/**
 * $Id: aicml_publications.php,v 1.10 2008/02/01 20:57:14 loyola Exp $
 *
 * Script that reports the all publications made by AICML PIs, PDFs, students
 * and staff.
 *
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'diag/aicml_pubs_base.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class author_report extends aicml_pubs_base {
    protected $format;
    protected $abstracts;

    public function __construct() {
        parent::__construct('aicml_publications');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);
        
        if (empty($this->format))
	        $this->format = 0;
	        
	    if (empty($this->abstracts))
	        $this->abstracts = 0;
        
        $pubs = array();
        // first get publications by PIs
        foreach (self::$aicml_authors[pi] as $pi_author) {
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
        
        uasort($pubs, array('pdPublication', 'pubsDateSortDesc'));
        krsort($pubs);
        
        // now display the page
        $buttons = array();
        $form = new HTML_QuickForm('aicml_pubs', 'get', 'aicml_publications.php');
        
        $elements[] = HTML_QuickForm::createElement(
        	'advcheckbox', 'format', null, 'Display as HTML', null, array(0, 1));
                
        $elements[] = HTML_QuickForm::createElement(
        	'advcheckbox', 'abstracts', null, 'Show Abstracts', null, array(0, 1));
                
       	$form->addGroup($elements, 'elgroup', '', '&nbsp', false);      	
        $form->addElement('submit', 'update', 'Update');       	
	    	 
       	
        // create a new renderer because $form->defaultRenderer() creates
        // a single copy
        $renderer = new HTML_QuickForm_Renderer_Default();
        $form->accept($renderer);

        echo $renderer->toHtml();
        
        echo '<h2>AICML Publications</h2>';

        if ($this->format)
        	$result = '';
        
        foreach ($pubs as $year => $year_pubs) {
        	foreach ($year_pubs as $pub) {
        		$citation = utf8_encode($this->getCitationHtml($pub));
		        if ($this->format)
        			$result .= $citation . "<br/>\n";
        		else
		    	    echo $citation . '<p/>';
        	}
        }
        
        if ($this->format)
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

    private function getCitationHtml($pub) {
    	$pub->dbLoad($this->db, $pub->pub_id);
        $citation = '';

        // Title
        $citation .= '<b>' . $pub->title. '</b>.<br/>';

        if (count($pub->authors) > 0) {      
        	$authors = array();             
        	foreach ($pub->authors as $auth) {
                $authors[] = $auth->firstname[0] . '. '	. $auth->lastname;
            }
            $citation .= '<i>' . implode(', ', $authors) . '</i>.<br/>';
        }

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
            
        if ($this->abstracts) {
        	// indent and replace all linefeeds with spaces
        	$citation .= '<blockquote>' 
        		. preg_replace('/[\\n\\r]/', ' ', $pub->abstract)
	        	. '</blockquote>';
        }
            
        $citation .= '</span>';

        return $citation;
    }

}

$page = new author_report();
echo $page->toHtml();

?>
