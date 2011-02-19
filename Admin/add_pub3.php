<?php

/**
 * This is the form portion for adding or editing author information.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'Admin/add_pub_base.php';
require_once 'includes/pdAuthInterests.php';
require_once 'includes/pdCatList.php';
require_once 'includes/pdVenueList.php';
require_once 'includes/pdAuthor.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub3 extends add_pub_base {
    public $debug = 0;
    public $cat_id;
    public $venue_id;
    public $used_by_me;
    public $booktitle;
    public $publisher;
    public $edition;
    public $editor;
    public $volume;
    public $number;
    public $pages;
    public $author_id = null;

    public function __construct() {
        parent::__construct();

        if ($this->loginError) return;
        $this->use_mootools = true;

        $this->loadHttpVars(true, false);
        $this->pub =& $_SESSION['pub'];

        if (isset($this->pub->pub_id))
            $this->page_title = 'Edit Publication';

        if (empty($this->venue_id) && is_object($this->pub->venue)
            && !empty($this->pub->venue->venue_id))
            $this->venue_id = $this->pub->venue->venue_id;

        if (isset($this->venue_id))
            if ($this->venue_id >= 0)
                $this->pub->addVenue($this->db, $this->venue_id);
            else
                $this->pub->venue = null;

        if (isset($this->cat_id))
            $this->pub->addCategory($this->db, $this->cat_id);
        else if (is_object($this->pub->category))
            $this->cat_id = $this->pub->category->cat_id;

        $this->addPubDisableMenuItems();

        $form = new HTML_QuickForm('add_pub3');

        $form->addElement('header', null, 'Category Information');

        // category
        $category_list = pdCatList::create($this->db);
        $category[] = HTML_QuickForm::createElement(
            'select', 'cat_id', null,
            array('' => '--- Please Select a Category ---')
            + $category_list,
            array('onchange' => 'dataKeep();'));
        $text = '';
        if (is_object($this->pub->venue)
            && is_object($this->pub->category)
            && ($this->pub->venue->cat_id != 0)
            && ($this->pub->venue->cat_id != $this->pub->category->cat_id))
            $text = '<span class="emph">(venue default is: '
                . $category_list[$this->pub->venue->cat_id]
                . ')</span>';
        $category[] = HTML_QuickForm::createElement(
            'static', null, null, $text);

        $tooltip = 'Category::The type of publication entry being
submitted to the database. For example this could be a conference paper, a
journal entry, a book chapter, etc.
&lt;p/&gt;
Select the appropriate category from the drop down menu';

        $form->addGroup(
            $category, null,
            "<span class=\"Tips1\" title=\"$tooltip\">Category</span>:",
            '&nbsp;&nbsp;', false);

        // Venue
        if (is_object($this->pub->category)
            && in_array($this->cat_id, array(1, 3, 4)))
            $vlist = pdVenueList::create(
                $this->db, array('cat_id' => $this->cat_id,
                                 'concat' => true));
        else
            $vlist = pdVenueList::create($this->db, array('concat' => true));

        $venues[''] = '--Select Venue--';
        $venues['-1'] = '--No Venue--';

        // check if user info has 'Venues previously used by me' checked
        if ($this->used_by_me == 'yes') {
            $user =& $_SESSION['user'];
            $user->venueIdsGet($this->db);

            foreach ($vlist as $venue_id => $name) {
                if (isset($user->venue_ids[$venue_id]))
                    $venues[$venue_id] = htmlentities($name);
            }
        }
        else {
            foreach ($vlist as $venue_id => $name) {
                $venues[$venue_id] = htmlentities($name);
            }
        }

        $tooltip = 'Venue::Where the paper was published -- specific journal,
conference, workshop, etc. If many of the database papers are in the same
venue, you can create a single <b>label</b> for that
venue, to specify name of the venue, location, date, editors
and other common information. You will then be able to use
and re-use that information.';

        $form->addElement(
            'select', 'venue_id',
            "<span class=\"Tips1\" title=\"$tooltip\">Venue</span>:",
            $venues, array('style' => 'width: 70%;',
                           'onchange' => 'dataKeep();'));

        $form->addElement('submit', 'add_venue', 'Add New Venue');

        $form->addElement('advcheckbox', 'used_by_me',
                          null, 'Only show venues previously used by me',
                          array('onchange' => 'dataKeep();'), array('', 'yes'));

        // rankings radio selections
        $rankings = pdPublication::rankingsGlobalGet($this->db);
        foreach ($rankings as $rank_id => $description) {
            $text = $description;

            if (is_object($this->pub->venue)) {
                if ($this->pub->venue->rank_id == $rank_id)
                    $text .= ' <span class="emph">(venue default)</span>';
            }
            else if (is_object($this->pub->category)
                     && ((($this->pub->category->cat_id == 1)
                          && ($rank_id == 2))
                         || (($this->pub->category->cat_id == 3)
                             && ($rank_id == 2))
                         || (($this->pub->category->cat_id == 4)
                             && ($rank_id == 3))))
                $text .= ' <span class="emph">(category default)</span>';

            $radio_rankings[] = HTML_QuickForm::createElement(
                'radio', 'paper_rank', null, $text, $rank_id);
        }
        $radio_rankings[] = HTML_QuickForm::createElement(
            'radio', 'paper_rank', null,
            'other (fill in box below)', -1);
        $radio_rankings[] = HTML_QuickForm::createElement(
            'text', 'paper_rank_other', null,
            array('size' => 30, 'maxlength' => 250));

        $tooltip = 'Ranking::Select the ranking of the venue. If the venue is
already in the database the ranking should be selected automatically.
Sometimes the paper may have a ranking different from the venue ranking.';
        $form->addGroup(
            $radio_rankings, 'group_rank',
            "<span class=\"Tips1\" title=\"$tooltip\">Ranking</span>:",
            '<br/>',
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

        $tooltip = 'Date::The date the publication was published.';
        $form->addElement(
            'date', 'pub_date',
            "<span class=\"Tips1\" title=\"$tooltip\">Date</span>:",
            array('format' => 'YM', 'minYear' => pdPublication::MIN_YEAR, 
                    'maxYear' => pdPublication::MAX_YEAR));

        $form->addElement('header', 'other_info', 'Other information', null);

        $tooltip = 'Extra Information::Specify auxiliary information to help
classify this publication. Eg, &quot;with student&quot; or &quot;best
paper&quot;, etc.
&lt;p/&gt;
Note: by default this information will NOT be shown
when this publication entry is displayed.';
        $form->addElement(
            'textarea', 'extra_info',
            "<span class=\"Tips1\" title=\"$tooltip\">Extra Information</span>:",
            array('cols' => 60, 'rows' => 5));

        $form->addElement('static', null, null,
                          '<span class="small">'
                          . 'Separate using semicolons.'
                          . ' See help text for examples of what goes here '
                          . '(use mouse to hover over \'Extra Information\' '
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

        $form->addGroup($buttons, 'buttons', '', '&nbsp;', false);

        $this->form =& $form;

        if ($form->validate())
            $this->processForm();
        else
            $this->renderForm();
    }

    public function renderForm() {
        $form =& $this->form;

        foreach (array_keys(get_class_vars(get_class($this))) as $member) {
            $defaults[$member] = $this->$member;
        }

        if (isset($this->pub->pub_id))
            echo '<h3>Editing Publication Entry</h3>';
        else
            echo '<h3>Adding Publication Entry</h3>';

        echo $this->pub->getCitationHtml('..', false), '&nbsp;',
            getPubIcons($this->db, $this->pub, 0x1), '<p/>',
            add_pub_base::similarPubsHtml($this->db);
            
        if (is_object($this->pub->category))
            $defaults['cat_id'] = $this->pub->category->cat_id;

        if (is_object($this->pub->venue))
            $defaults['venue_id'] = $this->venue_id;

        $defaults['used_by_me'] = $this->used_by_me;

        if (isset($this->pub->rank_id)) {
            if ($this->pub->rank_id > 4) {
                $defaults['paper_rank'] = -1;
                $defaults['paper_rank_other'] = $this->pub->ranking;
            }
            else
                $defaults['paper_rank'] = $this->pub->rank_id;
        }
        else if (is_object($this->pub->venue)) {
            // Use ranking from venue
            if ($this->pub->venue->rank_id > 4) {
                $defaults['paper_rank'] = -1;
                $defaults['paper_rank_other'] = $this->pub->venue->ranking;
            }
            else
                $defaults['paper_rank'] = $this->pub->venue->rank_id;
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
        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    public function processForm() {
        $form =& $this->form;

        $values = $form->exportValues();
        debugVar('values', $values);

        if (isset($this->pub->category) && ($this->pub->category->info != null)) {
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
                    && isset($this->pub->category)
                    && is_object($this->pub->category)
                    && ($this->pub->category->cat_id != $values['cat_id'])))
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

    public function formInfoElementsGet() {
        assert('is_object($this->pub->category)');
        if (!is_array($this->pub->category->info)) return null;

        $infoElements = array_values($this->pub->category->info);

        if (count($infoElements) == 0) return null;

        foreach (array_values($this->pub->category->info) as $name) {
            $element = strtolower(preg_replace("/\s+/", '', $name));
            $formElements[$element] = $name;
        }
        return $formElements;
    }

    public function javascript() {
        $js_files = array('js/add_pub3.js', 'js/add_pub_cancel.js');

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

    public function templateGet() {
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
