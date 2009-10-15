<?php

/**
 * Main page for PapersDB.
 *
 * Main page for public access, provides a login, and a function that selects
 * the most recent publications added.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries the base class and classes to access the database. */
require_once '../includes/defines.php';
require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAicmlStaff.php';
require_once 'includes/pdAicmlStaffList.php';

/**
 * Renders the whole page.
 *
 * @package PapersDB
 */
class aicml_staff extends pdHtmlPage {
    public function __construct() {
        parent::__construct('aicml_staff');

        if ($this->loginError) return;

        echo '<h1>AICML Staff</h1>';

        $table = new HTML_Table(array('class' => 'stats'));
        $table->addRow(array('Name', 'Start', 'End', 'Num Pubs', 'Pub Ids'));
        $table->setRowType(0, 'th');
        
        //pdDb::debugOn();

        $staff_list = pdAicmlStaffList::create($this->db);
        foreach ($staff_list as $staff_id => $author_id) {
            $staff = pdAicmlStaff::newFromDb($this->db, $staff_id, pdAicmlStaff::DB_LOAD_PUBS_MIN);
            $author = pdAuthor::newFromDb($this->db, $author_id, pdAuthor::DB_LOAD_MIN);

            //debugVar('staff', array($staff, $author));

            $pub_links = array();
            if (isset($staff->pub_ids)) {
                foreach ($staff->pub_ids as $pub_id) {
                    $pub_links[] = '<a href="../view_publication.php?pub_id='
                    . $pub_id . '">' . $pub_id . '</a>';
                }
            }

            $table->addRow(array($author->name, $staff->start_date, $staff->end_date,
                count($staff->pub_ids),
                implode(', ', $pub_links)),
                array('class' => 'stats_odd'));
        }
        echo $table->toHtml();
    }
}

$page = new aicml_staff();
echo $page->toHtml();

?>
