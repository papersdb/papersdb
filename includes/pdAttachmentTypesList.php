<?php ;

// $Id: pdAttachmentTypesList.php,v 1.4 2007/03/19 22:04:39 aicmltec Exp $

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
    var $list;

    /**
     * Constructor.
     */
    function pdAttachmentTypesList($db) {
        assert('is_object($db)');

        $this->list = array();

        $q = $db->select('attachment_types', array('DISTINCT type'), '',
                         "pdAttachmentTypesList::pdAttachmentTypesList");
        if ($q === false) return;

        $r = $db->fetchObject($q);
        while ($r) {
            $this->list[$r->type] = $r->type;
            $r = $db->fetchObject($q);
        }
        assert('is_array($this->list)');
    }
}

?>
