<?php

 /**
  * $Id: aicml_stats.php,v 1.5 2008/02/07 22:35:15 loyola Exp $
  *
  * Script that reports statistics for thepublications made by AICML PIs, PDFs,
  * students and staff.
  *
  * @package PapersDB
  */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'diag/aicml_pubs_base.php';
require_once 'includes/pdPublication.php';

/**
 * Base class for pages that display information about the Centre's 
 * Machine Learning papers.
 *
 * @package PapersDB
 */
class author_report extends aicml_pubs_base {
	protected $cvs_output;
    protected $stats = array(
        'pi'       => array(),  // publications for PIs combined
        'per_pi'   => array(),  // publications per individual PI
        'staff'    => array(),  // publications by staff 
        'fy_count' => array()   // publication counts by PIs and staff
    );

    public function __construct() {
        parent::__construct('aicml_stats');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);
        
        if (isset($this->cvs_output)) {
        	assert('isset($_SESSION["aicml_stats"])');
        	$this->stats =& $_SESSION['aicml_stats'];
        	return;
        }

        $pubs =& $this->getMachineLearningPapers();
        
        // populate $this->aicml_pi_authors
        $this->getPiAuthors(); 
        
        // populate $this->aicml_pdf_students_staff_authors
        $this->getPdfStudentsAndStaffAuthors(); 
        
        $this->collectStats($pubs);
        $_SESSION['aicml_stats'] =& $this->stats;
        
        $form = new HTML_QuickForm('aicml_stats', 'get', 'aicml_stats.php');
        
        $elements = array();     	
        $form->addElement('submit', 'cvs_output', 'Export to CVS');
       	
        // create a new renderer because $form->defaultRenderer() creates
        // a single copy
        $renderer = new HTML_QuickForm_Renderer_Default();
        $form->accept($renderer);

        echo $renderer->toHtml();
        
        echo $this->allPiPublicationTable();
        echo $this->fiscalYearTotalsTable('pi', 'PI Fiscal Year Totals');
                      
        foreach ($this->aicml_pi_authors as $pi_author) {
            echo $this->piPublicationsTable($pi_author);
        }
        
