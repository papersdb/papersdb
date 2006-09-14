<?php ;

// $Id: pdUser.php,v 1.23 2006/09/14 20:28:49 aicmltec Exp $

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
    var $verified;
    var $access_level;
    var $collaborators;
    var $author_rank;
    var $search_params;
    var $author_id;

    /**
     * Constructor.
     */
    function pdUser($obj = NULL) {
        if (!is_null($obj))
            $this->load($obj);

        $this->search_params = null;
    }

    /**
     *
     */
    function dbLoad(&$db, $id) {
        assert('is_object($db)');
        $q = $db->selectRow('user', '*', array('login' => $id),
                            "pdUser::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        if (!isset($this->login)) return;

        $this->collaboratorsDbLoad($db);
        $this->authorIdGet($db);
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
                         "pdUser::collaboratorsDbLoad",
                         array('ORDER BY' => 'name ASC'));
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

    function authorIdGet(&$db) {
        $name = explode(' ', $this->name);
        $count = count($name);
        $author_name = $name[$count - 1] . ', ' . $name[0];

        $q = $db->selectRow('author', 'author_id',
                            array('name' => $author_name),
                            "pdAuthor::dbLoad");

        if ($q === false) return;

        $this->author_id = $q->author_id;
    }

    /**
     * Loads user data from the object or array passed in
     */
    function load($mixed) {
        $members = array('login', 'password', 'name', 'email', 'comments',
                         'search', 'verified', 'access_level');

        if (is_object($mixed)) {
            foreach ($members as $member) {
                if (isset($mixed->$member))
                    $this->$member = $mixed->$member;
            }
        }
        else if (is_array($mixed)) {
            foreach ($members as $member) {
                if (isset($mixed[$member]))
                    $this->$member = $mixed[$member];
            }
        }
    }
}

?>
