<?php ;

// $Id: pdCategory.php,v 1.8 2006/06/13 23:56:04 aicmltec Exp $

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
            $r = $db->selectRow('category', '*', array('cat_id' => $id),
                                "pdPublication::dbLoad");
            if ($r === false) return false;
            $this->load($r);
        }
        else if (isset($name)) {
            $r = $db->selectRow('category', '*', array('category' => $name),
                                "pdPublication::dbLoad");
            if ($r === false) return false;
            $this->load($r);
        }

        if (($flags & PD_CATEGORY_DB_LOAD_CATEGORY_INFO)
            && isset($this->cat_id)) {
            $this->dbLoadCategoryInfo($db);
        }
        return true;
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
        assert('($q !== false)');
        $r = $db->fetchObject($q);
        while ($r) {
            $this->info[$r->info_id] = $r->name;
            $r = $db->fetchObject($q);
        }
    }

    /**
     *
     */
    function dbSave(&$db) {
        if (isset($this->cat_id)) {

            $table->updateColAttributes(0, array('id' => 'emph',
                                                 'width' => '25%'));            $db->update('category', array('category' => $this->category),
                        array('cat_id' => $this->cat_id), 'pdUser::dbSave');
        }
        else {
            $db->insert('category', array('category' => $this->category),
                        'pdUser::dbSave');

            // get the cat_id now
            $r = $db->selectRow('category', 'cat_id',
                                array('category' => $this->category),
                                'pdUser::dbSave');
            assert('($r !== false)');
            $this->cat_id = $r->cat_id;
        }
        $this->dbSaveInfo($db);
    }

    function dbSaveInfo (&$db) {
        if (!isset($this->info))  return;

        $info_list = new pdInfoList($db);

        $arr = array();
        foreach ($this->info as $info) {
            if (!$info_list->infoExists($info->name)) {
                $db->insert('info', array('name' =>$info->name),
                            'pdCategory::dbSaveInfo');
            }

            $r = $db->selectRow('info', 'info_id',
                                array('name' => $info->name),
                                'pdPublication::dbSaveInfo');
            assert('($r !== false)');
            array_push($arr, array('cat_id' => $this->cat_id,
                                   'info_id' => $r->info_id));
            $info->info_id = $r->info_id;
        }

        if (isset($this->cat_id)) {
            $db->delete('cat_info', array('cat_id' => $this->cat_id),
                        'pdUser::dbSave');
        }

        if (count($arr) > 0)
            $db->insert('cat_info', $arr, 'pdCategory::dbSaveInfo');
    }

    function dbDelete(&$db) {
        $db->delete('cat_info', array('cat_id' => $this->cat_id),
                    'pdCategory::dbDelete');
        $db->delete('category', array('cat_id' => $this->cat_id),
                    'pdCategory::dbDelete');
    }

    function infoAdd($info_id, $name) {
        assert('!is_null($info_id)');
        assert('!is_null($name)');
        $obj = new stdClass;
        $obj->info_id = $info_id;
        $obj->name = $name;
        $this->info[] = $obj;
    }

    function asArray() {
        return get_object_vars($this);
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
