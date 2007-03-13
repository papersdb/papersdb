<?php ;

// $Id: pdHtmlPage.php,v 1.61 2007/03/13 14:03:32 loyola Exp $

/**
 * Contains a base class for all view pages.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requries classes to build the navigation menu. */
require_once 'includes/functions.php';
require_once 'includes/pdUser.php';
require_once 'includes/pdNavMenu.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';
require_once('HTML/QuickForm/Renderer/Default.php');
require_once 'HTML/Table.php';


/**
 * Base class for all HTML pages in PapersDB.
 *
 * Page can be made up of:
 *   - form
 *   - table
 *
 * @package PapersDB
 */
class pdHtmlPage {
    var $page_id;
    var $page_title;
    var $relative_url;
    var $redirectUrl;
    var $redirectTimeout;
    var $access_level;
    var $login_level;
    var $db;
    var $loginError;
    var $pageError;
    var $table;
    var $form;
    var $renderer;
    var $js;
    var $contentPre;
    var $contentPost;
    var $useStdLayout;
    var $hasHelpTooltips;
    var $form_controller;
    var $nav_menu;

    var $db_tables = array('additional_info',
                           'attachment_types',
                           'author',
                           'author_interest',
                           'cat_info',
                           'category',
                           'extra_info',
                           'help_fields',
                           'info',
                           'interest',
                           'pointer',
                           'pub_add',
                           'pub_author',
                           'pub_cat',
                           'pub_cat_info',
                           'publication',
                           'user',
                           'user_author',
                           'venue',
                           'venue_occur',
                           'venueold');

    /**
     * Constructor.
     */
    function pdHtmlPage($page_id, $title = null, $relative_url = null,
                        $login_level = PD_NAV_MENU_NEVER,
                        $useStdLayout = true) {
        if (MAINTENANCE == 1) {
            echo 'PapersDB is under maintenance, please check back later';
            exit;
        }

        // a derived page may already have needed access to the database prior
        // to invoking the base class constructor, so only create the database
        // object if not already set
        if (!is_object($this->db))
            $this->db = dbCreate();

        if (!$this->db->isOpen()) {
            switch (mysql_errno()) {
                case 1045:
                case 2000:
                    $this->contentPre
                        .= 'failed due to authentication errors. '
                        . 'Check database username and password<br>/';
                    break;

                case 2002:
                case 2003:
                default:
                    // General connection problem
                    $this->contentPre
                        .= 'failed with error [' . $errno . '] '
                        . htmlspecialchars(mysql_error()) . '.<br>';
                    break;
            }
            return;
        }

        $this->dbIntegrityCheck();
        $this->check_login();
        $this->nav_menu = new pdNavMenu();

        if (($page_id != null) && ($page_id != '')
            && (isset($this->nav_menu->nav_items[$page_id]))) {
            $this->page_id = $page_id;
            $this->page_title
                = $this->nav_menu->nav_items[$page_id]->page_title;
            $this->relative_url = $this->nav_menu->nav_items[$page_id]->url;
            $this->login_level
                = $this->nav_menu->nav_items[$page_id]->access_level;
        }
        else {
            $this->page_title   = $title;
            $this->relative_url = $relative_url;
            $this->login_level  = $login_level;
        }

        // ensure that the user is logged in if a page requires login access
        if (($this->login_level >= PD_NAV_MENU_LOGIN_REQUIRED)
            && ($this->access_level < 1)) {
            $this->loginError = true;
            return;
        }

        $this->redirectTimeout = 0;
        $this->table           = null;
        $this->form            = null;
        $this->renderer        = null;
        $this->loginError      = false;
        $this->pageError       = false;
        $this->useStdLayout    = $useStdLayout;
        $this->hasHelpTooltips = false;

        // start buffering output, it will be displayed in the toHtml() method
        ob_start();
    }

    function dbIntegrityCheck() {
        if (isset($_SESSION['dbcheck'])) return;

        $q = $this->db->query('show tables');

        if ($this->db->numRows($q) == 0) {
            echo "Database error encountered: not all tables available";
            die();
        }

        $r = $this->db->fetchObject($q);
        while ($r) {
            $tables[] = $r->Tables_in_pubDBdev;
            $r = $this->db->fetchObject($q);
        }

        if ($tables != $this->db_tables) {
            echo "Database error encountered: not all tables available";
            die();
        }
        $_SESSION['dbcheck'] = true;
    }