        echo $this->staffPublicationsTable();
        echo $this->fiscalYearTotalsTable('staff', 'Staff Fiscal Year Totals');
        echo $this->studentTotalsTable();
    }
    
    // collect stats for all machine learning papers.
    private function collectStats(&$pubs) {
    	assert('isset($this->aicml_pi_authors)');
    	assert('isset($this->aicml_pdf_students_staff_authors)');
    	
        $this->stats['fy_pubs'] = array();
        
    	foreach ($pubs as $pub_id => &$pub) {
        	$pub->dbLoad($this->db, $pub_id,
        		pdPublication::DB_LOAD_VENUE
        		| pdPublication::DB_LOAD_CATEGORY
        		| pdPublication::DB_LOAD_AUTHOR_FULL);
        		        		
            $isT1 = ($pub->rank_id == 1) ? 'Y' : 'N';
            $fy   = $this->getFiscalYearKey($pub->published);
        	
            //if ($pub_id == 906)
        	//	debugVar('$isT1', $isT1);
        	
            if (!isset($this->stats['fy_pubs'][$fy]))
            	$this->stats['fy_pubs'][$fy] = array();
            	
            $this->stats['fy_pubs'][$fy][$pub_id] = true;
            
        	$pub_pi_authors = array_intersect_key(
        		$pub->authorsToArray(), $this->aicml_pi_authors);
            
        	foreach ($pub_pi_authors as $author_id => $author_name) {
        		// make sure publication published while at AICML
        		$published_stamp = date2Timestamp($pub->published);
        		if (($published_stamp < $this->aicml_pi_dates[$author_id][0])
        			|| (($this->aicml_pi_dates[$author_id][1] > 0)
        				&& ($published_stamp > $this->aicml_pi_dates[$author_id][1])))
        			unset($pub_pi_authors[$author_id]);
        	}

        	if (count($pub_pi_authors) > 0) {        		
        		$names = implode('; ', $pub_pi_authors);
        		if (!isset($this->stats['pi'][$fy][$isT1][$names]))
        			$this->stats['pi'][$fy][$isT1][$names] = array();
        		array_push($this->stats['pi'][$fy][$isT1][$names], $pub->pub_id);

        		foreach ($pub_pi_authors as $author_id => $author_name) {
	        		if (!isset($this->stats['per_pi'][$author_name][$fy][$isT1][$names]))
    	    			$this->stats['per_pi'][$author_name][$fy][$isT1][$names] = array();
        			array_push(
        				$this->stats['per_pi'][$author_name][$fy][$isT1][$names],
        				$pub->pub_id);
        		}
        	}

        	$pub_staff_authors = array_intersect_key(
        		$pub->authorsToArray(), $this->aicml_pdf_students_staff_authors);
        
        	foreach ($pub_staff_authors as $author_id => $author_name) {
        		// make sure publication published while at AICML
        		$published_stamp = date2Timestamp($pub->published);
        		if (($published_stamp < $this->aicml_pdf_students_staff_dates[$author_id][0])
        			|| (($this->aicml_pdf_students_staff_dates[$author_id][1] > 0)
        				&& ($published_stamp > $this->aicml_pdf_students_staff_dates[$author_id][1]))) {
        					
        			unset($pub_staff_authors[$author_id]);
        		}
        	}
        	
        	if (count($pub_staff_authors) > 0) {   
                $names  = implode('; ', $pub_staff_authors);
        		if (count($pub_pi_authors) > 0)        		
        			$names = implode('; ', $pub_pi_authors) . '; ' . $names;
                
                if (!isset($this->stats['staff'][$fy][$isT1][$names]))
                    $this->stats['staff'][$fy][$isT1][$names] = array();
                array_push($this->stats['staff'][$fy][$isT1][$names], 
                           $pub->pub_id);
            }
        }
        krsort($this->stats);
        krsort($this->stats['per_pi']);
        
        // get totals
        foreach (array('pi', 'staff') as $group) {
            if (!isset($this->stats['fy_count'][$group])) {
                $this->stats['fy_count'][$group] = array(
                    'all' => 0,
                	'N'   => 0, 
                	'Y'   => 0);
            }
            
            foreach ($this->stats[$group] as $fy => $subarr1) {
                if (!isset($this->stats['fy_count'][$group][$fy]))
                     $this->stats['fy_count'][$group][$fy] = array(
                        'all' => 0,
                        'N' => 0, 
                     	'Y' => 0);
                     
                foreach ($subarr1 as $t1 => $subarr2) {
                    foreach ($subarr2 as $authors => $pub_ids) {
                        $this->stats['fy_count'][$group][$fy][$t1] += count($pub_ids);
                    }
                    $this->stats['fy_count'][$group][$t1] 
                        += $this->stats['fy_count'][$group][$fy][$t1];
                    $this->stats['fy_count'][$group][$fy]['all'] 
                        += $this->stats['fy_count'][$group][$fy][$t1];
                    $this->stats['fy_count'][$group]['all'] 
                        += $this->stats['fy_count'][$group][$fy][$t1];
                }
            }
        }
    }

    private function statsToHtmlTable(&$table, $group) {   
        assert('isset($this->stats[$group])');
        $row_count = 0;
        foreach ($this->stats[$group] as $fy => $subarr1) {
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
                    
                    $class = ($t1 == 'Y') ? 'stats_odd' : 'stats_even';
                    $table->addRow(array(self::$fiscal_years[$fy][0], 
                                         $t1, $authors, count($pub_ids),
                                         implode(', ', $pub_links)),
                                   array('class' => $class));
                    ++$row_count;
                    $table->updateCellAttributes($row_count, 4,
                        array('class' => $class . '_pub_id'), NULL);
                }
            }
        }
        
        return '<h3>Machine Learning Publications by Principal Investigators</h3>'
            . $table->toHtml();
    }
    
    private function allPiPublicationTable() {
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Fiscal Year Start', 'T1', 'Author(s)',
                             'Num Pubs', 'Pub Ids'));
        $table->setRowType(0, 'th');
        $this->statsToHtmlTable($table, 'pi');
        
        return '<h3>Machine Learning Publications by Principal Investigators</h3>'
            . $table->toHtml();
    }
    
    private function fiscalYearTotalsTable($group, $heading) {    
        assert(isset($this->stats[$group]));
              
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Fiscal Year Start', 'T1', 'Num Pubs'));
        $table->setRowType(0, 'th');
        foreach ($this->stats[$group] as $fy => $subarr1) {
            krsort($subarr1);
            foreach ($subarr1 as $t1 => $subarr2) {
                ksort($subarr2);                   
                $class = ($t1 == 'Y') ? 'stats_odd' : 'stats_even';
                $table->addRow(array(self::$fiscal_years[$fy][0],
                    $t1, $this->stats['fy_count'][$group][$fy][$t1]),
                    array('class' => $class));
            }
        }
        $table->addRow(array('<b>TOTAL</b>', 'Y', 
                             $this->stats['fy_count'][$group]['Y'] ),
                       array('class' => 'stats_odd'));
        $table->addRow(array('<b>TOTAL</b>', 'N', 
                             $this->stats['fy_count'][$group]['N'] ),
                       array('class' => 'stats_even'));
                             
        return '<h3>' . $heading . '</h3>'. $table->toHtml();
    }
    
    // 
    private function studentTotalsTable() {              
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Fiscal Year Start', 'Student Pubs', 'Centre Pubs', 'Ratio (%)'));
        $table->setRowType(0, 'th');
        foreach ($this->stats['fy_count']['staff'] as $fy => $subarr1) {
            if (!is_numeric($fy)) continue;
            
            $student_pubs = $this->stats['fy_count']['staff'][$fy]['all'];
            $all_pubs = count($this->stats['fy_pubs'][$fy]);
            
            $ratio = 0;
            if ($all_pubs != 0)
                $ratio = 100 * $student_pubs / $all_pubs;
                
            $table->addRow(array(self::$fiscal_years[$fy][0],
                                 $student_pubs, $all_pubs, round($ratio, 2)),
                           array('class' => 'stats_odd'));
        }
                             
        return '<h3>Student to PI Publication Ratio</h3>'. $table->toHtml();
    }
    
    private function piPublicationsTable($pi_name) {
        $table = new HTML_Table(array('class' => 'stats'));
        
        $table->addRow(array('Fiscal Year Start', 'T1', 'Author(s)',
                             'Num Pubs', 'Pub Ids'));
        $table->setRowType(0, 'th');
        
        $row_count = 0;
        $pub_count = array('N' => 0, 'Y' => 0);
        foreach ($this->stats['per_pi'][$pi_name] as $fy => $subarr1) {
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
                    
                    $class = ($t1 == 'Y') ? 'stats_odd' : 'stats_even';
                    
                    $table->addRow(array(self::$fiscal_years[$fy][0], 
                                         $t1, $authors, count($pub_ids),
                                         implode(', ', $pub_links)),
                                   array('class' => $class));
                    ++$row_count;
                    $table->updateCellAttributes($row_count, 4,
                        array('class' => $class . '_pub_id'), NULL);
                    
                    $pub_count[$t1] += count($pub_ids);
                }
            }
        }
        $table->addRow(array('<b>TOTAL</b>', 'Y', null, $pub_count['Y'], null),
                             array('class' => 'stats_odd'));
        $table->addRow(array('<b>TOTAL</b>', 'N', null, $pub_count['N'], null),
                             array('class' => 'stats_even'));
        echo '<h3>', $pi_name, '</h3>', "\n", $table->toHtml();
    }
    
    private function staffPublicationsTable() {    
        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Fiscal Year Start', 'T1', 'Author(s)',
                             'Num Pubs', 'Pub Ids'));
        $table->setRowType(0, 'th');
        $this->statsToHtmlTable($table, 'staff');
        return '<h3>Staff Machine Learning Papers</h3>' . $table->toHtml();
    }
    
    /**
     * Allows the user to save this report as a CSV file.
     *
     * The base class uses output buffering, so we have to get the contents
     * of the buffer before we output the CVS content.
     */
    public function toCsv() {
    	$this->statsToCsv('pi',
    		"Machine Learning Publications by Principal Investigators");
    	$this->statsToCsv('staff',
    		"Staff Machine Learning Papers");
		$this->staffPublicationsTableCsv();
		
    	if (ob_get_length() > 0) {
            $csv_output .= ob_get_contents();
            ob_end_clean();
        }		
    	$size_in_bytes = strlen($csv_output);		
		header("Content-disposition:  attachment; filename=" .
			date("Y-m-d").".csv; size=$size_in_bytes");
		echo $csv_output;
    }
    
    private function statsToCsv($group, $heading) {    
        assert('isset($this->stats[$group])');
        echo $heading, "\n",
        	implode(',', array('Fiscal Year Start', 'T1', 'Author(s)',
                               'Num Pubs', 'Pub Ids')), "\n";
        foreach ($this->stats[$group] as $fy => $subarr1) {
            krsort($subarr1);
            foreach ($subarr1 as $t1 => $subarr2) {
                ksort($subarr2);
                foreach ($subarr2 as $authors => $pub_ids) {
                    rsort($pub_ids);
                    
                    echo implode(',',
                    	array(self::$fiscal_years[$fy][0],
                              $t1, 
                              '"' . $authors . '"', 
                              count($pub_ids),
                              '"' . implode(', ', $pub_ids) . '"')),
                        "\n";
                }
            }
        }
        echo "\n";
    }
    
    private function fiscalYearTotalsCsv($group, $heading) {    
        assert('isset($this->stats[$group])');
        
        echo $heading, "\n";
                      
        foreach ($this->stats[$group] as $fy => $subarr1) {
            krsort($subarr1);
            foreach ($subarr1 as $t1 => $subarr2) {
                ksort($subarr2); 
                echo implode(',', 
                	array(self::$fiscal_years[$fy][0],
                    	$t1, $this->stats['fy_count'][$group][$fy][$t1])),
                    "\n";
            }
        }
    }
}

$page = new author_report();

if (isset($_GET['cvs_output']))
	$page->toCsv();
else	
	echo $page->toHtml();

?>
