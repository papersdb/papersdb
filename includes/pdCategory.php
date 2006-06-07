<?php ;

// $Id: pdCategory.php,v 1.3 2006/06/07 14:04:49 aicmltec Exp $

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
            $this->load($obj);
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
        $this->load($db->fetchObject($q));

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
    function load($mixed) {
        if (is_object($mixed)) {
            if (isset($mixed->cat_id))
                $this->cat_id = $mixed->cat_id;
            if (isset($mixed->category))
                $this->category = $mixed->category;
            if (isset($mixed->info))
                $this->info = $mixed->info;
        }
        else if (is_array($mixed)) {
            if (isset($mixed['cat_id']))
                $this->cat_id = $mixed['cat_id'];
            if (isset($mixed['category']))
                $this->category = $mixed['category'];
            if (isset($mixed['info']))
                $this->info = $mixed['info'];
        }
    }
}

?>
