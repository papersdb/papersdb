<?php

  // $Id: pdPublication.php,v 1.1 2006/05/11 22:32:31 aicmltec Exp $

  /**
   * \file
   *
   * \brief Storage and retrieval of publication data to / from the database.
   *
   *
   */

  /**
   *
   * \brief Class for storage and retrieval of publications to / from the
   * database.
   */

class pdPublication {
    /**
     * Constructor.
     */
    function pdPublication($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad($id, $flags = 0) {
        $db =& connect_db();
        $q = $db->selectRow('publication', '*', array('pub_id' => $id),
                            "pdPublication::dbLoad");
        $this->objLoad($q);

        $q = $db->select(array('category', 'pub_cat'),
                         'category.category',
                         array('category.cat_id=pub_cat.cat_id',
                               'pub_cat.pub_id' => $id),
                         "pdPublication::dbLoad");
        $this->objLoad($db->fetchObject($q));

        $q = $db->select(array('additional_info', 'pub_add'),
                         array('additional_info.location',
                               'additional_info.type'),
                         array('additional_info.add_id=pub_add.add_id',
                               'pub_add.pub_id' => $id),
                         "pdPublication::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->additional_info[] = $r;
            $r = $db->fetchObject($q);
        }

        $q = $db->select(array('author', 'pub_author'),
                         array('author.author_id', 'author.name'),
                         array('author.author_id=pub_author.author_id',
                               'pub_author.pub_id' => $id),
                         "pdPublication::dbLoad",
                         array( 'ORDER BY' => 'pub_author.rank'));
        $r = $db->fetchObject($q);
        while ($r) {
            $this->author[] = $r;
            $r = $db->fetchObject($q);
        }

        $q = $db->select('pointer', 'value',
                         array('pub_id' => $id, 'type' => 'int'),
                         "pdPublication::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->intPointer[] = $r;
            $r = $db->fetchObject($q);
        }

        $q = $db->select('pointer', array('name', 'value'),
                         array('pub_id' => $id, 'type' => 'ext'),
                         "pdPublication::dbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->extPointer[] = $r;
            $r = $db->fetchObject($q);
        }

        $this->dbLoadVenue($db);

        print_r($this);
    }

    function dbLoadVenue(&$db) {
        if ($this->venue == "") return;

        if (preg_match("/:<([0-9]+)>/", $this->venue, $venue_id) == 0) return;

        if ($venue_id[1] == "") return;

        $q = $db->selectRow('venue', '*', array('venue_id' => $venue_id[1]),
                         "pdPublication::dbLoadVenue");
        $this->venue_info = $q;
    }

    /**
     * Loads publication data from the object passed in
     */
    function objLoad($obj) {
        if ($obj == NULL) return;

        if (isset($obj->pub_id))
            $this->pub_id = $obj->pub_id;
        if (isset($obj->title))
            $this->title = $obj->title;
        if (isset($obj->paper))
            $this->paper = $obj->paper;
        if (isset($obj->abstract))
            $this->abstract = $obj->abstract;
        if (isset($obj->keywords))
            $this->keywords = $obj->keywords;
        if (isset($obj->published))
            $this->published = $obj->published;
        if (isset($obj->venue))
            $this->venue = $obj->venue;
        if (isset($obj->extra_info))
            $this->extra_info = $obj->extra_info;
        if (isset($obj->submit))
            $this->submit = $obj->submit;
        if (isset($obj->updated))
            $this->updated = $obj->updated;
        if (isset($obj->additional_info))
            $this->additional_info = $obj->additional_info;
        if (isset($obj->category))
            $this->category = $obj->category;
        if (isset($obj->location))
            $this->location = $obj->location;
        if (isset($obj->type))
            $this->type = $obj->type;
    }
}

?>
