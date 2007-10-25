<?php ;

// $Id: pdUser.php,v 1.33 2007/10/25 17:44:51 aicmltec Exp $

/**
 * Implements a class that accesses user information from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */

/** Required to get the used author rank. */
require_once 'includes/pdDbAccessor.php';
require_once 'pdAuthorList.php';

define('PD_USER_OPTION_SHOW_INTERNAL_INFO', 1);

/**
 * Class that accesses user information from the database.
 *
 * @package PapersDB
 */
class pdUser extends pdDbAccessor {
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
    var $venue_ids;
    var $options;

    /**
     * Constructor.
     */
    function pdUser($mixed = null) {
        parent::pdDbAccessor($mixed);
        $this->search_params = null;
    }

    /**
     *
     */
    function dbLoad($db, $id) {
        assert('is_object($db)');
        $q = $db->selectRow('user', '*', array('login' => $id),
                            "pdUser::dbLoad");
        if ($q === false) return false;
        $this->load($q);

        if (!isset($this->login)) return;

        $this->collaboratorsDbLoad($db);
        $this->authorIdGet($db);
        $this->venueIdsGet($db);
        return true;
    }

    /**
     *
     */
    function dbSave($db) {
        assert('is_object($db)');
        assert('isset($this->login)');
        $db->update('user', array('name'    => $this->name,
                                  'email'   => $this->email,
                                  'options' => $this->options),
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
    function collaboratorsDbLoad($db) {
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
    function popularAuthorsDbLoad($db) {
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
            if (!isset($this->author_rank[$r->author_id]))
                $this->author_rank[$r->author_id] = 0;
            else
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

    function authorIdGet($db) {
        assert('$this->name != ""');
        $name = explode(' ', $this->name);
        $count = count($name);
        $author_name = $name[$count - 1] . ', ' . $name[0];

        $q = $db->selectRow('author', 'author_id',
                            array('name' => $author_name),
                            "pdAuthor::dbLoad");

        if ($q === false) return;

        $this->author_id = $q->author_id;
    }

    function venueIdsGet($db) {
        assert('$this->name != ""');
        unset($this->venue_ids);

        $q = $db->select(array('publication', 'venue'),
                         array('DISTINCT publication.venue_id',
                               'venue.title',
                               'venue.name'),
                         array('publication.submit' => $this->name,
                               'publication.venue_id=venue.venue_id'),
                         "pdAuthor::dbLoad",
                         array( 'ORDER BY' => 'venue.title'));

        if ($q === false) return;

        $r = $db->fetchObject($q);
        while ($r) {
            if ($r->title != '')
                $this->venue_ids[$r->venue_id] = $r->title;
            $r = $db->fetchObject($q);
        }
    }

    function showInternalInfo() {
        return ($this->options & PD_USER_OPTION_SHOW_INTERNAL_INFO);
    }
}

?>
