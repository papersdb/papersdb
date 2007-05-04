<?php ;

// $Id: add_pub2.php,v 1.26 2007/05/04 04:26:40 loyola Exp $

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
require_once 'includes/pdAuthor.php';
require_once 'includes/authorselect.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub2 extends add_pub_base {
    var $debug = 0;
    var $author_id = null;

    function add_pub2() {
        parent::add_pub_base();

        if ($this->loginError) return;

        $this->pub =& $_SESSION['pub'];

        if (isset($this->pub->pub_id))
            $this->page_title = 'Edit Publication';

        $auth_list = new pdAuthorList($this->db);
        $this->authors = $auth_list->asFirstLast();

        $form = new HTML_QuickForm('add_pub2');

        $form->addElement('header', null, 'Select from Authors in Database');
        $form->addElement('textarea', 'authors', 'Authors:',
                          array('cols' => 60,
                                'rows' => 5,
                                'class' => 'wickEnabled:MYCUSTOMFLOATER',
                                'wrap' => 'virtual'));
        $form->addElement('static', null, null,
                          '<span class="small">'
                          . 'There are ' . count($this->authors)
                          . ' authors in the database. Type a partial name to '
                          . 'see a list of matching authors.</span>');
        $form->addElement('submit', 'add_new_author', 'Add Author not in DB');

        // collaborations radio selections
        $form->addElement('header', null, 'Collaborations');
        $collaborations = pdPublication::collaborationsGet($this->db);

        foreach ($collaborations as $col_id => $description) {
            $radio_cols[] = HTML_QuickForm::createElement(
                'checkbox', 'paper_col[' . $col_id . ']', null, $description,
                1);
        }

        $form->addGroup($radio_cols, 'group_collaboration',
                        null, '<br/>', false);

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'prev_step', '<< Previous Step');
        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "cancelConfirm();"));
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'next_step', 'Next Step >>');

        if ($this->pub->pub_id != '')
            $buttons[] = HTML_QuickForm::createElement(
                'submit', 'finish', 'Finish');

        $form->addGroup($buttons, 'buttons', '', '&nbsp', false);

        $this->form =& $form;

        if ($form->validate()) {
            $this->processForm();
        }
        else {
            $this->renderForm();
        }
    }

    function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array();

        if (count($this->pub->authors) > 0) {
            foreach ($this->pub->authors as $author)
                $auth_names[] = $author->firstname . ' ' . $author->lastname;
            $defaults['authors'] = implode(', ', $auth_names);
        }

        if (is_array($this->pub->collaborations)
            && (count($this->pub->collaborations) > 0)) {
            foreach ($this->pub->collaborations as $col_id) {
                $defaults['paper_col'][$col_id] = 1;
            }
        }

        $form->setDefaults($defaults);

        if (isset($this->pub->pub_id))
            echo '<h3>Editing Publication Entry</h3>';
        else
            echo '<h3>Adding Publication Entry</h3>';

        echo $this->pub->getCitationHtml('', false) . '<p/>'
            . add_pub_base::similarPubsHtml();

        $renderer =& $form->defaultRenderer();

        $renderer->setFormTemplate(
            '<table width="100%" border="0" cellpadding="3" cellspacing="2" '
            . 'bgcolor="#CCCC99"><form{attributes}>{content}</form></table>');
        $renderer->setHeaderTemplate(
            '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
            . 'align="left" colspan="2"><b>{header}</b></td></tr>');

        $renderer->setElementTemplate(
            '<tr><td><b>{label}</b></td>'
            . '<td><div style="position:relative;text-align:left"><table id="MYCUSTOMFLOATER" class="myCustomFloater" style="position:absolute;top:50px;left:0;background-color:#cecece;display:none;visibility:hidden"><tr><td><div class="myCustomFloaterContent"></div></td></tr></table></div>{element}</td></tr>',
            'authors');

        $form->accept($renderer);
        $this->renderer =& $renderer;
        $this->javascript();
    }

    function processForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $values = $form->exportValues();

        if (!empty($values['authors'])) {
            // need to retrieve author_ids for the selected authors
            $selAuthors = explode(', ', preg_replace('/\s\s+/', ' ',
                                                     $values['authors']));
            $author_ids = array();
            foreach ($selAuthors as $author) {
                if (empty($author)) continue;

                $result = array_search($author, $this->authors);
                if ($result !== false)
                    $author_ids[] = $result;
            }

            if (count($author_ids) > 0)
                $this->pub->addAuthor($this->db, $author_ids);
        }

        if (isset($values['paper_col'])
            && (count($values['paper_col']) > 0)) {
            $this->pub->collaborations = array_keys($values['paper_col']);
        }

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('pub', $this->pub);
            return;
        }

        if (isset($values['add_new_author']))
            header('Location: add_author.php');
        else if (isset($values['prev_step']))
            header('Location: add_pub1.php');
        else if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else
            header('Location: add_pub3.php');
    }

    function javascript() {
        $this->js .= "<script language=\"JavaScript\" type=\"text/JavaScript\">"
            . "\ncollection="
            . convertArrayToJavascript($this->authors, false)
            . "\n</script>\n";

        $js_files = array(FS_PATH . '/js/wick.js',
                          FS_PATH . '/Admin/js/add_pub_cancel.js');

        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        foreach ($js_files as $js_file) {
            assert('file_exists($js_file)');
            $contents = file_get_contents($js_file);

            $contents = str_replace(array('{host}', '{self}',
                                          '{new_location}'),
                                    array($_SERVER['HTTP_HOST'],
                                          $_SERVER['PHP_SELF'],
                                          $url),
                                    $contents);

            $this->js .= $contents;
        }
    }
}

$page = new add_pub2();
echo $page->toHtml();

?>
