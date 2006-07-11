<?php ;

// $Id: pdVenue.php,v 1.8 2006/07/11 22:01:03 aicmltec Exp $

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
            $this->load($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad(&$db, $id) {
        assert('is_object($db)');

        $q = $db->selectRow('venue', '*', array('venue_id' => $id),
                         "pdPublication::dbLoadVenue");
        if ($q === false) return false;
        $this->load($q);
        return true;
    }

    /**
     *
     */
    function dbSave(&$db) {
        assert('is_object($db)');

        $values = array('title'    => $this->title,
                        'name'     => $this->name,
                        'url'      => $this->url,
                        'type'     => $this->type,
                        'data'     => $this->data,
                        'editor'   => $this->editor,
                        'date'     => $this->date);

        if (isset($this->venue_id)) {
            $db->update('venue', $values, array('venue_id' => $this->venue_id),
                        'pdUser::dbSave');
            return $db->affectedRows();
        }
        else {
            $db->insert('venue ', $values, 'pdUser::dbSave');
            return true;
        }
    }

    /**
     *
     */
    function dbDelete (&$db) {
        assert('is_object($db)');
        $db->delete('venue', array('venue_id' => $this->venue_id),
                    'pdUser::dbDelete');
        return $db->affectedRows();
    }

    /**
     * Loads publication data from the object passed in
     */
    function load($mixed) {
        if (is_object($mixed)) {
            if (isset($mixed->venue_id))
                $this->venue_id = $mixed->venue_id;
            if (isset($mixed->title))
                $this->title = $mixed->title;
            if (isset($mixed->name))
                $this->name = $mixed->name;
            if (isset($mixed->url))
                $this->url = $mixed->url;
            if (isset($mixed->type))
                $this->type = $mixed->type;
            if (isset($mixed->data))
                $this->data = $mixed->data;
            if (isset($mixed->editor))
                $this->editor = $mixed->editor;
            if (isset($mixed->date))
                $this->date = $mixed->date;
        }
        else if (is_array($mixed)) {
            if (isset($mixed['venue_id']))
                $this->venue_id = $mixed['venue_id'];
            if (isset($mixed['title']))
                $this->title = $mixed['title'];
            if (isset($mixed['name']))
                $this->name = $mixed['name'];
            if (isset($mixed['url']))
                $this->url = $mixed['url'];
            if (isset($mixed['type']))
                $this->type = $mixed['type'];
            if (isset($mixed['data']))
                $this->data = $mixed['data'];
            if (isset($mixed['editor']))
                $this->editor = $mixed['editor'];
            if (isset($mixed['date']))
                $this->date = $mixed['date'];
        }

    }
}

?>
