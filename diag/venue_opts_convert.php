<?php ;

//#!/usr/bin/env php

ini_set("include_path", ini_get("include_path") . ":..");

//require_once 'includes/functions.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdVenueList.php';

$db = pdDb::newFromParams(DB_SERVER, DB_USER, DB_PASSWD, 'pubDBdev');

$venues = new pdVenueList($db);

if (count($venues->list) == 0) {
    echo 'No venues in database';
    $db->close();
    exit;
}

foreach ($venues->list as $venue) {
    $venue->dbLoad($db, $venue->pub_id);
}

$db->close();

?>
