<?php ;

// $Id: pdVenue.php,v 1.1 2006/05/30 23:01:09 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of venue data to / from the database.
 *
 *
 */

/**
 *
 * \brief Class for storage and retrieval of venue to / from the
 * database.
 */
class pdVenue {
    var $venue_id;
    var $title;
    var $name;
    var $url;
    var $type;
    var $data;
    var $editor;
    var $date;

    /**
     * Constructor.
     */
    function pdVenue($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id, $flags = 0) {
        $q = $db->selectRow('venue', '*', array('venue_id' => $id),
                         "pdPublication::dbLoadVenue");
        $this->objLoad($q);
    }

    /**
     * Loads publication data from the object passed in
     */
    function objLoad($obj) {
        if ($obj == NULL) return;

        if (isset($obj->venue_id))
            $this->venue_id = $obj->venue_id;
        if (isset($obj->title))
            $this->title = $obj->title;
        if (isset($obj->name))
            $this->name = $obj->name;
        if (isset($obj->url))
            $this->url = $obj->url;
        if (isset($obj->type))
            $this->type = $obj->type;
        if (isset($obj->data))
            $this->data = $obj->data;
        if (isset($obj->editor))
            $this->editor = $obj->editor;
        if (isset($obj->date))
            $this->date = $obj->date;

    }
}

?>
