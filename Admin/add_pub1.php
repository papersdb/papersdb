<?php ;

// $Id: add_pub1.php,v 1.39 2007/04/27 22:15:52 aicmltec Exp $

/**
 * This page is the form for adding/editing a publication.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requires the base class and classes to access the database. */
require_once 'Admin/add_pub_base.php';
require_once 'includes/pdAuthorList.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdPublication.php';
require_once 'includes/pdPubList.php';
require_once 'includes/pdExtraInfoList.php';
require_once 'includes/authorselect.php';
require_once 'includes/pdAttachmentTypesList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub1 extends add_pub_base {
    var $debug = 0;
    var $cat_venue_options;
    var $category_list;

    function add_pub1() {
        parent::add_pub_base();

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);

        if (isset($_SESSION['pub'])) {
            // according to session variables, we are already editing a
            // publication
            $this->pub =& $_SESSION['pub'];
        }
        else if ($this->pub_id != '') {
            // pub_id passed in with $_GET variable
            $this->db = dbCreate();
            $this->pub = new pdPublication();
            $result = $this->pub->dbLoad($this->db, $this->pub_id);
            if (!$result) {
                $this->pageError = true;
                return;
            }

            $_SESSION['pub'] =& $this->pub;
        }
        else {
            // create a new publication
            $this->pub = new pdPublication();
            $_SESSION['pub'] =& $this->pub;
        }

        if ($this->pub->pub_id != '')
            $this->page_title = 'Edit Publication';

        $form = new HTML_QuickForm('add_pub1');
        $form->addElement('header', null, 'Add Publication');

        // title
        $form->addElement('text', 'title',
                          $this->helpTooltip('Title', 'titleHelp') . ':',
                          array('size' => 60, 'maxlength' => 250));
        $form->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        // category
        $this->category_list = new pdCatList($this->db);
        $form->addElement(
            'select', 'cat_id',
            $this->helpTooltip('Category', 'categoryHelp') . ':',
            array('' => '--- Please Select a Category ---')
            + $this->category_list->list,
            array('onchange' => 'catVenueSwapOptions(this.form);'));


        // Venue
        $form->addElement('select', 'venue_id',
                          $this->helpTooltip('Venue', 'venueHelp') . ':', null,
                          array('style' => 'width: 70%;'));

        $form->addElement('submit', 'add_venue', 'Add New Venue');

        $form->addElement('textarea', 'abstract',
                          $this->helpTooltip('Abstract', 'abstractHelp')
                          . ':<br/><div class="small">HTML Enabled</div>',
                          array('cols' => 60, 'rows' => 10));

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'keywords', null,
                    array('size' => 60, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span class="small">separate using semi-colons (;)</span>')),

            'kwgroup', $this->helpTooltip('Keywords', 'keywordsHelp') . ':',
            '<br/>', false);

        // rankings radio selections
        $rankings = pdPublication::rankingsGlobalGet($this->db);
        foreach ($rankings as $rank_id => $description) {
            $radio_rankings[] = HTML_QuickForm::createElement(
                'radio', 'paper_rank', null, $description, $rank_id);
        }
        $radio_rankings[] = HTML_QuickForm::createElement(
            'radio', 'paper_rank', null,
            'other (fill in box below)', -1);
        $radio_rankings[] = HTML_QuickForm::createElement(
            'text', 'paper_rank_other', null,
            array('size' => 30, 'maxlength' => 250));

        $form->addGroup($radio_rankings, 'group_rank', 'Ranking:', '<br/>',
                        false);

        // collaborations radio selections
        $collaborations = pdPublication::collaborationsGet($this->db);

        foreach ($collaborations as $col_id => $description) {
            $radio_cols[] = HTML_QuickForm::createElement(
                'checkbox', 'paper_col[' . $col_id . ']', null, $description,
                1);
        }

        $form->addGroup($radio_cols, 'group_collaboration',
                        'Collaboration:', '<br/>', false);

        $form->addElement('textarea', 'user',
                          $this->helpTooltip('User Info:', 'userInfoHelp'),
                          array('cols' => 60, 'rows' => 2));

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $form->addElement('date', 'pub_date', 'Date:',
                          array('format' => 'YM', 'minYear' => '1985'));

        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "cancelConfirm();"));
        $buttons[] = HTML_QuickForm::createElement(
            'reset', 'reset', 'Reset');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'next', 'Next step >>');

        if ($this->pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->db =& $this->db;
        $this->form =& $form;

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array('title'    => $this->pub->title,
                          'abstract' => $this->pub->abstract,
                          'keywords' => $this->pub->keywords,
                          'user'     => $this->pub->user);

        if (is_object($this->pub->category)) {
            $defaults['cat_id'] = $this->pub->category->cat_id;
        }

        if (isset($this->pub->rank_id)) {
            $defaults['paper_rank'] = $this->pub->rank_id;
            if ($this->pub->rank_id == -1)
                $defaults['paper_rank_other'] = $this->pub->ranking;
        }

        if (is_array($this->pub->collaborations)
            && (count($this->pub->collaborations) > 0)) {
            foreach ($this->pub->collaborations as $col_id) {
                $defaults['paper_col'][$col_id] = 1;
            }
        }

        if (is_object($this->pub->venue)) {
            $defaults['venue_id'] = $this->pub->venue->venue_id;
        }

        if (!isset($this->pub->published) || ($this->pub->published == '')) {
            $defaults['pub_date'] = array('Y' => date('Y'), 'M' => date('m'));
        }
        else {
            $date = explode('-', $this->pub->published);

            $defaults['pub_date']['Y'] = $date[0];
            $defaults['pub_date']['M'] = $date[1];
        }

        $this->form->setDefaults($defaults);

        if (isset($_SESSION['pub']) && ($_SESSION['pub']->title != '')) {
            $this->pub =& $_SESSION['pub'];

            if (isset($this->pub->pub_id))
                echo '<h3>Editing Publication Entry</h3>';
            else
                echo '<h3>Adding Publication Entry</h3>';

            echo $this->pub->getCitationHtml('..', false) . '<p/>'
                . add_pub_base::similarPubsHtml();
        }

        $renderer =& $this->form->defaultRenderer();

        $this->form->setRequiredNote(
            '<font color="#FF0000">*</font> shows the required fields.');

        $renderer->setFormTemplate(
            '<table width="100%" bgcolor="#CCCC99">'
            . '<form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');
        $renderer->setGroupTemplate(
            '<table><tr>{content}</tr></table>', 'name');

        $renderer->setElementTemplate(
            '<tr><td colspan="2">{label}</td></tr>',
            'categoryinfo');

        $this->form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');
        $form =& $this->form;

        $values = $form->exportValues();

        $this->pub->load($values);
        $this->pub->published = $values['pub_date']['Y'] . '-'
            .  $values['pub_date']['M'] . '-1';
        $_SESSION['state'] = 'pub_add';

        if ((!empty($values['cat_id'])) && ($values['cat_id'] > 0))
            $this->pub->addCategory($this->db, $values['cat_id']);
        else if (is_object($this->pub->category)) {
            unset($this->pub->category);
            unset($this->pub->info);
        }

        if ((!empty($values['venue_id'])) && ($values['venue_id'] > 0))
            $this->pub->addVenue($this->db, $values['venue_id']);
        else if (is_object($this->pub->venue)) {
            unset($this->pub->venue);
            unset($this->pub->venue_id);
        }

        if (isset($values['paper_rank']))
            $this->pub->rank_id = $values['paper_rank'];

        if (isset($values['paper_rank']) && ($values['paper_rank'] == -1)
            && (strlen($values['paper_rank_other']) > 0)) {
            $this->pub->rank_id = -1;
            $this->pub->ranking = $values['paper_rank_other'];
        }

        if (isset($values['paper_col'])
            && (count($values['paper_col']) > 0)) {
            $this->pub->collaborations = array_keys($values['paper_col']);
        }

        $result = $this->pub->duplicateTitleCheck($this->db);
        if (count($result) > 0)
            $_SESSION['similar_pubs'] = $result;

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('pub', $this->pub);
            return;
        }

        if (isset($values['add_venue']))
            header('Location: add_venue.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub2.php');
    }

    function javascript() {
        foreach ($this->category_list->list as $cat_id => $category) {
            unset($vlist);
            $v_cat_id = $cat_id;

            switch ($category) {
                case 'In Conference':
                    $vlist = new pdVenueList($this->db,
                                             array('type' => 'Conference',
                                                   'concat' => true));
                    break;

                case 'In Journal':
                    $vlist = new pdVenueList($this->db,
                                          array('type' => 'Journal',
                                                'concat' => true));
                    break;

                case 'In Workshop':
                    $vlist = new pdVenueList($this->db,
                                             array('type' => 'Workshop',
                                                   'concat' => true));
                    break;

                default:
                    $v_cat_id = 0;
                    if (!isset($venues[0])) {
                        $vlist = new pdVenueList($this->db,
                                                 array('concat' => true));
                    }
                    break;
            }

            if (isset($vlist)) {
                //debugVar('vlist', $vlist->list);

                $venues[$v_cat_id][''] = array('--Select Venue--', '1');
                $venues[$v_cat_id]['-1'] = array('--No Venue--', '1');

                foreach ($vlist->list as $venue_id => $name) {
                    $venues[$v_cat_id][$venue_id] = array($name, 0);
                }
            }
        }

        $js_files = array(FS_PATH . '/Admin/js/add_pub1.js',
                          FS_PATH . '/Admin/js/add_pub_cancel.js');

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $js_array = $this->convertArrayToJavascript($venues, true);
        if (is_object($this->pub->venue))
            $venue_default = $this->pub->venue->venue_id;
        else
            $venue_default = 0;

        foreach ($js_files as $js_file) {
            assert('file_exists($js_file)');
            $this->js .= file_get_contents($js_file);

            $this->js = str_replace(array('{host}', '{self}',
                                          '{new_location}',
                                          '{cat_venue_options}',
                                          '{cat_venue_default}'),
                                    array($_SERVER['HTTP_HOST'],
                                          $_SERVER['PHP_SELF'],
                                          $url, $js_array,
                                          $venue_default),
                                    $this->js);
        }
    }

   /**
    * Converts PHP array to its Javascript analog
    *
    * @access private
    * @param  array     PHP array to convert
    * @param  bool      Generate Javascript object literal (default, works like PHP's associative array) or array literal
    * @return string    Javascript representation of the value
    */
    function convertArrayToJavascript($array, $assoc = true) {
        if (!is_array($array)) {
            return $this->convertScalarToJavascript($array);
        } else {
            $items = array();
            foreach ($array as $key => $val) {
                $item = $assoc? "'" . $this->escapeString($key) . "': ": '';
                if (is_array($val)) {
                    $item .= $this->convertArrayToJavascript($val, $assoc);
                } else {
                    $item .= $this->convertScalarToJavascript($val);
                }
                $items[] = $item;
            }
        }
        $js = implode(', ', $items);
        return $assoc? '{ ' . $js . ' }': '[' . $js . ']';
    }

   /**
    * Converts PHP's scalar value to its Javascript analog
    *
    * @access private
    * @param  mixed     PHP value to convert
    * @return string    Javascript representation of the value
    */
    function convertScalarToJavascript($val)
    {
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        } elseif (is_int($val) || is_double($val)) {
            return $val;
        } elseif (is_string($val)) {
            return "'" . $this->escapeString($val) . "'";
        } elseif (is_null($val)) {
            return 'null';
        } else {
            // don't bother
            return '{}';
        }
    }

   /**
    * Quotes the string so that it can be used in Javascript string constants
    *
    * @access private
    * @param  string
    * @return string
    */
    function escapeString($str)
    {
        return strtr($str,array(
            "\r"    => '\r',
            "\n"    => '\n',
            "\t"    => '\t',
            "'"     => "\\'",
            '"'     => '\"',
            '\\'    => '\\\\'
        ));
    }
}

$page = new add_pub1();
echo $page->toHtml();

?>
