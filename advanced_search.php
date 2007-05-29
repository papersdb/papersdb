<?php ;

// $Id: advanced_search.php,v 1.61 2007/05/29 19:56:11 aicmltec Exp $

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
require_once 'includes/jscalendar.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class advanced_search extends pdHtmlPage {
    var $debug = 0;
    var $cat_list;
    var $category;
    var $search;
    var $cat_id;
    var $title;
    var $authortyped;
    var $paper;
    var $abstract;
    var $venue;
    var $keywords;
    var $authorselect;
    var $selected_authors;
    var $startdate;
    var $enddate;

    function advanced_search() {
        parent::pdHtmlPage('advanced_search');

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        $this->cat_list = new pdCatList($this->db);

        $this->category = new pdCategory();
        $this->category->dbLoad($this->db, $this->cat_id);

        $form = $this->createForm();
        $this->form =& $form;
        $this->setFormValues();

        if (isset($_SESSION['search_params'])
            && (count($_SESSION['search_params']->authorselect) > 0))
            $this->selected_authors = ':'
                . implode(':', $_SESSION['search_params']->authorselect)
                . ':';


        // NOTE: order is important here: this must be called after creating
        // the form elements, but before rendering them.
        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    /**
     * Creates the from used on this page. The renderer is then used to
     * display the form correctly on the page.
     *
     * Note: jscalendar.php is used as a shorcut way of entering date values.
     */
    function createForm() {
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

        $authElements[] =& HTML_QuickForm::createElement(
            'text', 'authortyped', null,
            array('size' => 60, 'maxlength' => 250));
        $authElements[] =& HTML_QuickForm::createElement(
            'static', 'auth_label', null, 'or select from list');
        $authElements[] =& HTML_QuickForm::createElement(
            'select', 'authorselect', null, $auth_list->list,
            array('multiple' => 'multiple', 'size' => 10));

        if ($user != null) {
            $authElements[] =& HTML_QuickForm::createElement(
                'advcheckbox', 'author_myself',
                null, 'myself', null, array('', $user->author_id));
        }

        $form->addGroup($authElements, 'authors', 'Authors:', '<br/>',
                        false);

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
                    array('format' => 'YM', 'minYear' => '1985')),
                HTML_QuickForm::createElement('static', null, null, 'and'),
                HTML_QuickForm::createElement(
                    'date', 'enddate', 'End Date:',
                    array('format' => 'YM', 'minYear' => '1985')),
                ),
            null, 'Published Between:', '&nbsp;', false);

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
    function setFormValues() {
        $defaults = array(
            'search'     => $this->search,
            'cat_id'     => $this->cat_id,
            'title'      => $this->title,
            'authortyped'=> $this->authortyped,
            'paper'      => $this->paper,
            'abstract'   => $this->abstract,
            'venue'      => $this->venue,
            'keywords'   => $this->keywords,
            'startdate'  => array('Y' => $this->startdate['Y'],
                                  'M' => $this->startdate['M']),
            'enddate'    => array('Y' => $this->enddate['Y'],
                                  'M' => $this->enddate['M']));

        if (count($this->authorselect) > 0)
            $defaults['authorselect'] =& $this->authorselect;

        $this->form->setConstants($defaults);
    }

    /**
     * Outputs the java script used by the page.
     */
    function javascript() {
        if (isset($_SESSION['search_params']))
            $sp = $_SESSION['search_params'];
        else
            $sp = new pdSearchParams();

        $js_file = FS_PATH . '/js/advanced_search.js';
        assert('file_exists($js_file)');
        $content = file_get_contents($js_file);

        $this->js .= str_replace(array('{host}',
                                       '{self}',
                                       '{selected_authors}',
                                       '{cat_id}',
                                       '{title}',
                                       '{authortyped}',
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
                                       '{author_myself}'),
                                 array($_SERVER['HTTP_HOST'],
                                       $_SERVER['PHP_SELF'],
                                       $this->selected_authors,
                                       $sp->cat_id,
                                       $sp->title,
                                       $sp->authortyped,
                                       $sp->paper,
                                       $sp->abstract,
                                       $sp->venue,
                                       $sp->keywords,
                                       $sp->paper_rank_other,
                                       $sp->startdate['Y'],
                                       $sp->startdate['M'],
                                       $sp->enddate['Y'],
                                       $sp->enddate['M'],
                                       $sp->paper_rank[1],
                                       $sp->paper_rank[2],
                                       $sp->paper_rank[3],
                                       $sp->paper_rank[4],
                                       $sp->paper_col[1],
                                       $sp->paper_col[2],
                                       $sp->paper_col[3],
                                       $sp->paper_col[4],
                                       $sp->author_myself),
                                 $content);
    }
}

$page = new advanced_search();
echo $page->toHtml();

?>


