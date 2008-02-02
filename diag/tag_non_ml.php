<?php ;

// $Id: tag_non_ml.php,v 1.1 2008/02/02 18:15:12 loyola Exp $

/**
 * Main page for PapersDB.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'diag/aicml_pubs_base.php';
require_once 'includes/pdPubList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class tag_non_ml extends aicml_pubs_base {
    public function __construct() {
        parent::__construct('tag_non_ml');

        if ($this->loginError) return;
        
        $pubs =& $this->getNonMachineLearningPapers();
        
        debugVar('non ml papers', array_keys($pubs));
    }
    
    protected function getNonMachineLearningPapers() {
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

        // now get publications by all AICML members
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
            $pubs = $this->pubsArrayMerge($pubs, $author_pubs);
        }
        
        foreach ($pubs as $pub_id => $pub) {
            $pub->dbLoad($this->db, $pub_id);

            // only consider machine learning papers
            if (isset($pub->keywords)
                && (strpos(strtolower($pub->keywords), 'machine learning') !== false))
                unset($pubs[$pub_id]);

            // publication must have the category assigned and
            // category must be either 'In Journal' or 'In Conference'
            if (isset($pub->category)  && ($pub->category->cat_id != 1) 
                && ($pub->category->cat_id != 3))
                unset($pubs[$pub_id]);
        }

        uasort($pubs, array('pdPublication', 'pubsDateSortDesc'));
        return $pubs;
    }    
}

$page = new tag_non_ml();
echo $page->toHtml();

?>
