<?php ;

// $Id: pdVenue.php,v 1.5 2006/06/13 19:00:22 aicmltec Exp $

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
        if ($q === false) return false;
        $this->load($q);
        return true;
    }

    /**
     *
     */
    function dbSave(&$db) {
        assert('is_object($db)');

        if (isset($this->venue_id)) {
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
            return $db->affectedRows();
        }
        else {
            $db->query('INSERT INTO venue '
                       . '(venue_id, title, name, url, type, data, editor, date)'
                       . 'VALUES (NULL, '
                       . quote_smart($this->title) .  ', '
                       . quote_smart($this->name) .   ', '
                       . quote_smart($this->url) .    ', '
                       . quote_smart($this->type) .   ', '
                       . quote_smart($this->data) .   ', '
                       . quote_smart($this->editor) . ', '
                       . quote_smart($this->date) .   ')');
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
