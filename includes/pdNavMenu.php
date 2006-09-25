<?php ;

// $Id: pdNavMenu.php,v 1.9 2006/09/25 19:59:09 aicmltec Exp $

/**
 * Contains the class that builds the navigation menu.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Flags used when loading information from the database. */
define('PD_NAV_MENU_NEVER',          0);
define('PD_NAV_MENU_ALWAYS',         1);
define('PD_NAV_MENU_LOGIN_NOT_REQ',  2);
define('PD_NAV_MENU_LOGIN_REQUIRED', 3);
define('PD_NAV_MENU_LEVEL_ADMIN',    4);

/**
 * Class for a navigation menu item.
 *
 * @package PapersDB
 */
class pdNavMenuItem {
    var $id;
    var $page_title;
    var $url;
    var $access_level;
    var $display;
    var $enabled;

    function pdNavMenuItem($id, $page_title, $url, $access_level,
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
    var $nav_items;

    // private date to this class
    //
    // used to build the navigation menu and other things.
    //
    // kinda kludgey but works
    //
    var $nav_items_init = array(
        'home'               => array('Home', 'index.php',
                                      PD_NAV_MENU_LOGIN_NOT_REQ),
        'add_publication'    => array('Add Publication',
                                      'Admin/add_pub1.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'add_author'         => array('Add Author',
                                      'Admin/add_author.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'add_category'       => array('Add Category', 'Admin/add_category.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'add_venue'          => array('Add Venue', 'Admin/add_venue.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'delete_publication' => array('Delete Publication',
                                      'Admin/delete_pbublication.php',
                                      PD_NAV_MENU_NEVER),
        'delete_author'      => array('Delete Author',
                                      'Admin/delete_author.php',
                                      PD_NAV_MENU_NEVER),
        'delete_category'    => array('Delete Category',
                                      'Admin/delete_category.php',
                                      PD_NAV_MENU_NEVER),
        'delete_venue'       => array('Delete Venue', 'Admin/delete_venue.php',
                                      PD_NAV_MENU_NEVER),
        'delete_interest'    => array('Delete Interest',
                                      'Admin/delete_interest.php',
                                      PD_NAV_MENU_NEVER),
        'edit_publication'   => array('Edit Publication',
                                      'Admin/add_pub1.php',
                                      PD_NAV_MENU_NEVER),
        'edit_author'        => array('Edit Author',
                                      'Admin/add_author.php',
                                      PD_NAV_MENU_NEVER),
        'advanced_search'    => array('Advanced Search', 'advanced_search.php',
                                      PD_NAV_MENU_LOGIN_NOT_REQ),
        'search_results'    => array('Search Results',
                                     'search_results.php',
                                      PD_NAV_MENU_LOGIN_NOT_REQ),
        'all_publications'   => array('All Publications', 'list_publication.php',
                                      PD_NAV_MENU_LOGIN_NOT_REQ),
        'all_authors'        => array('All Authors', 'list_author.php',
                                      PD_NAV_MENU_LOGIN_NOT_REQ),
        'all_categories'     => array('All Categories', 'list_categories.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'all_venues'         => array('All Venues', 'list_venues.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'logout'             => array('Logout', 'logout.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'login'              => array('Login or Register', 'login.php',
                                      PD_NAV_MENU_ALWAYS),
        'edit_user'          => array('User Preferences',
                                      'Admin/edit_user.php',
                                      PD_NAV_MENU_LOGIN_REQUIRED),
        'view_publications'  => array('View Publication',
                                      'view_publication.php',
                                      PD_NAV_MENU_NEVER),
        'view_authors'       => array('Author Information',
                                      'view_author.php',
                                      PD_NAV_MENU_NEVER),
        'check_attachments'  => array('Check Attachments',
                                      'diag/check_attachments.php',
                                      PD_NAV_MENU_LEVEL_ADMIN),
        'author_report'      => array('Author Report',
                                      'diag/author_report.php',
                                      PD_NAV_MENU_LEVEL_ADMIN)
        );

    function pdNavMenu() {
        foreach ($this->nav_items_init as $id => $item) {
            $this->nav_items[$id]
                = new pdNavMenuItem($id, $item[0], $item[1], $item[2]);
        }
    }
}

?>
