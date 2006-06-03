<?php ;

// $Id: pdCategory.php,v 1.1 2006/06/03 04:24:10 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of venue data to / from the database.
 *
 *
 */


define('PD_CATEGORY_DB_LOAD_BASIC',         0);
define('PD_CATEGORY_DB_LOAD_CATEGORY_INFO', 1);
define('PD_CATEGORY_DB_LOAD_ALL',           1);

/**
 *
 * \brief Class for storage and retrieval of venue to / from the
 * database.
 */
class pdCategory {
    var $cat_id;
    var $category;
    var $info;
    var $dbLoadFlags;

    /**
     * Constructor.
     */
    function pdCategory($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id, $flags = PD_CATEGORY_DB_LOAD_ALL) {
        $this->dbLoadFlags = $flags;

        $q = $db->select('category', '*', array('cat_id' => $id),
                         "pdPublication::dbLoad");
        $this->objLoad($db->fetchObject($q));

        if (($flags & PD_CATEGORY_DB_LOAD_CATEGORY_INFO)
            && isset($this->cat_id)) {
            $this->dbLoadCategoryInfo($db);
        }
    }


    function dbLoadCategoryInfo(&$db) {
        assert ('isset($this->cat_id)');

        $this->dbLoadFlags |= PD_CATEGORY_DB_LOAD_CATEGORY_INFO;

        $q = $db->select(array('info', 'cat_info'),
                         array('info.info_id', 'info.name'),
                         array('info.info_id=cat_info.info_id',
                               'cat_info.cat_id' => $this->cat_id),
                         "pdCategory::dbLoadCategoryInfo");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->info[$r->info_id] = $r->name;
            $r = $db->fetchObject($q);
        }
    }

    /**
     * Loads publication data from the object passed in
     */
    function objLoad(&$obj) {
        if ($obj == NULL) return;

        if (isset($obj->cat_id))
            $this->cat_id = $obj->cat_id;
        if (isset($obj->category))
            $this->category = $obj->category;

    }
}

?>
