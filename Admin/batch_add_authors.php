<?php

/**
 * Script that reports the publications with two PI's and also one PI and one
 * PDF.
 *
 * @package PapersDB
 */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthor.php';
require_once 'HTML/QuickForm/Renderer/QuickHtml.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class batch_add_authors extends pdHtmlPage {
    public function __construct() {
        parent::__construct('batch_add_authors');

        if ($this->loginError) return;
        
        $this->use_mootools = true;

        $form = new HTML_QuickForm('batch_add', 'post', null, '_self',
                                   'multipart/form-data');
    
        $tooltip = <<<TOOLTIP_END
New Authors::A semi-colon separated list of author names. Names can be in the following 
formats: 
&lt;ul&gt;
  &lt;li&gt;fist last&lt;/li&gt;
  &lt;li&gt;fist initials last&lt;/li&gt;
  &lt;li&gt;last, first&lt;/li&gt;
  &lt;li&gt;last, first initials&lt;/li&gt;
&lt;/ul&gt;
TOOLTIP_END;

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'textarea', 'new_authors', null,
                    array('cols' => 60, 'rows' => 10)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span class="small">separate using semi-colons (;)</span>')),
            'new_auth_group',
            "<span class=\"Tips1\" title=\"$tooltip\">New Authors:</span>",
            '<br/>', false);

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                  'submit', 'submit', 'Add New Authors'),
                HTML_QuickForm::createElement(
                  'button', 'cancel', 'Cancel',
                  array('onclick' => 'history.back()'))
              ),
            null, null, '&nbsp;', false);

        if ($form->validate()) {
            $values = $form->exportValues();
            $values['new_authors'] 
                = preg_replace("/;\s*;/", ';', $values['new_authors']);
            $new_authors = preg_split('/;\s*/', $values['new_authors']);

            $fl_auth_list = pdAuthorList::create($this->db, null, null, true);

            $in_db_auths = array_intersect($fl_auth_list, $new_authors);
            $new_auths = array_diff($new_authors, $fl_auth_list);

            foreach ($new_auths as $auth_name) {
                $auth = new pdAuthor();
                $auth->nameSet($auth_name);
                $auth->dbSave($this->db);
                unset($auth);
            }

            if (count($in_db_auths) > 0) {
                echo 'These authors were already in the database:<ul>';
                foreach ($in_db_auths as $auth_name) {
                    echo '<li>', $auth_name, '</li>';
                }
            }

            if (count($new_auths) > 0) {
                if (count($in_db_auths) > 0) {
                    echo '</ul>', 'Only these authors were added to the database:', 
                    	'<ul>';
                }
                else {
                    echo 'These authors were added to the database:<ul>';
                }

                foreach ($new_auths as $auth_name) {
                    echo '<li>', $auth_name, '</li>';
                }

                echo '</ul>';
            }
            else {
                echo '</ul>No authors were added to the database.';
            }
        }
        else {
            echo '<h2>Batch Add Authors</h2>';

            $renderer =& $form->defaultRenderer();

            $form->setRequiredNote(
                '<font color="#FF0000">*</font> shows the required fields.');
            $form->accept($renderer);
            $this->form =& $form;
            $this->renderer =& $renderer;
            
            $this->js = <<<JS_END
window.addEvent('domready', function() {
                    var Tips1 = new Tips($$('.Tips1'));
                });
JS_END;
        }
    }
}

$page = new batch_add_authors();
echo $page->toHtml();

?>
