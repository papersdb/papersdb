<?php ;

// $Id: pdAttachmentTypesList.php,v 1.1 2006/08/30 20:15:57 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

/**
 * \brief
 */
class pdAttachmentTypesList {
    var $list;

    /**
     * Constructor.
     */
    function pdAttachmentTypesList(&$db) {
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
