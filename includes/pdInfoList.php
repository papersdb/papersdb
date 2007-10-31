<?php ;

// $Id: pdInfoList.php,v 1.7 2007/10/31 23:17:34 loyola Exp $

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
    var $list;

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

    function infoExists($name) {
        assert('isset($this->list)');
        return in_array($name, $this->list);
    }
}

?>
