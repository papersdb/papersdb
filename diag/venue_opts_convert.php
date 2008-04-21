<?php

//#!/usr/bin/env php

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/functions.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdVenue.php';
require_once 'includes/pdVenueList.php';

$db = new pdDb(array('name' => 'pubDB'));

$venues = pdVenueList::create($db);

if (count($venues) == 0) {
    echo 'No venues in database';
    $db->close();
    exit;
}

if (0)
	foreach ($venues as $pub_id => $title) {
    	$venue = new pdVenue();
	    $venue->dbLoad($db, $pub_id);
    
		if ((pdDB::venueTableUpgraded() == 0) && ! empty($venue->data)) {
			$op = array($pub_id, $venue->nameGet()); 
			
			if (!empty($venue->cat_id))
				$op[] = $venue->cat_id;
			else
				$op[] = '*';
				
			$op[] = $venue->data;
			
			echo '<pre>', implode("\t", $op),  '</pre>', "\n";
		}
		
		$venue->dbSave($db);
	}
else
	foreach ($venues as $pub_id => $title) {
    	$venue = new pdVenue();
	    $venue->dbLoad($db, $pub_id);
    
		if ((pdDB::venueTableUpgraded() == 0) && ! empty($venue->data)) {
			$op = array($pub_id, $venue->nameGet()); 
		
			if (!empty($venue->cat_id))
				$op[] = $venue->cat_id;
			else
				$op[] = '*';

		    if (is_array($venue->options)) 
		    	foreach ($venue->options as $vopt)
		    		if (!empty($vopt))
						$op[] = $vopt;
			
			echo '<pre>', implode("\t", $op),  '</pre>', "\n";
		}
	}

$db->close();

?>
