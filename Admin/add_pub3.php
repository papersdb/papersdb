<?php ;

// $Id: add_pub3.php,v 1.35 2007/07/09 18:43:51 aicmltec Exp $

/**
 * This is the form portion for adding or editing author information.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'Admin/add_pub_base.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdAuthor.php';
require_once 'includes/pdExtraInfoList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub3 extends add_pub_base {
    var $debug = 0;
    var $cat_id;
    var $venue_id;
    var $used_by_me;
    var $booktitle;
    var $publisher;
    var $edition;
    var $editor;
    var $volume;
    var $number;
    var $pages;
    var $author_id = null;

    function add_pub3() {
        parent::add_pub_base();

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);
        $this->pub =& $_SESSION['pub'];

        if (isset($this->pub->pub_id))
            $this->page_title = 'Edit Publication';

        if (empty($this->venue_id) && is_object($this->pub->venue)
            && !empty($this->pub->venue->venue_id))
            $this->venue_id = $this->pub->venue->venue_id;

        if (isset($this->venue_id) && ($this->venue_id >= 0))
            $this->pub->addVenue($this->db, $this->venue_id);

        if (isset($this->cat_id))
            $this->pub->addCategory($this->db, $this->cat_id);
        else if (is_object($this->pub->category))
            $this->cat_id = $this->pub->category->cat_id;

        $this->addPubDisableMenuItems();

        $form = new HTML_QuickForm('add_pub3');

        $form->addElement('header', null, 'Category Information');

        // category
        $category_list = new pdCatList($this->db);
        $form->addElement(
            'select', 'cat_id',
            $this->helpTooltip('Category', 'categoryHelp') . ':',
            array('' => '--- Please Select a Category ---')
            + $category_list->list,
            array('onchange' => 'dataKeep();'));

        // Venue
        switch ($this->cat_id) {
            case 1:
            case 3:
            case 4:
                $vlist = new pdVenueList($this->db,
                                         array('cat_id' => $this->cat_id,
                                               'concat' => true));
                break;

            default:
                $vlist = new pdVenueList($this->db,
                                         array('concat' => true));
                break;
        }

        $venues[''] = '--Select Venue--';
        $venues['-1'] = '--No Venue--';

        // check if user info has 'Venues previously used by me' checked
        if ($this->used_by_me == 'yes') {
            $user =& $_SESSION['user'];
            $user->venueIdsGet($this->db);

            foreach ($vlist->list as $venue_id => $name) {
                if (in_array($venue_id, $user->venue_ids))
                    $venues[$venue_id] = $name;
            }
        }
        else {
            foreach ($vlist->list as $venue_id => $name) {
                $venues[$venue_id] = $name;
            }
        }

        $form->addElement('select', 'venue_id',
                          $this->helpTooltip('Venue', 'venueHelp') . ':',
                          $venues, array('style' => 'width: 70%;',
                                         'onchange' => 'dataKeep();'));

        $form->addElement('submit', 'add_venue', 'Add New Venue');

        $form->addElement('advcheckbox', 'used_by_me',
                          null, 'Only show venues previously used by me',
                          array('onchange' => 'dataKeep();'), array('', 'yes'));



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

        if (($this->cat_id > 0)
            && is_object($this->pub->category)
            && is_array($this->pub->category->info)
            && (count($this->pub->category->info) > 0)) {
            foreach ($this->formInfoElementsGet() as $element => $name) {
                $form->addElement('text', $element, $name . ':',
                                  array('size' => 50, 'maxlength' => 250));
            }
        }

        $form->addElement('date', 'pub_date', 'Date:',
                          array('format' => 'YM', 'minYear' => '1985'));

        $form->addElement('header', 'other_info', 'Other information', null);

        $form->addElement('textarea', 'extra_info',
                          $this->helpTooltip('Extra Information',
                                             'extraInfoHelp') . ':',
                          array('cols' => 60, 'rows' => 5));

        $extra_info = new pdExtraInfoList($this->db);

        $form->addElement('static', null, null,
                          '<span class="small">'
                          . 'Separate using semicolons.'
                          . ' See help text for examples of what goes here '
                          . '(use mouse to over over \'Extra Information\' '
                          . 'text).'
                          . '</span>');

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'prev_step', '<< Previous Step');
        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "cancelConfirm();"));
        $buttons[] = HTML_QuickForm::createElement(
            'reset', 'reset', 'Reset');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'next_step', 'Next Step >>');

        if ($this->pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->form =& $form;

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    function renderForm() {
        $form =& $this->form;

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            $defaults[$member] = $this->$member;
        }

        if (isset($this->pub->pub_id))
            echo '<h3>Editing Publication Entry</h3>';
        else
            echo '<h3>Adding Publication Entry</h3>';

        echo $this->pub->getCitationHtml('..', false) . '&nbsp;'
            . $this->getPubIcons($this->pub, 0x1)
            . '<p/>'
            . add_pub_base::similarPubsHtml();

        if (is_object($this->pub->category))
            $defaults['cat_id'] = $this->pub->category->cat_id;

        if (is_object($this->pub->venue))
            $defaults['venue_id'] = $this->venue_id;

        $defaults['used_by_me'] = $this->used_by_me;

        if (isset($this->pub->rank_id)) {
            $defaults['paper_rank'] = $this->pub->rank_id;
            if ($this->pub->rank_id == -1)
                $defaults['paper_rank_other'] = $this->pub->ranking;
        }
        else if (is_object($this->pub->venue)) {
            // Use ranking from venue
            $defaults['paper_rank'] = $this->pub->venue->rank_id;
            if ($this->pub->venue->rank_id == -1)
                $defaults['paper_rank_other'] = $this->pub->venue->ranking;
        }

        $defaults['extra_info'] = $this->pub->extra_info;

        // assign category info items
        if ((count($this->pub->info) > 0)
            && is_object($this->pub->category)
            && (count($this->pub->category->info) > 0))
            foreach ($this->formInfoElementsGet() as $element => $name) {
                if (isset($this->pub->info[$name]))
                    $defaults[$element] = $this->pub->info[$name];
            }

        if (!isset($this->pub->published) || ($this->pub->published == '')) {
            $defaults['pub_date'] = array('Y' => date('Y'), 'M' => date('m'));
        }
        else {
            $date = explode('-', $this->pub->published);

            $defaults['pub_date']['Y'] = $date[0];
            $defaults['pub_date']['M'] = $date[1];
        }

        $form->setDefaults($defaults);

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

    function processForm() {
        $form =& $this->form;

        $values = $form->exportValues();

        if ($this->pub->category->info != null) {
            foreach ($this->formInfoElementsGet() as $element => $name) {
                if (isset($values[$element]))
                    $this->pub->info[$name] = $values[$element];
            }
        }

        if ((!empty($values['venue_id'])) && ($values['venue_id'] > 0))
            $this->pub->addVenue($this->db, $values['venue_id']);
        else if (is_object($this->pub->venue)) {
            unset($this->pub->venue);
            unset($this->pub->venue_id);
        }

        if ($values['cat_id'] > 0) {
            if (!isset($this->pub->venue)
                || (is_object($this->pub->venue)
                    && ($this->pub->category != $values['cat_id'])))
                // either no venue set for this pub entry, OR user has
                // overriden the category since user selected one that does not
                // match the venue
                $this->pub->addCategory($this->db, $values['cat_id']);
        }

        if (isset($values['paper_rank']))
            $this->pub->rank_id = $values['paper_rank'];

        if (isset($values['paper_rank']) && ($values['paper_rank'] == -1)
            && (strlen($values['paper_rank_other']) > 0)) {
            $this->pub->rank_id = -1;
            $this->pub->ranking = $values['paper_rank_other'];
        }

        $this->pub->published = $values['pub_date']['Y'] . '-'
            .  $values['pub_date']['M'] . '-1';

        $extra_info_arr = array();
        if ($values['extra_info'] != '')
            $extra_info_arr = array_merge($extra_info_arr,
                                          array($values['extra_info']));

        $this->pub->extraInfoSet($extra_info_arr);

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('pub', $this->pub);
            return;
        }

        if (isset($values['add_venue']))
            header('Location: add_venue.php');
        else if (isset($values['prev_step']))
            header('Location: add_pub2.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub4.php');
    }

    function formInfoElementsGet() {
        if (!is_array($this->pub->category->info)) return null;

        $infoElements = array_values($this->pub->category->info);

        if (count($infoElements) == 0) return null;

        foreach (array_values($this->pub->category->info) as $name) {
            $element = strtolower(preg_replace("/\s+/", '', $name));
            $formElements[$element] = $name;
        }
        return $formElements;
    }

    function javascript() {
        $js_files = array(FS_PATH . '/Admin/js/add_pub3.js',
                          FS_PATH . '/Admin/js/add_pub_cancel.js');

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        foreach ($js_files as $js_file) {
            assert('file_exists($js_file)');
            $content = file_get_contents($js_file);

            $this->js .= str_replace(array('{host}', '{self}',
                                           '{new_location}'),
                                     array($_SERVER['HTTP_HOST'],
                                           $_SERVER['PHP_SELF'],
                                           $url),
                                     $content);
        }
    }

    function templateGet() {
        $template = <<<END
{javascript}
<table{class}>
<tr>
  <th>&nbsp;</th>
  <!-- BEGIN label_2 --><th>{label_2}</th><!-- END label_2 -->
  <th>&nbsp;</th>
  <!-- BEGIN label_3 --><th>{label_3}</th><!-- END label_3 -->
</tr>
<tr>
  <td class="middle">{moveup}<br/>{movedown}<br/>{remove}</td>
  <td class="middle">{selected}</td>
  <td class="middle">{add}</td>
  <td class="middle">{unselected}</td>
</tr>
</table>
{javascript}
END;
       return $template;
    }
}

$page = new add_pub3();
echo $page->toHtml();


?>
