<?php ;

// $Id: tag_non_ml.php,v 1.4 2008/02/04 21:25:46 loyola Exp $

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
        foreach ($pubs as $pub) {
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

        if ($form->validate())
            $this->processForm($form);
        else
            $this->renderForm($form);
    }
    
    private function processForm(&$form) {
        $values =& $form->exportValues();
        debugVar('$values', $values);
    }
    
    private function renderForm(&$form) {
        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<form{attributes}>{content}</form>');
        $renderer->setHeaderTemplate(
            '<table class="stats"><tr><th colspan="2">{header}</th></tr></table>');
            
        $renderer->setGroupElementTemplate('{element}', 'tag_ml_group');
        $renderer->setElementTemplate('<table><tr class="stats">'
	        . '<td style="color: #006633;font-weight: bold;">'
            . '{label}</td><td>{element}</td></tr></table>',
            'tag_ml_group');
        
        $form->accept($renderer);
        $this->renderer =& $renderer;
    }
    
    private function getNonMachineLearningPapers() {
    	$pubs =& $this->getAllAicmlAuthoredPapers();
        
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
