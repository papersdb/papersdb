<?php ;

// $Id: pdNavMenu.php,v 1.19 2007/11/02 16:36:29 loyola Exp $

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
    public $all_items = array(
        'home'               => array('Home', 'index.php',
                                      pdNavMenuItem::MENU_LOGIN_NOT_REQ),
        'add_publication'    => array('Add Publication',
                                      'Admin/add_pub1.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
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
        'view_publications'  => array('View Publications',
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
        'edit_user'          => array('User Preferences',
                                      'Admin/edit_user.php',
                                      pdNavMenuItem::MENU_LOGIN_REQUIRED),
        'add_category'       => array('Add Category', 'Admin/add_category.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN),
        'sanity_checks'      => array('Sanity Checks',
                                      'diag/sanity_checks.php',
                                      pdNavMenuItem::MENU_LEVEL_ADMIN)
        );

    public $not_logged_in = array(
        'Home' ,
        'Search' => array(
            'advanced_search',
            'search_results'),
        'Publications' => array(
            'add_publication',
            'view_publications'),
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
            'logout',
            'edit_user')
        );
        
    public function __construct() {
        foreach ($this->all_items as $id => $item) {
            $this->nav_items[$id]
                = new pdNavMenuItem($id, $item[0], $item[1], $item[2]);
        }
    }
}

?>
