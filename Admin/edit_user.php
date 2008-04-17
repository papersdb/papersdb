<?php

/**
 * This page displays/edits the users information.
 *
 * This includes fields like name, email and favorite collaborators. These
 * authors will be people the user will most likely be using a lot.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthorList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class edit_user extends pdHtmlPage {
    protected $status;
    protected $db_authors;   

    public function __construct() {
        parent::__construct('edit_user');

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (isset($this->status) && ($this->status == 'edit'))
            $this->editUser();
        else
            $this->showUser();

    }

    public function editUser() {
        $this->db_authors = pdAuthorList::create($this->db, null, null, true);
        $user =& $_SESSION['user'];
        $user->collaboratorsDbLoad($this->db);

        $form = new HTML_QuickForm('pubForm');

        $form->addElement('static', 'login_label', 'Login:', $user->login);
        $form->addElement('hidden', 'status', 'edit');
        $form->addElement('hidden', 'login', $user->login);
        $form->addElement('text', 'name', 'Name:',
                          array('size' => 50, 'maxlength' => 100));
        $form->addElement('text', 'email', 'E-mail:',
                          array('size' => 50, 'maxlength' => 100));

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

        $form->addElement('advcheckbox', 'option_internal_info',
                          'Options:', 'show internal info', null,
                          array('No', 'Yes'));

        $auth_list = pdAuthorList::create($this->db);

        $form->addElement('submit', 'Submit', 'Save');

        if ($form->validate()) {
            $values = $form->exportValues();

            assert('$values["login"] == $user->login');

            $user->name = $values['name'];
            $user->email = $values['email'];
            $user->options = 0;
            if ($values['option_internal_info'] == 'Yes')
                $user->options |= PD_USER_OPTION_SHOW_INTERNAL_INFO;

            unset($user->collaborators);
            
            // need to retrieve author_ids for the selected authors
            $selAuthors = explode(
            	', ', preg_replace('/\s\s+/', ' ', $values['authors']));
            $author_ids = array();
            foreach ($selAuthors as $author) {
                if (empty($author)) continue;

                $result = array_search($author, $this->db_authors);
                if ($result !== false)
                    $user->collaborators[$result] = $this->db_authors[$result];
            }

            $user->dbSave($this->db);
            echo 'Change to user information submitted.<p/>';
            echo 'Click <a href="edit_user.php?status=edit">here</a> to edit your preferences again.';
        }
        else {
            echo '<h2>Login Information</h2>';

            $defaults = array('name' => $user->name,
                              'email' => $user->email,
                              'option_internal_info'
                              => (($user->options
                                  & PD_USER_OPTION_SHOW_INTERNAL_INFO)
                                  ? 'Yes' : 'No'));

            if (count($user->collaborators) >0) {
            	$author_names = pdAuthorList::createFromAuthorIds(
                		$this->db, array_keys($user->collaborators), true);
                $defaults['authors'] = implode(', ', array_values($author_names));
            }

            $form->setDefaults($defaults);

            $renderer =& $form->defaultRenderer();

            $renderer->setFormTemplate(
                '<table width="100%" border="0" cellpadding="3" '
                . 'cellspacing="2" bgcolor="#CCCC99">'
                . '<form{attributes}>{content}</form></table>');
            $renderer->setHeaderTemplate(
                '<tr><td style="white-space:nowrap;background:#996;color:#ffc;" '
                . 'align="left" colspan="2"><b>{header}</b></td></tr>');

            $renderer->setElementTemplate(
	            '<tr><td><b>{label}</b></td>'
    	        . '<td><div style="position:relative;text-align:left"><table id="MYCUSTOMFLOATER" class="myCustomFloater" style="font-size:1.1em;position:absolute;top:50px;left:0;background-color:#f4f4f4;display:none;visibility:hidden"><tr><td><div class="myCustomFloaterContent"></div></td></tr></table></div>{element}</td></tr>',
        	    'authors');

            $form->accept($renderer);

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->javascript();
        }
    }

    public function showUser() {
        $user =& $_SESSION['user'];
        $user->collaboratorsDbLoad($this->db);

        echo '<h2>Login Information&nbsp;<a href="edit_user.php?status=edit">',
        	'<img src="../images/pencil.gif" title="edit" ', 
        	'alt="edit" height="16" width="16" border="0" ', 
        	'align="top" /></a></h2>';

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '6',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        $table->addRow(array('Login:', $user->login));
        $table->addRow(array('Name:', $user->name));
        $table->addRow(array('E-mail:', $user->email));

        if ($user->showInternalInfo())
        $option_value = 'Yes';
        else
        $option_value = 'No';

        $table->addRow(array('Show Internal Info:', $option_value));

        if (is_array($user->collaborators)
            && (count($user->collaborators) > 0)) {
            $rowcount = 0;
            foreach ($user->collaborators as $collaborator) {
                if ($rowcount == 0)
                $cell1 = 'Favorite Collaborators:';
                else
                $cell1 = '';
                $table->addRow(array($cell1, $collaborator));
                $rowcount++;
            }
        }
        else {
            $table->addRow(array('Favorite Collaborators:', 'None assigned'));
        }

        $table->updateColAttributes(0, array('class' => 'emph',
                                             'width' => '30%'));
        $this->table =& $table;
    }

    private function javascript() {        
        // WICK
        $this->js .= "\ncollection="
            . convertArrayToJavascript($this->db_authors, false)
            . "\n";
                                 
        $this->addJavascriptFiles(array('js/wick.js'));
    }
}

$page = new edit_user();
echo $page->toHtml();

?>

