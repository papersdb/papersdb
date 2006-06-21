<?php ;

// $Id: pdUser.php,v 1.19 2006/06/21 05:34:22 aicmltec Exp $

/**
 * \file
 *
 * \brief Storage and retrieval of user data to / from the database.
 *
 *
 */

require_once 'pdAuthorList.php';

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
            $this->load($obj);
    }

    /**
     *
     */
    function dbLoad(&$db, $id, $flags = 0) {
        assert('is_object($db)');
        $q = $db->selectRow('user', '*', array('login' => $id),
                            "pdUser::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        if (!isset($this->login)) return;

        $this->collaboratorsDbLoad($db);
        return true;
    }

    /**
     *
     */
    function dbSave(&$db) {
        assert('is_object($db)');
        assert('isset($this->login)');
        $db->update('user', array('name' => $this->name,
                                  'email' => $this->email),
                    array('login' => $this->login),
                    'pdUser::dbSave');

        $db->delete('user_author', array('login' => $this->login),
                    'pdUser::dbSave');

        if (isset($this->collaborators) && count($this->collaborators) > 0) {
            // first add the interests
            $arr = array();
            foreach (array_keys($this->collaborators) as $author_id) {
                array_push($arr, array('login' => $this->login,
                                       'author_id' => $author_id));
            }
            $db->insert('user_author', $arr, 'pdUser::dbSave');
        }
    }

    /**
     *
     */
    function collaboratorsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->login)');

        if (isset($this->collaborators) && (count($this->collaborators) > 0))
            return;

        $q = $db->select(array('user_author', 'author'),
                         array('author.author_id', 'author.name'),
                         array('user_author.login' => $this->login,
                               'user_author.author_id=author.author_id'),
                         "pdUser::collaboratorsDbLoad");
        $r = $db->fetchObject($q);
        while ($r) {
            $this->collaborators[$r->author_id] = $r->name;
            $r = $db->fetchObject($q);
        }
    }

    /**
     * User's most popular Authors, sorted according to number of publications
     * submitted.
     */
    function popularAuthorsDbLoad(&$db) {
        assert('is_object($db)');
        assert('isset($this->login)');

        if (isset($this->author_rank) && (count($this->author_rank) > 0))
            return;

        $q = $db->select(array('publication', 'pub_author', 'user'),
                         'pub_author.author_id',
                         array('publication.submit=user.name',
                               'publication.pub_id=pub_author.pub_id',
                               'user.login' => $this->login),
                         "pdUser::popularAuthorsDbLoad");
        if ($q === false) return;
        $r = $db->fetchObject($q);
        while ($r) {
            $this->author_rank[$r->author_id]++;
            $r = $db->fetchObject($q);
        }

        if (count($this->author_rank) > 0) {
            arsort($this->author_rank, SORT_NUMERIC);

            // now remove the author ids that are invalid
            $valid_authors = new pdAuthorList($db);

            $ranked_author_ids = array_keys($this->author_rank);
            foreach ($ranked_author_ids as $id) {
                if (!isset($valid_authors->list[$id]))
                    unset($this->author_rank[$id]);
            }
        }
    }

    /**
     * Loads publication data from the object passed in
     */
    function load($obj) {
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
