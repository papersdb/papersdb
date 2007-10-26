<?php ;

// $Id: batch_add_authors.php,v 1.11 2007/10/26 22:03:15 aicmltec Exp $

/**
 * Script that reports the publications with two PI's and also one PI and one
 * PDF.
 *
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPubList.php';
require_once('HTML/QuickForm/Renderer/QuickHtml.php');

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class batch_add_authors extends pdHtmlPage {
    function batch_add_authors() {
        parent::__construct('batch_add_authors');

        if ($this->loginError) return;

        $form = new HTML_QuickForm('batch_add', 'post', null, '_self',
                                   'multipart/form-data');

        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'textarea', 'new_authors', null,
                    array('cols' => 60, 'rows' => 10)),
                HTML_QuickForm::createElement(
                    'static', 'kwgroup_help', null,
                    '<span class="small">separate using semi-colons (;)</span>')),
            'new_auth_group',
            $this->helpTooltip('New Authors:', 'newAuthorHelp'),
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
            $values['new_authors'] = preg_replace("/;\s*;/", ';',
                                                  $values['new_authors']);

            $new_authors = split(';\s*', $values['new_authors']);

            $auth_list = new pdAuthorList($this->db);
            assert('is_array($auth_list->list)');
            $fl_auth_list = $auth_list->asFirstLast();

            $in_db_auths = array_intersect($fl_auth_list, $new_authors);
            $new_auths = array_diff($new_authors, $fl_auth_list);

            foreach ($new_auths as $auth_name) {
                $auth = new pdAuthor();
                $auth->nameSet($auth_name);
                $auth->dbSave($this->db);
            }

            if (count($in_db_auths) > 0) {
                echo 'These authors were already in the '
                    . 'database:<ul>';
                foreach ($in_db_auths as $auth_name) {
                    echo '<li>' . $auth_name . '</li>';
                }
            }

            if (count($new_auths) > 0) {
                if (count($in_db_auths) > 0) {
                    echo '</ul>'
                        . 'Only these authors were added to the database:'
                        . '<ul>';
                }
                else {
                    echo 'These authors were added to the database:<ul>';
                }

                foreach ($new_auths as $auth_name) {
                    echo '<li>' . $auth_name . '</li>';
                }

                echo '</ul>';
            }
            else {
                echo '</ul>'
                    . 'No authors were added to the database.';
            }
        }
        else {
            echo '<h2>Batch Add Authors</h2>';

            $renderer =& $form->defaultRenderer();

            $form->setRequiredNote(
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

            $form->accept($renderer);

            $this->form =& $form;
            $this->renderer =& $renderer;
            $this->javascript();
        }
    }

    function javascript() {
        $this->js = <<<JS_END
            var newAuthorHelp=
            "A semi-colon separated list of author names. Names can be in "
            + "the following formats: <ul><li>fist last</li><li>fist initials "
            + "last</li><li>last, first</li><li>last, first initials</li></ul>";
JS_END;
    }
}

$page = new batch_add_authors();
echo $page->toHtml();

?>
