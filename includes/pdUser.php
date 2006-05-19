<?php

  // $Id: pdUser.php,v 1.5 2006/05/19 15:55:55 aicmltec Exp $

  /**
   * \file
   *
   * \brief Storage and retrieval of user data to / from the database.
   *
   *
   */

/**
   *
   * \brief Class for storage and retrieval of user to / from the
   * database.
   */

class pdUser {
    var $login;
    var $password;
    var $name;
    var $email;
    var $comments;
    var $search;
    var $collaborators;

    /**
     * Constructor.
     */
    function pdUser($obj = NULL) {
        if (!is_null($obj))
            $this->objLoad($obj);
    }

    /**
     * Loads a specific publication from the database.
     *
     * Use flags to load individual tables
     */
    function dbLoad($id, &$db, $flags = 0) {
        $q = $db->selectRow('user', '*', array('login' => $id),
                            "pdUser::dbLoad");
        $this->objLoad($q);

        if (!isset($this->login)) return;

        $this->collaboratorsDbLoad($db);

        //print_r($this);
    }

    function collaboratorsDbLoad(&$db) {
        if (!isset($this->login))
            die("pdUser::collaboratorsDbLoad: pdUser id not assigned");

        $q = $db->select(array('user_author', 'author'),
                         array('author.author_id', 'author.name'),
                         array('login' => $id),
                         "pdUser::collaboratorsDbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->collaborators[] = $r;
            $r = $db->fetchObject($q);
        }
    }

    /**
     * Loads publication data from the object passed in
     */
    function objLoad($obj) {
        if ($obj == NULL) return;

        if (isset($obj->login))
            $this->login = $obj->login;
        if (isset($obj->password))
            $this->password = $obj->password;
        if (isset($obj->name))
            $this->name = $obj->name;
        if (isset($obj->email))
            $this->email = $obj->email;
        if (isset($obj->comments))
            $this->comments = $obj->comments;
        if (isset($obj->search))
            $this->search = $obj->search;
    }
}

?>
