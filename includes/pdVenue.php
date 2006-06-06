<?php ;

// $Id: pdVenue.php,v 1.2 2006/06/06 21:11:12 aicmltec Exp $

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
    function dbLoad(&$db, $id, $flags = 0) {
        $q = $db->selectRow('venue', '*', array('venue_id' => $id),
                         "pdPublication::dbLoadVenue");
        $this->load($q);
    }

    /**
     *
     */
    function dbSaveNew(&$db) {
        assert('is_object($db)');
        $db->query('INSERT INTO venue '
                   . '(venue_id, title, name, url, type, data, editor, date)'
                   . 'VALUES (NULL, "' . $this->title .'", '
                   . '"' . $this->name . '", '
                   . '"' . $this->url . '", '
                   . '"' . $this->type . '", '
                   . '"' . $this->data . '", '
                   . '"' . $this->editor . '", '
                   . '"' . $this->date . '")');
    }

    /**
     *
     */
    function dbSave(&$db) {
        assert('is_object($db)');
        assert('isset($this->venue_id)');
        $db->update('venue',
                    array('title' => $this->title,
                          'name' => $this->name,
                          'url' => $this->url,
                          'type' => $this->type,
                          'data' => $this->data,
                          'editor' => $this->editor,
                          'date' => $this->date),
                    array('venue_id' => $this->venue_id),
                    'pdUser::dbSave');
    }

    /**
     * Loads publication data from the object passed in
     */
    function load($o) {
        if (is_object($o)) {
            if (isset($o->venue_id))
                $this->venue_id = $o->venue_id;
            if (isset($o->title))
                $this->title = $o->title;
            if (isset($o->name))
                $this->name = $o->name;
            if (isset($o->url))
                $this->url = $o->url;
            if (isset($o->type))
                $this->type = $o->type;
            if (isset($o->data))
                $this->data = $o->data;
            if (isset($o->editor))
                $this->editor = $o->editor;
            if (isset($o->date))
                $this->date = $o->date;
        }
        else if (is_array($o)) {
            if (isset($o['venue_id']))
                $this->venue_id = $o['venue_id'];
            if (isset($o['title']))
                $this->title = $o['title'];
            if (isset($o['name']))
                $this->name = $o['name'];
            if (isset($o['url']))
                $this->url = $o['url'];
            if (isset($o['type']))
                $this->type = $o['type'];
            if (isset($o['data']))
                $this->data = $o['data'];
            if (isset($o['editor']))
                $this->editor = $o['editor'];
            if (isset($o['date']))
                $this->date = $o['date'];
        }

    }
}

?>
