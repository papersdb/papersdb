<?php ;

// $Id: pdUser.php,v 1.8 2006/06/05 04:28:41 aicmltec Exp $

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
    var $author_rank;

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
    function dbLoad(&$db, $id, $flags = 0) {
        $q = $db->selectRow('user', '*', array('login' => $id),
                            "pdUser::dbLoad");
        $this->objLoad($q);

        if (!isset($this->login)) return;

        $this->collaboratorsDbLoad($db);

        //print_r($this);
    }

    /**
     *
     */
    function collaboratorsDbLoad(&$db) {
        assert('isset($this->login)');

        $q = $db->select(array('user_author', 'author'),
                         array('author.author_id', 'author.name'),
                         array('login' => $this->login),
                         "pdUser::collaboratorsDbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->collaborators[] = $r;
            $r = $db->fetchObject($q);
        }
    }

    /**
     * User's most popular Authors, sorted according to number of publications
     * submitted.
     */
    function popularAuthorsDbLoad(&$db) {
        assert('isset($this->login)');

        $q = $db->select(array('pub_author', 'user'),
                         'pub_author.author_id',
                         array('publication.submit=user.name',
                               'publication.pub_id=pub_author.pub_id',
                               'user.login' => $this->login),
                         "pdUser::popularAuthorsDbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->author_rank[$r->author_id]++;
            $r = $db->fetchObject($q);
        }
        asort($this->author_rank);
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
