<?php

/**
 * Implements a class that accesses category information from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */
require_once 'includes/pdDbAccessor.php';

/**
 * Class that accesses category information from the database.
 *
 * @package PapersDB
 */
class pdCategory extends pdDbAccessor {
    public $cat_id;
    public $category;
    public $info;
    public $dbLoadFlags;

	/** Flags used when loading information from the database. */
	const DB_LOAD_BASIC = 0;
	const DB_LOAD_CATEGORY_INFO = 1;
	const DB_LOAD_ALL = 1;

    /**
     * Constructor.
     */
    public function __construct($mixed = null) {
        parent::__construct($mixed);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    public function dbLoad($db, $id, $name = null, $flags = self::DB_LOAD_ALL) {
        $this->dbLoadFlags = $flags;

        if (isset($id)) {
            // category id 0 is a null category, don't load anything
            if ($id == 0) return;

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

        if (($flags & self::DB_LOAD_CATEGORY_INFO)
            && isset($this->cat_id)) {
            $this->dbLoadCategoryInfo($db);
        }
        return true;
    }

    public function dbLoadCategoryInfo($db) {
        assert ('isset($this->cat_id)');

        // only load this once
        if (count($this->info) > 0) return;

        $this->dbLoadFlags |= self::DB_LOAD_CATEGORY_INFO;

        $q = $db->select(array('info', 'cat_info'),
                         array('info.info_id', 'info.name'),
                         array('info.info_id=cat_info.info_id',
                               'cat_info.cat_id' => $this->cat_id),
                         "pdCategory::dbLoadCategoryInfo");
        foreach ($q as $r) {
            $this->info[$r->info_id] = $r->name;
        }
    }

    /**
     *
     */
    public function dbSave($db) {
        if (isset($this->cat_id)) {

            $table->updateColAttributes(0, array('class' => 'emph',
                                                 'width' => '25%'));
            $db->update('category', array('category' => $this->category),
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

    public function dbSaveInfo ($db) {
        if (!isset($this->info))  return;

        $info_list = pdInfoList::create($db);

        $arr = array();
        foreach ($this->info as $info) {
            if (!in_array($info->name, $info_list)) {
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

    public function dbDelete($db) {
        $db->delete('cat_info', array('cat_id' => $this->cat_id),
                    'pdCategory::dbDelete');
        $db->delete('category', array('cat_id' => $this->cat_id),
                    'pdCategory::dbDelete');
    }

    public function infoAdd($info_id, $name) {
        assert('!is_null($info_id)');
        assert('!is_null($name)');
        $obj = new stdClass;
        $obj->info_id = $info_id;
        $obj->name = $name;
        $this->info[] = $obj;
    }

    public function asArray() {
        return get_object_vars($this);
    }
}

?>
