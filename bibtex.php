<?php

/**
 * This file outputs all the search results given to it in a BibTeX format.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdPublication.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class bibtex extends pdHtmlPage {
    public $pub_ids;

    public function __construct() {
        parent::__construct('bibtex', null, false);

        if ($this->loginError) return;

        $this->loadHttpVars();

        if (!isset($this->pub_ids)) {
            $this->pageError = true;
            return;
        }

        $pubs = explode(',', $this->pub_ids);

        if (!is_array($pubs) || (count($pubs) == 0)) {
            $this->pageError = true;
            return;
        }

        $pub_list = pdPubList::create($this->db, array('pub_ids' => $pubs));

        if (!is_array($pub_list) || (count($pub_list) == 0)) {
            $this->pageError = true;
            return;
        }

        $table = new HTML_Table(array('width' => '100%',
                                      'border' => '0',
                                      'cellpadding' => '0',
                                      'cellspacing' => '0'));
        $table->setAutoGrow(true);

        $pub_count = 0;
        foreach ($pub_list as $pub) {
            $pub_count++;
            $result = $pub->dbLoad($this->db, $pub->pub_id);

            if ($result === false) {
                $this->pageError = true;
                return;
            }

            $table->addRow(array('<pre>' . $pub->getBibtex() . '</pre>'));
        }

        // now assign table attributes including highlighting for even and odd
        // rows
        for ($i = 0; $i < $table->getRowCount(); $i++) {
            if ($i & 1)
                $table->updateRowAttributes($i, array('class' => 'even'), true);
            else
                $table->updateRowAttributes($i, array('class' => 'odd'), true);
        }
        $table->updateColAttributes(0, array('class' => 'publist'), true);

        echo $table->toHtml();
    }
}

$page = new bibtex();
echo $page->toHtml();

?>

