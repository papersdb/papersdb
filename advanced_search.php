<?php ;

// $Id: advanced_search.php,v 1.72 2007/11/02 16:36:28 loyola Exp $

/**
 * Performs advanced searches on publication information in the
 * database.
 *
 * It is mainly only forms, with little data being read from the database. It
 * sends the users input to search_publication_db.php.
 *
 * Uses the Pear library's HTML_QuickForm and HTML_Table to create and
 * display the content.
 *
 * \note Follows coding standards from
 * http://pear.php.net/manual/en/standards.php.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdCategory.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdSearchParams.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class advanced_search extends pdHtmlPage {
    protected $debug = 0;
    protected $cat_list;
    protected $category;
    protected $search;
    protected $cat_id;
    protected $title;
    protected $paper;
    protected $abstract;
    protected $venue;
    protected $keywords;
    protected $authors;
    protected $selected_authors;
    protected $startdate;
    protected $enddate;
    protected $db_authors;

    public function __construct() {
        parent::__construct('advanced_search');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);
        
        $auth_list = new pdAuthorList($this->db);
        $this->db_authors = $auth_list->asFirstLast();

        $this->cat_list = new pdCatList($this->db);

        $this->category = new pdCategory();
        $this->category->dbLoad($this->db, $this->cat_id);

        $form = $this->createForm();
        $this->form =& $form;
        $this->setFormValues();

        // NOTE: order is important here: this must be called after creating
        // the form elements, but before rendering them.
        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $renderer->setElementTemplate(
            '<tr><td><b>{label}</b></td>'
            . '<td><div style="position:relative;text-align:left"><table id="MYCUSTOMFLOATER" class="myCustomFloater" style="font-size:1.1em;position:absolute;top:50px;left:0;background-color:#f4f4f4;display:none;visibility:hidden"><tr><td><div class="myCustomFloaterContent"></div></td></tr></table></div>{element}</td></tr>',
            'authors');

        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    /**
     * Creates the from used on this page. The renderer is then used to
     * display the form correctly on the page.
     */
    public function createForm() {
        $user = null;

        $form = new HTML_QuickForm('advSearchForm', 'get',
                                   'search_publication_db.php',
                                   '_self', 'multipart/form-data');

        $form->addElement('header', null, 'Advanced Search');
        $form->addElement('text', 'title', 'Title:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'venue', 'Venue:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('select', 'cat_id', 'Category:',
                          array('' => '-- All Categories --')
                          + $this->cat_list->list);

        $auth_list = new pdAuthorList($this->db);

        if (($this->access_level > 0) && ($_SESSION['user']->author_id != '')) {
            $user =& $_SESSION['user'];
            unset($auth_list->list[$user->author_id]);
        }


        $form->addElement('textarea', 'authors', 'Authors:',
                          array('cols' => 60,
                                'rows' => 5,
                                'class' => 'wickEnabled:MYCUSTOMFLOATER',
                                'wrap' => 'virtual'));

        $form->addElement('static', null, null,
                          '<span class="small">'
                          . 'There are ' . count($this->db_authors)
                          . ' authors in the database. Type a partial name to '
                          . 'see a list of matching authors. Separate names '
                          . 'using commas.</span>');
                          
        if ($user != null) {
            $form->addElement('advcheckbox', 'author_myself',
                null, 'add me to the search', null, array('', $user->author_id));
        }

        if (isset($_SESSION['user'])
            && ($_SESSION['user']->showInternalInfo())) {
            // rankings selections
            $rankings = pdPublication::rankingsGlobalGet($this->db);
            foreach ($rankings as $rank_id => $description) {
                $radio_rankings[] = HTML_QuickForm::createElement(
                    'advcheckbox', 'paper_rank[' . $rank_id . ']', null,
                    $description, null, array('', 'yes'));
            }
            $radio_rankings[] = HTML_QuickForm::createElement(
                'static', 'paper_ranking_label', null, 'other', -1);
            $radio_rankings[] = HTML_QuickForm::createElement(
                'text', 'paper_rank_other', null,
                array('size' => 30, 'maxlength' => 250));

            $form->addGroup($radio_rankings, 'group_rank', 'Ranking:', '<br/>',
                            false);

            // collaborations radio selections
            $collaborations = pdPublication::collaborationsGet($this->db);

            foreach ($collaborations as $col_id => $description) {
                $radio_cols[] = HTML_QuickForm::createElement(
                    'advcheckbox', 'paper_col[' . $col_id . ']', null,
                    $description, null, array('', 'yes'));
            }

            $form->addGroup($radio_cols, 'group_collaboration',
                            'Collaboration:', '<br/>', false);
        }

        $form->addElement('text', 'paper', 'Paper filename:',
                          array('size' => 60, 'maxlength' => 250));
        $form->addElement('text', 'abstract', 'Abstract:',
                          array('size' => 60, 'maxlength' => 250));

        $kwElement[0] =& HTML_QuickForm::createElement(
            'text', 'keywords', null,
            array('size' => 60, 'maxlength' => 250));
        $kwElement[1] =& HTML_QuickForm::createElement(
            'static', 'auth_label', null,
            '<span class="small">seperate using semi-colon (;)</span>');
        $form->addGroup($kwElement, 'keywordsGroup', 'Keywords:', '<br/>',
                        false);

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'date', 'startdate', 'Start Date:',
                    array('format' => 'YM', 'minYear' => '1970')),
                HTML_QuickForm::createElement('static', null, null, 'and'),
                HTML_QuickForm::createElement(
                    'date', 'enddate', 'End Date:',
                    array('format' => 'YM', 'minYear' => '1970')),
                ),
            null, 'Published Between:', '&nbsp;', false);

        $form->addElement(
                'advcheckbox', 'show_internal_info',
                'Options:', 'show internal information', null, 
        		array('no', 'yes'));
            
        $form->addGroup(
            array(
                HTML_QuickForm::createElement('reset', 'Clear', 'Clear'),
                HTML_QuickForm::createElement(
                    'button', 'fill_last', 'Load Previous Search Terms',
                    array('onClick' => 'lastSearchUse();')),
                HTML_QuickForm::createElement('submit', 'Submit', 'Search')
                ),
            'buttonsGroup', '', '&nbsp;', false);
                
        return $form;
    }

    /**
     * Assigns the form's values as per the HTTP GET string.
     */
    public function setFormValues() {
        $defaults = array(
            'search'     => $this->search,
            'cat_id'     => $this->cat_id,
            'title'      => $this->title,
            'paper'      => $this->paper,
            'abstract'   => $this->abstract,
            'venue'      => $this->venue,
            'keywords'   => $this->keywords,
            'startdate'  => array('Y' => $this->startdate['Y'],
                                  'M' => $this->startdate['M']),
            'enddate'    => array('Y' => $this->enddate['Y'],
                                  'M' => $this->enddate['M']));
        
        if (count($this->authors) > 0) {
            foreach ($this->authors as $author)
                $auth_names[] = $author->firstname . ' ' . $author->lastname;
            $defaults['authors'] = implode(', ', $auth_names);
        }

        if (empty($this->enddate)
            || (empty($this->enddate['Y']) && ($this->enddate['M']))) {
            $defaults['enddate']['Y'] = date('Y');
            $defaults['enddate']['M'] = date('m');
        }

        $this->form->setConstants($defaults);
    }

    /**
     * Outputs the java script used by the page.
     */
    public function javascript() {
        if (isset($_SESSION['search_params']))
            $sp = $_SESSION['search_params'];
        else
            $sp = new pdSearchParams();
            
        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        // WICK
        $this->js .= "\ncollection="
            . convertArrayToJavascript($this->db_authors, false)
            . "\n";

        $js_file = FS_PATH . '/js/advanced_search.js';
        assert('file_exists($js_file)');
        $content = file_get_contents($js_file);

        $this->js .= str_replace(array('{host}',
                                       '{self}',
                                       '{selected_authors}',
                                       '{cat_id}',
                                       '{title}',
                                       '{authors}',
                                       '{paper}',
                                       '{abstract}',
                                       '{venue}',
                                       '{keywords}',
                                       '{paper_rank_other}',
                                       '{startdateY}',
                                       '{startdateM}',
                                       '{enddateY}',
                                       '{enddateM}',
                                       '{paper_rank1}',
                                       '{paper_rank2}',
                                       '{paper_rank3}',
                                       '{paper_rank4}',
                                       '{paper_col1}',
                                       '{paper_col2}',
                                       '{paper_col3}',
                                       '{paper_col4}',
                                       '{author_myself}',
								       '{show_internal_info}'),
                                 array($_SERVER['HTTP_HOST'],
                                       $_SERVER['PHP_SELF'],
                                       $this->selected_authors,
                                       $sp->cat_id,
                                       $sp->title,
                                       $sp->authors,
                                       $sp->paper,
                                       $sp->abstract,
                                       $sp->venue,
                                       $sp->keywords,
                                       $sp->paper_rank_other,
                                       $sp->startdate['Y'],
                                       $sp->startdate['M'],
                                       $sp->enddate['Y'],
                                       $sp->enddate['M'],
                                       ($sp->paper_rank[1] == 'yes'),
                                       ($sp->paper_rank[2] == 'yes'),
                                       ($sp->paper_rank[3] == 'yes'),
                                       ($sp->paper_rank[4] == 'yes'),
                                       ($sp->paper_col[1] == 'yes'),
                                       ($sp->paper_col[2] == 'yes'),
                                       ($sp->paper_col[3] == 'yes'),
                                       ($sp->paper_col[4] == 'yes'),
                                       ($sp->author_myself != ''),
                                       ($sp->show_internal_info == 'yes')),
                                 $content);

        $js_file = FS_PATH . '/js/wick.js';
        assert('file_exists($js_file)');
        $content = file_get_contents($js_file);

        $this->js .= str_replace(array('{host}', '{self}', '{new_location}'),
                                 array($_SERVER['HTTP_HOST'],  
                                       $_SERVER['PHP_SELF'],
                                       $url),
                                 $content);
    }
}

$page = new advanced_search();
echo $page->toHtml();

?>


