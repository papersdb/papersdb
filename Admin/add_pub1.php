<?php ;

// $Id: add_pub1.php,v 1.57 2008/02/19 13:38:41 loyola Exp $

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
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class add_pub1 extends add_pub_base {
    public $debug = 0;
    public $cat_venue_options;
    public $category_list;

    /**
     * Constructor.
     *
     */
    public function __construct() {
        parent::__construct();

        if ($this->loginError) return;

        $this->loadHttpVars(true, false);
        $this->use_mootools = true;

        if (isset($_SESSION['pub'])) {
            // according to session variables, we are already editing a
            // publication
            $this->pub =& $_SESSION['pub'];
        }
        else if ($this->pub_id != '') {
            // pub_id passed in with $_GET variable
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
        $form->addElement(
            'text', 'title',
            '<span class="Tips1" title="Title::The title of the publication.">Title:</span>',
            array('size' => 60, 'maxlength' => 250));
        $form->addRule('title', 'please enter a title', 'required',
                       null, 'client');

        $tooltip = 'Abstract::If available, enter the abstract of the document
 you are submitting. &lt;p/&gt;
 Plain text or HTML text (using HTML tags) can be used.';

        $form->addElement(
            'textarea', 'abstract',
            "<span class=\"Tips1\" title=\"$tooltip\">Abstract</span>:<br/><div class=\"small\">HTML Enabled</div>",
            array('cols' => 60, 'rows' => 10));

        $tooltip = 'Keywords::Enter keywords that describe your paper and could
 possibly be used to find your paper by other users searching the database. You
 may want to enter multiple terms that are associated with your document.
 &lt;p/&gt;
 &lt;i&gt;&lt;b&gt;If your paper is Machine Learning related please put
 \'mahcine learning\' here.&lt;/b&gt;&lt;/i&gt;
 &lt;p/&gt;
 Examples may include words like: medical imaging; robotics; data mining.';

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'keywords', null,
                    array('size' => 60, 'maxlength' => 250)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span class="small">separate using semi-colons (;)</span>')),

            'kwgroup',
            "<span class=\"Tips1\" title=\"$tooltip\">Keywords:</span>",
            '<br/>', false);

        $tooltip = 'User Info::A place for the user to enter his/her own information';

        $form->addElement(
            'textarea', 'user',
            "<span class=\"Tips1\" title=\"$tooltip\">User Info:</span>",
            array('cols' => 60, 'rows' => 2));

        $buttons[] = HTML_QuickForm::createElement(
            'button', 'cancel', 'Cancel',
            array('onclick' => "cancelConfirm();"));
        $buttons[] = HTML_QuickForm::createElement(
            'reset', 'reset', 'Reset');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'step2', '>> Step 2');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'step3', '>> Step 3');
        $buttons[] = HTML_QuickForm::createElement(
            'submit', 'step4', '>> Step 4');

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

    /**
     * Called to render the form.
     */
    public function renderForm() {
        assert('isset($_SESSION["pub"])');

        $form =& $this->form;

        $defaults = array('title'    => $this->pub->title,
                          'abstract' => $this->pub->abstract,
                          'keywords' => $this->pub->keywords,
                          'user'     => $this->pub->user);

        $this->form->setDefaults($defaults);

        if (isset($_SESSION['pub']) && ($_SESSION['pub']->title != '')) {
            $this->pub =& $_SESSION['pub'];

            if (isset($this->pub->pub_id))
                echo '<h3>Editing Publication Entry</h3>';
            else
                echo '<h3>Adding Publication Entry</h3>';

            echo $this->pub->getCitationHtml('..', false), '&nbsp;',
                $this->getPubIcons($this->pub, 0x1), '<p/>',
                add_pub_base::similarPubsHtml();
        }

        $renderer =& $this->form->defaultRenderer();

        $this->form->setRequiredNote(
            '<font color="#FF0000">*</font> shows the required fields.');

        $renderer->setFormTemplate(
            '<form{attributes}><table width="100%" bgcolor="#CCCC99">'
            . '{content}</form></table>');
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

    /**
     * Called to process the input typed into the form by the user.
     */
    public function processForm() {
        assert('isset($_SESSION["pub"])');
        $form =& $this->form;

        $values = $form->exportValues();

        $this->pub->load($values);
        $_SESSION['state'] = 'pub_add';

        $result = $this->pub->duplicateTitleCheck($this->db);
        if (count($result) > 0)
            $_SESSION['similar_pubs'] = $result;

        if ($this->debug) {
            debugVar('values', $values);
            debugVar('pub', $this->pub);
            return;
        }

        if (isset($values['finish']))
            header('Location: add_pub_submit.php');
        else if (isset($values['step2']))
            header('Location: add_pub2.php');
        else if (isset($values['step3']))
            header('Location: add_pub3.php');
        else if (isset($values['step4']))
            header('Location: add_pub4.php');
    }

    public function javascript() {
        $js_files = array(FS_PATH . '/Admin/js/add_pub1.js',
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
}

$page = new add_pub1();
echo $page->toHtml();

?>
