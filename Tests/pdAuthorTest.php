<?php

ini_set("include_path",  ini_get("include_path") . ':..');

require_once 'PHPUnit/Framework.php';
require_once 'includes/functions.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdAuthor.php';

//pdDb::debugOn();

class pdAuthorTest extends PHPUnit_Framework_TestCase {
    protected $db;
    protected $author;

    protected function setUp() {
        $this->db = pdDb::newFromParams(DB_SERVER, DB_USER, DB_PASSWD, 'pubDBdev');
    }

    protected function tearDown() {
        $this->db->close();
        unset($this->author);
        $this->db->debug();
    }

    public function testAllNull() {
    	assert('is_object($this->db)');
        $author = new pdAuthor();

        $this->assertEquals(null, $author->author_id);
        $this->assertEquals(null, $author->title);
        $this->assertEquals(null, $author->webpage);
        $this->assertEquals(null, $author->name);
        $this->assertEquals(null, $author->firstname);
        $this->assertEquals(null, $author->lastname);
        $this->assertEquals(null, $author->email);
        $this->assertEquals(null, $author->organization);
        $this->assertEquals(null, $author->interests);
        $this->assertEquals(null, $author->dbLoadFlags);
        $this->assertEquals(null, $author->pub_list);
        $this->assertEquals(null, $author->totalPublications);

        unset($author);
    }

    protected function addDummyAuthor() {
    	$author = new pdAuthor();

        $author->name         = 'Bar, Foo';
        $author->title        = 'Professor';
        $author->webpage      = 'http://www.cs.ualberta.ca/';
        $author->email        = 'foo@bar.com';
        $author->organization = 'FooBarIndustries';

        $author->addInterest(array(uniqid('interest_'),
                                   uniqid('interest_')));

        $author->dbSave($this->db);
        return $author;
    }

    public function testSimpleAdd() {
    	assert('is_object($this->db)');
    	$author = $this->addDummyAuthor();

    	$author2 = new pdAuthor();
        $author2->dbLoad($this->db, $author->author_id);

        $this->assertEquals($author->firstname,    'Foo');
        $this->assertEquals($author->lastname,     'Bar');

        $this->assertEquals($author->author_id,    $author2->author_id);
        $this->assertEquals($author->title,        $author2->title);
        $this->assertEquals($author->webpage,      $author2->webpage);
        $this->assertEquals($author->name,         $author2->name);
        $this->assertEquals($author->firstname,    $author2->firstname);
        $this->assertEquals($author->lastname,     $author2->lastname);
        $this->assertEquals($author->email,        $author2->email);
        $this->assertEquals($author->organization, $author2->organization);
        $this->assertEquals(array_values($author->interests),
                            array_values($author2->interests));

        $author2->dbDelete($this->db);

        unset($author);
        unset($author2);
    }

    public function testSimpleDelete() {
    	assert('is_object($this->db)');

    	$author = $this->addDummyAuthor();
    	$author_id = $author->author_id;
    	$interests = $author->interests;
    	$author->dbDelete($this->db);

        $q = $this->db->select('author', 'author_id',
                               array('author_id' => $author_id),
                               'pdAuthorTest::testSimpleDelete');

        $this->assertEquals($this->db->numRows($q), 0);

        $q = $this->db->select('author_interest', 'author_id',
                               array('author_id' => $author_id),
                               'pdAuthorTest::testSimpleDelete');
        $this->assertEquals($this->db->numRows($q), 0);

        foreach($interests as $i) {
            $q = $this->db->selectRow('interest', 'interest_id',
                                      array('interest' => $i),
                                      'pdAuthorTest::testSimpleDelete');
            $this->assertEquals($q, false);
        }
    }
    
    /*
     * Assumes database already populated with sample data.
     */
    public function testAuthorPubs() {
    	// load the first author with a publication
    	$q = $this->db->selectRow('pub_author', '*', '',
                                  'pdAuthorTest::testSimpleDelete',
    							  'LIMIT 1');
		debugVar('', $q);
    }
}
?>