<?php 

/**
 * Main page for PapersDB.
 *
 * $Id: aicml_staff.php,v 1.1 2008/02/06 15:17:15 loyola Exp $
 * 
 * @package PapersDB
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once("includes/functions.php");
require_once("includes/pdDb.php");

$usage =<<<USAGE_END
USAGE: {$argv[0]} filename

USAGE_END;

function getAicmlPositions(&$db) {
    assert('is_object($db)');
    $result = array();
    $q = $db->select('aicml_positions', '*');
    if (!$q) return $result;
    
    $r = $db->fetchObject($q);
    while ($r) {
        $result[$r->description] = $r->pos_id;
        $r = $db->fetchObject($q);
    }
    return $result;
}

function getAuthorId(&$db, $name) {
    assert('is_object($db)');
    $q = $db->selectRow('author', 'author_id', 
        array('name LIKE "%' . $name . '%"'));
    if (!$q) return -1;
    return $q->author_id;
}

if (count($argv) != 2) 
    exit($usage);

$filename = $argv[1]; 

$contents = file_get_contents($filename);
if (!$contents)
    exit("\ncould not open file " . $filename . "\n");

$info = array();    
foreach (explode("\n", $contents) as $key => $line) {
    $args = explode("\t", $line);
    $n = count($args);
    
    if (($n != 4) && ($n != 5)) {
        echo 'skipping line: ', $key, " only ", $n, " arguments\n";
        continue;
    }
    
    $firstname = trim($args[1]);
    
    $info[$key] = array(
        'fullname'   => trim($args[0]) . ', ' . $firstname,
        'shortname'  => trim($args[0]) . ', ' . $firstname[0],
        'position'   => trim($args[2]),
        'start_date' => trim($args[3]));        
    
    if (isset($args[4]))
        $info[$key]['end_date'] = trim($args[4]);
}

if (count($info) == 0)
    exit('no data to update database with');
    
//pdDb::debugOn();    
    
$db = pdDb::newFromParams();
$positions =& getAicmlPositions($db);

$staff = array();
foreach ($info as $key => $p) {
    $author_id = getAuthorId($db, $p['fullname']);
    if ($author_id < 0) {
        $author_id = getAuthorId($db, $p['shortname']);
        if ($author_id < 0) {
            // author information will not be added to database
            echo "author ", $p['fullname'], " not in database\n";
            continue;
        }
    }
    
    // make sure the position matches the one in the database    
    if (!in_array($p['position'], array_keys($positions))) {
        if ($p['position'] == 'PI')
            $pos_id = $positions['Principal Investigator'];
        else if ($p['position'] == 'PDF')
            $pos_id = $positions['Post Doctoral Fellow'];
        else if ($p['position'] == 'PhD')
            $pos_id = $positions['PhD Student'];
        else if ($p['position'] == 'MSc')
            $pos_id = $positions['MSc Student'];
    }
    else
        $pos_id = $positions[$p['position']];
    
    $staff_member = array(
        'author_id' => $author_id,
        'pos_id'    => $pos_id,
        'start_date' => $p['start_date']
    );
    
    if (isset($p['end_date']))
        $staff_member['end_date'] = $p['end_date'];
        
    $staff[] = $staff_member;
}

// $staff contains staff members that are already in the papersDb database
foreach ($staff as $member) {
    $result = $db->insert('aicml_staff', $member);
    if (!$result)
        echo 'could not insert staff information for ', $member['author_id'], "\n";
}

debugVar('$staff', $staff);
   
?>
