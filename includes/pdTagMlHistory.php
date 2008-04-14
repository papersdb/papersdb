<?php



/**
 * Implements a class that accesses category information from the database.
 *
 * @package PapersDB
 * @subpackage DB_Access
 */
require_once 'includes/pdDbAccessor.php';

/**
 * Class that accesses category information from the database.
 *
 * @package PapersDB
 */
class pdTagMlHistory {    
    public static function dbSave(&$db, &$pub_ids) {
        assert('is_array($pub_ids)');
        assert('count($pub_ids) > 0');
        
        $user =& $_SESSION['user'];
        $db->insert('tag_ml_history', 
            array('login'     => $user->login,
                  'pub_ids'   => implode(' ', $pub_ids)),
            'pdUser::dbSave');
        
    }
}

?>