    /**
     * Assigns $this->access_level according to whether the user is logged
     * in or not.
     */
    function check_login() {
        $passwd_hash = "aicml";
        $this->access_level = 0;

        if (!isset($_SESSION['user'])) return;

        // remember, $_SESSION['password'] will be encrypted.
        if(!get_magic_quotes_gpc()) {
            $_SESSION['user']->login = addslashes($_SESSION['user']->login);
        }

        // addslashes to session login before using in a query.
        $q = $this->db->selectRow('user', 'password',
                                  array('login' => $_SESSION['user']->login),
                                  "Admin/check_login.php");

        // now we have encrypted pass from DB in $q->password,
        // stripslashes() just incase:

        $q->password = stripslashes($q->password);

        //compare:
        if ($q->password == $_SESSION['user']->password) {
            // valid password for login
            // they have correct info in session variables.

            if ($_SESSION['user']->verified == 1) {
                // user is valid
                $this->access_level = $_SESSION['user']->access_level;
            }
        }
        else {
            unset($_SESSION['user']); // kill incorrect session variables.
        }
    }


    function htmlPageHeader() {
        $result =
            "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n"
            . "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n"
            . "\"http://www.w3.org/TR/html4/strict.dtd\">\n"
            . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" '
            . "lang=\"en\">\n"
            . "<head>\n"
            . "<title>\n";

        // change the HTML title tag if this is the index page
        if ($this->page_title == 'Home')
            $result .= 'PapersDB';
        else
            $result .= $this->page_title;

        $result .= '</title>'
            . '<meta http-equiv="Content-Type" '
            . 'content="text/html; charset=iso-8859-1" />' . "\n";

        if ($this->redirectUrl != null) {
            $result .= '<meta http-equiv="refresh" content="5;url='
                . $this->redirectUrl . '" />' . "\n";
        }

        if (strstr($this->relative_url, '/'))
            $cssFile = '../style.css';
        else
            $cssFile = 'style.css';

        $result .= '<link rel="stylesheet" href="' . $cssFile . '" />' . "\n"
            . "</head>\n"
            . $this->js
            . "\n<body>\n";

        if($this->useStdLayout) {
            $result .= $this->pageHeader();
            $result .= $this->navMenu();
            $result .= '<div id="content">';
        }

        return $result;
    }

    function htmlPageFooter() {
        $result = '';
        if($this->useStdLayout) {
            $result .= '</div>' . $this->pageFooter();
        }

        if ($this->hasHelpTooltips) {
            if (strstr($this->relative_url, '/'))
                $jsFile = '../wz_tooltip.js';
            else
                $jsFile = 'wz_tooltip.js';

            $result
                .= '<script language="JavaScript" type="text/javascript" src="'
                . $jsFile . '"></script>';
        }

        // set up for google analytics
        //
        // note this code is added only on the real site
        if (strpos($_SERVER['PHP_SELF'], '~papersdb') !== false) {
            $result .= $this->googleAnalytics();
        }

        $result .= '</body></html>';

        return $result;
    }

    // set up for google analytics
    //
    // note this code is added only on the real site
    function googleAnalytics() {
        return '<script src="http://www.google-analytics.com/urchin.js" '
            . 'type="text/javascript">' . "\n"
            . '</script>' . "\n"
            . '<script type="text/javascript">' . "\n"
            . '_uacct = "UA-584619-1";' . "\n"
            . 'urchinTracker();' . "\n"
            . '</script>' . "\n";
    }

