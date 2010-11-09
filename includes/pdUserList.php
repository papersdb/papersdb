<?php

class pdUserList {
	private function __construct() {}

	public static function create($db) {
		assert('is_object($db)');
		$q = $db->select('user', '*', '', "pdUserList::create");

		$list = array();
		foreach ($q as $r) {
			$list[$r->login] = $r;
		}
		return $list;
	}

    public static function getNotVerified($db) {
        assert('is_object($db)');
        $q = $db->select('user', '*', array('verified' => '0'), 
            "pdUserList::getNotVerified");

        $list = array();
        foreach ($q as $r) {
            $list[$r->login] = new pdUser($r);
        }
        return $list;
    }

}

?>