<?php ;

// $Id: pdInfoList.php,v 1.8 2007/11/02 16:36:29 loyola Exp $

/**
 * Class to retrieve information table data.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * The class retrieves data from the information table.
 *
 * @package PapersDB
 */
class pdInfoList {
    public $list;

    /**
     * Constructor.
     */
    public function __construct($db) {
        assert('is_object($db)');
        $q = $db->select('info', '*', '', "pdInfoList::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->info_id] = $r->name;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }

    public function infoExists($name) {
        assert('isset($this->list)');
        return in_array($name, $this->list);
    }
}

?>