    /**
     * Renders the page.
     */
    function toHtml() {
        if (isset($this->redirectUrl) && ($this->redirectTimeout == 0)) {
            header('Location: ' . $this->redirectUrl);
            return;
        }

        $result = $this->htmlPageHeader();

        if ($this->loginError) {
            if (isset($this->contentPre))
                $result .= $this->contentPre;
            else
                $result .= $this->loginErrorMessage();

            if (isset($this->contentPost))
                $result .= $this->contentPost;
        }
        else if ($this->pageError) {
            if (isset($this->contentPre))
                $result .= $this->contentPre;
            else
                $result .= $this->errorMessage();

            if (isset($this->contentPost))
                $result .= $this->contentPost;
        }
        else {
            $result .= ob_get_contents();
            ob_end_clean();

            // debug
            //$this->contentPost .= '<pre>' . print_r($this, true) . '</pre>';

            if ($this->renderer != null) {
                if ($this->table != null)
                    $result .= $this->renderer->toHtml($this->table->toHtml());
                else
                    $result .= $this->renderer->toHtml();
            }
            else if ($this->table != null) {
                $result .= $this->table->toHtml();
            }
        }
        $result .= $this->htmlPageFooter();

        return $result;
    }

    function navMenu() {
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        foreach ($this->nav_menu->nav_items as $page_id => $item) {
            if (!$item->display || ($item->access_level <= PD_NAV_MENU_NEVER))
                continue;

            // the first AND statement displays the nav menu links
            // for someone with edit privilidges
            //
            // the second AND takes care of displaying the admin links
            //
            // the third and takes care of displaying the guest login level
            // (not logged in) links
            if ((($this->access_level > 0)
                 && ($item->access_level > PD_NAV_MENU_ALWAYS)
                 && ($item->access_level < PD_NAV_MENU_LEVEL_ADMIN))
                || (($this->access_level >= 2)
                    && ($item->access_level == PD_NAV_MENU_LEVEL_ADMIN))
                || (($this->access_level == 0)
                    && ($item->access_level < PD_NAV_MENU_LOGIN_REQUIRED))) {

                // only display search results if a search was performed
                if (($page_id == 'search_results')
                    && !isset($_SESSION['search_results'])
                    && !isset($_SESSION['search_url'])) {
                    continue;
                }

                if (($page_id == $this->page_id) || !$item->enabled) {
                    $options[$item->page_title] = '';
                }
                else
                    $options[$item->page_title] = $url_prefix . $item->url;

                // add redirection option to the login URL
                //
                // note: only add it if not at the login page
                if (($page_id == 'login')
                    && (strpos($_SERVER['PHP_SELF'], 'login.php') === false)) {
                    $options[$item->page_title]
                        .= '?redirect=' . $_SERVER['PHP_SELF'];

                    if ($_SERVER['QUERY_STRING'] != '')
                        $options[$item->page_title]
                            .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
        }

        $result = '<div id="nav"><ul>';

        if (is_array($options))
            foreach ($options as $key => $value) {
                if ($value == '')
                    $result .= '<li class="selected">' . $key . '</li>';
                else
                    $result
                        .= '<li><a href="' . $value . '">' . $key . '</a></li>';
            }

        $result .= "</ul>\n" . $this->quickSearchFormCreate() . '</div>';
        return $result;
    }

    function loginErrorMessage() {
        return '<br/>'
            . '<h4>You must be logged in to access this page.</h4>'
            . '</div>';
    }

    function errorMessage() {
        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        return '<br/>'
            . '<h4>An error has occurred</h4><br/>'
            . 'Please return to the <a href="' . $url . '">main page<a>.';
    }

    function pageHeader() {
        if ($this->access_level > 0) {
            $status = 'Logged in as: ' . $_SESSION['user']->login;

            if ($this->access_level >= 2) {
                $status .= ', DB : ' . DB_NAME;
            }
        }
        else {
            $status = 'Not Logged In';
        }

        $dir_prefix = '';
        if (strstr($this->relative_url, '/'))
            $dir_prefix = '../';

        return <<<END
            <div id="statusbar">{$status}</div>
            <div id="titlebar">
            <a href="http://www.uofaweb.ualberta.ca/science/">
            <img class="floatLeft" src="{$dir_prefix}images/science.gif"
            alt="Faculty of Science Home Page" border="0"/></a>
            <a href="http://www.ualberta.ca/">
            <img class="floatRight" src="{$dir_prefix}images/uofa_top.gif"
            alt="University of Alberta Home Page" border="0"/></a>
            </div>

            <div id="header">
            <h1>PapersDB</h1>
            </div>

END;
    }

    function pageFooter() {
        $uofa_logo = 'images/uofa_logo.gif';
        $aicml_logo = 'images/aicml.png';

        if (strstr($this->relative_url, '/')) {
            $uofa_logo = '../' . $uofa_logo;
            $aicml_logo = '../' . $aicml_logo;
        }

        return <<<END
            <div id="footer">
            For any questions/comments about the Papers Database please e-mail
            <a href="mailto:papersdb@cs.ualberta.ca">PapersDB Administrator</a>
            </div>
            <div id="footer2">
            <table width="100%">
            <tr>
            <td>
            <a href="http://www.ualberta.ca">
            <img src="{$uofa_logo}" alt="University of Alberta Logo" />
            </a>
            </td>
            <td>
            <a href="http://kingman.cs.ualberta.ca/">
            <img src="{$aicml_logo}" alt="AICML Logo" />
            </a>
            </td>
            <td>
            <span id="copyright">
            <ul>
            <li>Copyright &copy; 2002-2007</li>
                                     </ul>
                                     </span>
                                     </td>
                                     </tr>
                                     </table>
                                     </div>

END;
    }

    function helpTooltip($text, $varname, $class = 'help') {
        $this->hasHelpTooltips = true;
        return '<span class="' . $class . '">'
            . '<a href="javascript:void(0);" onmouseover="this.T_WIDTH=300;'
            . 'return escape(' . $varname . ')">' . $text . '</a></span>';
    }

    function &confirmForm($name, $action = null, $label = 'Delete') {
        $form = new HTML_QuickForm($name, 'post', $action, '_self',
                                   'multipart/form-data');
        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'submit', 'submit', $label),
                HTML_QuickForm::createElement(
                    'button', 'cancel', 'Cancel',
                    array('onclick' => 'history.back()'))
                ),
            null, null, '&nbsp;', false);
        return $form;
    }

    function quickSearchFormCreate() {
        if (strstr($this->relative_url, '/') !== false)
            $script = '../search_publication_db.php';
        else
            $script = 'search_publication_db.php';

        $form = new HTML_QuickForm('quickSearchForm', 'get', $script);
        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'text', 'search', null,
                    array('size' => 12, 'maxlength' => 80)),
                HTML_QuickForm::createElement('submit', 'Quick', 'Search')
                ),
            null, null, null);

        // create a new renderer because $form->defaultRenderer() creates
        // a single copy
        $renderer =& new HTML_QuickForm_Renderer_Default();
        $form->accept($renderer);

        return $renderer->toHtml();
    }

    function navMenuItemDisplay($page_id, $enable) {
        assert('isset($this->nav_menu->nav_items[$page_id])');
        $this->nav_menu->nav_items[$page_id]->display = $enable;
    }

    function navMenuItemEnable($page_id, $enable) {
        assert('isset($this->nav_menu->nav_items[$page_id])');
        $this->nav_menu->nav_items[$page_id]->enabled = $enable;
    }

    function debugVar($name,$data) {
        $captured = explode("\n",debug_capture_print_r($data));
        $this->contentPost .= $name . "<br/>\n<pre>";
        foreach  ($captured as $line) {
            $this->contentPost .= debug_colorize_string($line)
                . "\n";
        }
        $this->contentPost .= "</pre>\n";
    }

    function getPubIcons(&$pub, $flags = 0xf) {
        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if (($flags & 0x1) && ($pub->paper != 'No paper')) {
            $html .= '<a href="' . $pub->paperAttGetUrl() . '">';

            if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                $html .= '<img src="' . $url_prefix
                    . 'images/pdf.gif" alt="PDF" '
                    . 'height="18" width="17" border="0" align="top">';
            }
            else if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                $html .= '<img src="' . $url_prefix
                    . 'images/ppt.gif" alt="PPT" height="18" '
                    . 'width="17" border="0" align="top">';
            }
            else if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                $html .= '<img src="' . $url_prefix
                    . 'images/ps.gif" alt="PS" height="18" '
                    . 'width="17" border="0" align="top">';
            }
            $html .= '</a>';
        }

        if ($flags & 0x2) {
            $html .= '<a href="' . $url_prefix
                . 'view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="' . $url_prefix
                .'images/viewmag.png" title="view" alt="view" '
                . ' height="16" width="16" border="0" align="top" /></a>';
        }

        if (($flags & 0x4) && ($this->access_level > 0)) {
            $html .= '<a href="' . $url_prefix
                . 'Admin/add_pub1.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="' . $url_prefix
                . 'images/pencil.png" title="edit" alt="edit" '
                . ' height="16" width="16" border="0" align="top" />'
                . '</a>';
        }

        if (($flags & 0x8) && ($this->access_level > 0)) {
            $html .= '<a href="' . $url_prefix
                . 'Admin/delete_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="' . $url_prefix
                . 'images/kill.png" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="top" /></a>';
        }

        return $html;
    }

    function getPubAddAttIcons(&$att) {
        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if (preg_match("/\.(pdf|PDF)$/", $att->location)) {
            $html .= '<img src="' . $url_prefix
                . 'images/pdf.gif" alt="PDF" '
                . 'height="18" width="17" border="0" '
                . 'align="top">';
        }
        else if (preg_match("/\.(ppt|PPT)$/", $att->location)) {
            $html .= '<img src="' . $url_prefix
                . 'images/ppt.gif" alt="PPT" '
                . 'height="18" width="17" border="0" '
                . 'align="top">';
        }
        else if (preg_match("/\.(ps|PS)$/", $att->location)) {
            $html .= '<img src="' . $url_prefix
                . 'images/ps.gif" alt="PS" '
                . 'height="18" width="17" border="0" '
                . 'align="top">';
        }

        return $html;
    }

    function getAuthorIcons(&$author, $flags = 0x7) {
        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if ($flags & 0x1)
            $html .= '<a href="' . $url_prefix
                . 'view_author.php?author_id='
                . $author->author_id . '">'
                . '<img src="' . $url_prefix
                . 'images/viewmag.png" title="view" alt="view" height="16" '
            . 'width="16" border="0" align="top" /></a>';

        if ($this->access_level > 0) {
            if ($flags & 0x2)
                $html .= '<a href="' . $url_prefix
                    . 'Admin/add_author.php?author_id='
                    . $author->author_id . '">'
                    . '<img src="' . $url_prefix
                    . 'images/pencil.png" title="edit" alt="edit" '
                    . 'height="16" width="16" border="0" align="top" /></a>';

            if ($flags & 0x4)
                $html .= '<a href="' . $url_prefix
                    . 'Admin/delete_author.php?author_id='
                    . $author->author_id . '">'
                    . '<img src="' . $url_prefix
                    . 'images/kill.png" title="delete" alt="delete" '
                    . 'height="16" width="16" border="0" align="top" /></a>';
        }

        return $html;
    }

    function getCategoryIcons(&$category, $flags = 0x3) {
        if ($this->access_level < 1) return null;

        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if ($flags & 0x1)
            $html .= '<a href="' . $url_prefix
                . 'Admin/add_category.php?cat_id='
                . $category->cat_id . '">'
                . '<img src="' . $url_prefix
                . 'images/pencil.png" title="edit" alt="edit" '
                . 'height="16" width="16" border="0" align="top" /></a>';

        if ($flags & 0x2)
            $html .= '<a href="' . $url_prefix
                . 'Admin/delete_category.php?cat_id='
                . $category->cat_id . '">'
                . '<img src="' . $url_prefix
                . 'images/kill.png" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="top" /></a>';

        return $html;
    }

    function getVenueIcons(&$venue, $flags = 0x3) {
        if ($this->access_level < 1) return null;

        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if ($flags & 0x1)
            $html .= '<a href="' . $url_prefix
                . 'Admin/add_venue.php?venue_id='
                . $venue->venue_id . '">'
                . '<img src="' . $url_prefix
                . 'images/pencil.png" title="edit" alt="edit" '
                . 'height="16" width="16" border="0" align="middle" /></a>';

        if ($flags & 0x2)
            $html .= '<a href="' . $url_prefix
                . 'Admin/delete_venue.php?venue_id='
                . $venue->venue_id . '">'
                . '<img src="' . $url_prefix
                . 'images/kill.png" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="middle" /></a>';

        return $html;
    }
}

?>
