<?php

class AccessLevel {

    private static $access_levels = array(
    0 => 'None',
    1 => 'Editor',
    2 => 'Administrator'
    );
    
    public static function getAccessLevelStr($level) {
    	if ($level > count(self::$access_levels)) {
    		throw new Exception("invalid level: $level");
    	}
    	return self::$access_levels[$level];
    }
    
    public static function getAccessLevels() {
        return self::$access_levels;
    }
	
}

?>