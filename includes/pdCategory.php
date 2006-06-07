<?php ;

// $Id: pdCategory.php,v 1.4 2006/06/07 23:08:37 aicmltec Exp $

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
    function dbLoad(&$db, $id, $name = null, $flags = PD_CATEGORY_DB_LOAD_ALL) {
        $this->dbLoadFlags = $flags;

        if (isset($id)) {
            $q = $db->select('category', '*', array('cat_id' => $id),
                             "pdPublication::dbLoad");
            $this->load($db->fetchObject($q));
        }
        else if (isset($name)) {
            $q = $db->select('category', '*', array('category' => $name),
                             "pdPublication::dbLoad");
            $this->load($db->fetchObject($q));
        }

        if (($flags & PD_CATEGORY_DB_LOAD_CATEGORY_INFO)
            && isset($this->cat_id)) {
            $this->dbLoadCategoryInfo($db);
        }
    }


    function dbLoadCategoryInfo(&$db) {
        assert ('isset($this->cat_id)');

        // only load this once
        if (count($this->info) > 0) return;

        $this->dbLoadFlags |= PD_CATEGORY_DB_LOAD_CATEGORY_INFO;

        $q = $db->select(array('info', 'cat_info'),
                         array('info.info_id', 'info.name'),
                         array('info.info_id=cat_info.info_id',
                               'cat_info.cat_id' => $this->cat_id),
                         "pdCategory::dbLoadCategoryInfo");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->info[] = $r;
            $r = $db->fetchObject($q);
        }
    }

    /**
     *
     */
    function dbSave(&$db) {
        if (isset($this->cat_id)) {
            $db->update('category', array('category' => $this->category),
                        array('cat_id' => $this->cat_id), 'pdUser::dbSave');
            dbSaveInfo($db);
        }
        else {
            $db->query('INSERT INTO category (cat_id, category)'
                       . 'VALUES (NULL, "' . $this->category . '")');
            $this->dbSaveInfo($db);
        }
    }

    function dbSaveInfo (&$db) {
        if (!isset($this->info))  return;

        foreach ($this->info as $info) {
            if ($info->info_id = '') {
                $db->query('INSERT INTO info (info_id, name)'
                           . 'VALUES (NULL, "' . $info->name . '")');

                $r = $db->selectRow('info', 'info_id',
                                    array('name' => $info->name),
                                    'pdPublication::dbSaveInfo');
                $cat_info[] = '(' . $this->cat_id . ',' . $r->info_id . ')';
            }
        }

        if (count($cat_info) > 0)
            $db->query('INSERT INTO cat_info (cat_id, info_id)'
                       . 'VALUES ' . implode(',', $cat_info));
    }

    function infoAdd($info_id, $name) {
        assert('!is_null($info_id)');
        assert('!is_null($name)');
        $obj = new stdClass;
        $obj->info_id = $info_id;
        $obj->name = $name;
        $this->info[] = $obj;
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
