<?php

/**
 * Contains the class that builds the navigation menu.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/**
 * Class for a navigation menu item.
 *
 * @package PapersDB
 */
class pdNavMenuItem {
    public $id;
    public $page_title;
    public $url;
    public $access_level;
    public $display;
    public $enabled;
    public $sub_items;
    
	/** Flags used when loading information from the database. */
	const MENU_NEVER = 0;
	const MENU_ALWAYS = 1;
	const MENU_LOGIN_NOT_REQ = 2;
	const MENU_LOGIN_REQUIRED = 3;
	const MENU_LEVEL_ADMIN = 4;

    public function __construct($id, $page_title, $url, $access_level,
                           $display = 1, $enabled = 1) {
        $this->id           = $id;
        $this->page_title   = $page_title;
        $this->url          = $url;
        $this->access_level = $access_level;
        $this->display      = $display;
        $this->enabled      = $enabled;
        $this->sub_items    = array();
    }
};

/**
 * Class that builds the navigation menu.
 *
 * @package PapersDB
 */
class pdNavMenu {
    public $nav_items;

    // used to build the navigation menu and other things.
    //
    // kinda kludgey but works
    //
    public static $all_items = array(
        'home'               => array('Home', 'index.php',
                                      pdNavMenuItem::MENU_LOGIN_NOT_REQ),
        'add_publication'    => array('Add Publication Entry',
                                      'Admin/add_pub1.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'validate_publications' => array('Validate Publication Entries',
                                      'Admin/validate_pubs.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'add_author'         => array('Add Author',
                                      'Admin/add_author.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'batch_add_authors'  => array('Batch Add Authors',
                                      'Admin/batch_add_authors.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'add_venue'          => array('Add Venue', 'Admin/add_venue.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'advanced_search'    => array('Advanced Search', 'advanced_search.php',
                                      pdNavMenuItem::MENU_LOGIN_NOT_REQ),
        'search_results'    => array('Search Results',
                                     'search_results.php',
                                      pdNavMenuItem::MENU_LOGIN_NOT_REQ),
        'view_publications'  => array('View Publication Entries',
                                      'list_publication.php',
                                      pdNavMenuItem::MENU_LOGIN_NOT_REQ),
        'all_authors'        => array('View Authors', 'list_author.php',
                                      pdNavMenuItem::MENU_LOGIN_NOT_REQ),
        'all_venues'         => array('View Venues', 'list_venues.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'all_categories'     => array('View Categories', 'list_categories.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'logout'             => array('Logout', 'logout.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'login'              => array('Login or Register', 'login.php',
                                      pdNavMenuItem::MENU_ALWAYS),
        'authorize_new_users'=> array('Authorize new users', 'Admin/authorize_new_users.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'edit_user'          => array('User Preferences',
                                      'Admin/edit_user.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'add_category'       => array('Add Category', 'Admin/add_category.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'aicml_staff'        => array('AICML Staff',
                                      'diag/aicml_staff.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'sanity_checks'      => array('Sanity Checks',
                                      'diag/sanity_checks.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'aicml_stats'        => array('AICML Publications Statistics', 
        							  'diag/aicml_stats.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'aicml_publications' => array('AICML Publications', 
        							  'diag/aicml_publications.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'aicml_publications_no_rank' => array('AICML Publications (no rank)', 
                                      'diag/aicml_publications_no_rank.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'tag_non_ml'           => array('Tag Non ML Papers', 
        							  'diag/tag_non_ml.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN)
        );

    public static $menu = array(
        'Home' => array(),
        'Search' => array(
            'advanced_search',
            'search_results'),
        'Publications' => array(
            'add_publication',
            'view_publications',
            'validate_publications'),
        'Authors' => array(
            'add_author',
            'batch_add_authors',
            'all_authors'),
        'Venue'  => array(
            'add_venue',
            'all_venues'
            ),
        'Categories'  => array(
            'add_category',
            'all_categories'
            ),
        'User'  => array(
            'login',
            'logout',
            'authorize_new_users',
            'edit_user'),
        'Diagnostics'  => array(
            'aicml_staff',
            'sanity_checks',
            'aicml_stats',
            'aicml_publications',
            'aicml_publications_no_rank',
            'tag_non_ml')
        );
        
    public function __construct($access_level, $current_page_id) {
        foreach (self::$menu as $id => $sub_items) {
        	if ($id == 'Home') {
        		$item =& self::$all_items['home'];
	            $this->nav_items[$id]
	            	= new pdNavMenuItem($id, $item[0], $item[1], $item[2]);
        	}
        	else
	            $this->nav_items[$id] = new pdNavMenuItem(
    	        	$id, $id, null, null, $display = 1, $enabled = 1);
            	
        	foreach ($sub_items as $sub_id) {           	
	        	assert('isset(self::$all_items[$sub_id])');

	        	$sub_item =& self::$all_items[$sub_id];
       		
                if (($access_level == 0)
                     && ($sub_item[2] >= pdNavMenuItem::MENU_LOGIN_REQUIRED))
                     continue;
                     
                if (($access_level < 2)
                    && ($sub_item[2] >= pdNavMenuItem::MENU_LEVEL_ADMIN))
                     continue;
                     
                // no need to display the login item if user is logged in
                if (($access_level >= 1) && ($sub_id == 'login'))
                     continue;
                        
                // only display search results if a search was performed
        		if (($sub_id == 'search_results')
                    && !isset($_SESSION['search_results'])
                    && !isset($_SESSION['search_url']))
	                continue;
        		
	            $this->nav_items[$id]->sub_items[$sub_id] 
	            	= new pdNavMenuItem($sub_id, $sub_item[0], $sub_item[1], $sub_item[2]);
        	}
        }
        
        // go through items and remove the ones without sub items
        foreach ($this->nav_items as $id => $items) {
            if ($id == 'Home') continue;
            
        	if (isset($items->sub_items) && (count($items->sub_items) == 0))
        		unset($this->nav_items[$id]);
        }
    }
    
    public function findPageId($page_id) {
        if (isset(self::$all_items[$page_id])) {
        	$item = self::$all_items[$page_id]; 
	        return new pdNavMenuItem($page_id, $item[0], $item[1], $item[2]);
        }
	    return null;
    }
}

?>
