<?php

/*-----------------------------------------------------------------------------
 *
 * The information contained herein is proprietary and confidential to Alberta
 * Ingenuity Centre For Machine Learning (AICML) and describes aspects of AICML
 * products and services that must not be used or implemented by a third party
 * without first obtaining a license to use or implement.
 *
 * Copyright 2008 Alberta Ingenuity Centre For Machine Learning.
 *
 *-----------------------------------------------------------------------------
 */

/**
 * Parses a search string that contains the following operators:
 * 
 *   - OR and |
 *   - quoted phrases
 * 
 * Search terms separated by spaces are considered to be AND'ed.
 */
class SearchTermParser {
    private $search_str;
    private $terms;
    protected static $common_words = array(
    	"a", "all", "am", "an", "and","any","are","as", "at", "be","but","can",
    	"did","do","does","for", "from", "had", "has","have","here","how","i",                             
    	"if","in","is", "it","no", "not","of","on","or", "so","that","the", 
    	"then","there", "this","to", "too","up","use", "what","when","where", 
    	"who", "why","you");
    
    public function __construct($search_str) {
        $this->search_str = $search_str;
        $this->terms = array();
        $words = $this->makeWordList($search_str);
        $this->terms = $this->makeLogicalWordList($words);
    }
    
    /**
     * Returns a two dimensional array. The first dimension has terms that
     * are ANDed together, the second dimension has terms that are ORed 
     * together.
     */
    public function &getWordList() {
    	return $this->terms;
    }
    
    /**
     * Makes a list of the words or phrases passed in with space as the 
     * delimiter. Strings within quotes are treated as a phrase.
     */
    private function &makeWordList($search_str) {
        $within_quotes = false;
        $word = '';
        $len = strlen($search_str);
        
        $words = array();
        for ($i = 0; $i < $len; ++$i) {
            $ch = $search_str[$i];
            if (($ch == '"') || ($ch == '\'')) {
                $within_quotes = ! $within_quotes;
            }
            else if ($ch == ' ') {
                if ($within_quotes) {
                    $word .= $ch;                    
                }
                else {
                    if ($word != '') {
                        $words[] = $word;
                    }
                    $word = '';
                }
            }
            else {
                $word .= $ch;
            }
        }
        
        if ($word != '') {
            $words[] = trim($word);
        }
        return $words;
    }
    
    /**
     * Makes a two dimensional array. The first dimension has terms that
     * are ANDed together, the second dimension has terms that are ORed 
     * together.
     */
    private function &makeLogicalWordList(&$words) {
    	$or_condition = false;
    	$log_words = array();
    	foreach ($words as $key => $word) {
    		if (($word == 'OR') || ($word == '|')) {
    			$or_condition = true;
    		}
    		else {
    			if ($or_condition) {
    				$count = count($log_words);
    				$index = ($count > 0) ? $count - 1 : 0;
    				$log_words[$index][] = $word;
    				$or_condition = false;
    			}
    			else {
    				$log_words[] = array($word);
    			}
    		}
    	}
    	
    	// now remove common words
    	foreach ($log_words as $and_term) {
    		foreach ($and_term as $key => $or_term) {
    			if (in_array($or_term, self::$common_words)) {
    				unset($and_term[$key]);
    			}
    		}
    	}
    	return $log_words;
    }
}

// the following code runs only in CLI mode.
//
// should be replaced by unit test (in it's own file).
if (PHP_SAPI == "cli") {
    require_once('../../php_common/functions.php');
    
    if (!isset($argv[1])) {
        die("parameter expected\n");
    }
    $parser = new SearchTermParser($argv[1]);
    $wordList = $parser->getWordList();
    debugVar('$wordList', $wordList);
}

?>