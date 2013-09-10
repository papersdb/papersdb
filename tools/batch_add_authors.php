<?php

/*
 * Adds a list of authors to PapersDB. Note that only the name field is filled in, and all other
 * fields are left blank.
 */

require_once '../includes/defines.php';
require_once '../includes/functions.php';
require_once '../includes/pdDb.php';
require_once '../includes/pdAuthorList.php';
require_once '../includes/pdAuthor.php';

$authors_new_raw = <<< AUTHORS_NEW_END
Brian P. Anton,
Yi-Chien Chang,
Peter Brown,
Han-Pil Choi,
Lina L. Faller,
Jyotsna Guleria,
Zhenjun Hu,
Niels Klitgord,
Ami Levy-Moonshine,
Almaz Maksad,
Varun Mazumdar,
Mark McGettrick,
Lais Osmani,
Revonda Pokrzywa,
John Rachlin,
Rajeswari Swaminathan,
Benjamin Allen,
Genevieve Housman,
Caitlin Monahan,
Krista Rochussen,
Kevin Tao,
Ashok S. Bhagwat,
Steven E. Brenner,
Linda Columbus,
Valérie de Crécy-Lagard,
Donald Ferguson,
Alexey Fomenkov,
Giovanni Gadda,
Richard D. Morgan,
Andrei L. Osterman,
Dmitry A. Rodionov,
Irina A. Rodionova,
Kenneth E. Rudd,
Dieter Söll,
James Spain,
Shuang-yong Xu,
Alex Bateman,
Robert M. Blumenthal,
J. Martin Bollinger,
Woo-Suk Chang,
Manuel Ferrer,
Iddo Friedberg,
Michael Y. Galperin,
Julien Gobeill,
Daniel Haft,
John Hunt,
Peter Karp,
William Klimke,
Carsten Krebs,
Dana Macelis,
amana Madupu,
Maria J. Martin,
Jeffrey H. Miller,
Claire O\'Donovan,
Bernhard Palsson,
Patrick Ruch,
Aaron Setterdahl,
Granger Sutton,
John Tate,
Alexander Yakunin,
Dmitri Tchigvintsev,
Germán Plata,
Jie Hu,
Russell Greiner,
David Horn,
Kimmen Sjölander,
Steven L. Salzberg,
Dennis Vitkup,
Stanley Letovsky,
Daniel Segrè,
Charles DeLisi,
Richard J. Roberts,
Martin Steffen,
Simon Kasif
AUTHORS_NEW_END;

$db = new pdDb(array('server' => "localhost",
                     'user'   => "dummy",
                     'passwd' => "ozzy498",
                     'name'   => "pubdbdev"));

// this array contains the names in the format: FIRST OTHER LAST
$authors_in_db = pdAuthorList::create($db, null, null, true);
var_dump($authors_in_db);

$authors = array();
foreach (explode(',', $authors_new_raw) as $author_name) {
   if (in_array($author_name, $authors_in_db)) {
      exit("author $author_name already exists in the database");
   }

   $author = new pdAuthor;
   $author->nameSet($author_name);
   $authors[] = $author;
}

print "adding " . count($authors) . " new authors...\n";

// none of the authors already exist in the database, they can be saved
foreach ($authors as $author) {
   $author->dbSave($db);
}

print "new authors added.\n";

//var_dump($authors);
