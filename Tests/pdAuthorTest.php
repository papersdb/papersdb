<?php

ini_set("include_path",  ini_get("include_path") . ':..');

require_once 'PHPUnit/Framework.php';
require_once 'includes/pdDb.php';
require_once 'includes/pdAuthor.php';
 
class pdAuthorTest extends PHPUnit_Framework_TestCase {
	protected $db;
	protected $author;
	
	protected function setUp() {
		$this->db = pdDb::newFromParams(DB_SERVER, DB_USER, DB_PASSWD, 'pubDBdev');
	}
	
	protected function tearDown() {
		$this->db->close();
		unset($this->author);
	}
	
    public function testAllNull() {
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
    
    public function testSimpleAdd() {
		$author = new pdAuthor();
		
        $author->title        = "Professor";
        $author->webpage      = "http://www.cs.ualberta.ca/";
        $author->name         = "Bar, Foo";
        $author->email        = "foo@bar.com";
        $author->organization = "FooBarIndustries";
        
        $author->dbSave($this->db);
        $author_id = $author->author_id;
        
		$author2 = new pdAuthor($this->db);
		$author2->dbLoad($this->db, $author->author_id);
		
	    $this->assertEquals($author->author_id,    $author2->author_id);
	    $this->assertEquals($author->title,        $author2->title);    
	    $this->assertEquals($author->webpage,      $author2->webpage);    
	    $this->assertEquals($author->name,         $author2->name);    
        $this->assertEquals($author->email,        $author2->email);    
        $this->assertEquals($author->organization, $author2->organization);    
    }
    
    public function testSimpleDelete() {
    
    }
}
?>