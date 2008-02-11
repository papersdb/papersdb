<?php ;

// $Id: tag_non_ml.php,v 1.7 2008/02/11 22:57:00 loyola Exp $

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
 * For each AICML fiscal year, this script shows the publication entries that
 * have not been tagged as "machine learning" papers and allows the user
 * to quickly tag the papers that are actually are "machine learning" papers.
 *
 * @package PapersDB
 */
class tag_non_ml extends aicml_pubs_base {
    protected $submit;
    
    public function __construct() {
        parent::__construct('tag_non_ml');

        if ($this->loginError) return;
        
        $this->loadHttpVars();
        
        $pubs =& $this->getNonMachineLearningPapers();
        
        $form = new HTML_QuickForm('tag_non_ml_form', 'post', './tag_ml_submit.php');
        $form->addElement('header', null, 'Citation</th><th style="width:7%">Is ML');
        
        $count = 0;
        foreach ($pubs as &$pub) {
        	$pub->dbLoad($this->db, $pub->pub_id,
        		pdPublication::DB_LOAD_VENUE
        		| pdPublication::DB_LOAD_CATEGORY
        		| pdPublication::DB_LOAD_AUTHOR_FULL);
            ++$count;
        	$form->addGroup(array(
                HTML_QuickForm::createElement('static', null, null,
                    $pub->getCitationHtml() . '&nbsp;'
                    . $this->getPubIcons($pub, 0x7)),
                HTML_QuickForm::createElement('advcheckbox', 
                	'pub_tag[' . $pub->pub_id . ']',
        			null, null, null, array('no', 'yes'))
        		),
        		'tag_ml_group', $count, '</td><td>', false);
        }
        
        $form->addElement('submit', 'submit', 'Submit');
        
        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            "<form{attributes}>\n{content}\n</form>\n");
        $renderer->setHeaderTemplate(
            "<table class=\"stats\">\n<tr><th colspan=\"2\">{header}</th></tr>\n</table>\n");
            
        $renderer->setGroupElementTemplate("{element}\n", 'tag_ml_group');
        
        // set default template for all elements
        $renderer->setElementTemplate("<table>\n<tr>"
	        . "<td>{label}</td><td>{element}</td></tr>\n</table>\n");
	        
	    // set template for this single element
        $renderer->setElementTemplate("<table>\n<tr class=\"stats\">"
	        . '<td style="color: #006633;font-weight: bold;">'
            . "{label}</td><td>{element}</td></tr>\n</table>\n",
            'tag_ml_group');
        
        $form->accept($renderer);
        $this->renderer =& $renderer;
    }
    
    private function getNonMachineLearningPapers() {       
        $q = $this->db->query('select distinct(publication.pub_id),
 publication.title, publication.paper, publication.abstract, 
 publication.keywords, publication.published, publication.venue_id, 
 publication.extra_info, publication.submit, publication.user, 
 publication.rank_id, publication.updated       
 from publication 
 inner join  pub_author on pub_author.pub_id=publication.pub_id 
 inner join aicml_staff on aicml_staff.author_id=pub_author.author_id
 inner join pub_cat on publication.pub_id=pub_cat.pub_id
 where keywords not rlike "mach.*learn.*" 
 and pub_cat.cat_id in (1, 3)
 and publication.published >= "' . self::$fiscal_years[0][0]. '"');
        if (!$q) return false;
        
        $pubs = array();
        $r = $this->db->fetchObject($q);
        while ($r) {
        	$pub = new pdPublication($r);
        	$pubs[$r->pub_id] = $pub;
        	$r = $this->db->fetchObject($q);
        }

        uasort($pubs, array('pdPublication', 'pubsDateSortDesc'));
        return $pubs;
    }    
}

$page = new tag_non_ml();
echo $page->toHtml();

?>
