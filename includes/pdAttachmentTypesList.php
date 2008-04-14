<?php ;



/**
 * Queries the databse for the different attachment types allowed.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/**
 * Queries the databse for the different attachment types allowed.
 *
 * @package PapersDB
 */
class pdAttachmentTypesList {
    public static function create($db) {
        assert('is_object($db)');
        
        if (isset($_SESSION['attachment_types'])
            && is_array($_SESSION["attachment_types"])) 
            return $_SESSION['attachment_types'];
        
        $list = array();

        $q = $db->select('attachment_types', array('DISTINCT type'), '',
                         "pdAttachmentTypesList::create");
        if ($q === false) return null;

        $r = $db->fetchObject($q);
        while ($r) {
            $list[$r->type] = $r->type;
            $r = $db->fetchObject($q);
        }
        $_SESSION['attachment_types'] =& $list;
        return $list;        
    }
}

?>
