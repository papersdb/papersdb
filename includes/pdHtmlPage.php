<?php ;

// $Id: pdHtmlPage.php,v 1.112 2007/11/13 16:50:56 loyola Exp $

/**
 * Contains a base class for all view pages.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

ini_set("include_path",  ini_get("include_path") . ':..');

/** Requried classes to build the navigation menu. */
require_once 'includes/functions.php';
require_once 'includes/pdDb.php';
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
    protected $page_id;
    protected $page_title;
    protected $relative_url;
    protected $redirectUrl;
    protected $redirectTimeout;
    protected $access_level;
    protected $login_level;
    protected $db;
    protected $loginError;
    protected $pageError;
    protected $table;
    protected $form;
    protected $renderer;
    protected $js;
    protected $useStdLayout;
    protected $hasHelpTooltips;
    protected $form_controller;
    protected $nav_menu;

    const HTML_TOP_CONTENT = '
<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
  "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>';

    const GOOGLE_ANALYTICS = '
<script
  src="http://www.google-analytics.com/urchin.js"
  type="text/javascript">
</script>
<script type="text/javascript">
  _uacct = "UA-584619-1";
  urchinTracker();
</script>';

    /**
     * Constructor.
     */
    public function __construct($page_id, $title = null, $relative_url = null,
                                $login_level = pdNavMenuItem::MENU_NEVER,
                                $useStdLayout = true) {
        if (MAINTENANCE == 1) {
            echo 'PapersDB is under maintenance, please check back later';
            exit;
        }

        session_start();

        // start buffering output, it will be displayed in the toHtml() method
        ob_start();

        // initialize session variables
        if ((get_class($this) != 'add_pub1')
            && (get_class($this) != 'add_pub2')
            && (get_class($this) != 'add_pub3')
            && (get_class($this) != 'add_pub4')
            && (get_class($this) != 'add_pub_submit')
            && (get_class($this) != 'add_author')
            && (get_class($this) != 'author_confirm')
            && (get_class($this) != 'add_venue')) {
            pubSessionInit();
        }

        // a derived page may already have needed access to the database prior
        // to invoking the base class constructor, so only create the database
        // object if not already set
        if (!is_object($this->db)) {
            $this->db = pdDb::newFromParams();
        }

        $this->check_login();
        $this->nav_menu = new pdNavMenu($this->access_level, $page_id);

        if (!empty($page_id)) {
            $nav_item = $this->nav_menu->findPageId($page_id);

            if ($nav_item != null) {
                $this->page_id     = $page_id;
    	        $this->page_title   = $nav_item->page_title;
            	$this->relative_url = $nav_item->url;
                $this->login_level  = $nav_item->access_level;
            }
        }
        else {
            $this->page_title   = $title;
            $this->relative_url = $relative_url;
            $this->login_level  = $login_level;
        }

        $this->redirectTimeout = 0;
        $this->table           = null;
        $this->form            = null;
        $this->renderer        = null;
        $this->loginError      = false;
        $this->pageError       = false;
        $this->useStdLayout    = $useStdLayout;
        $this->hasHelpTooltips = false;

        // ensure that the user is logged in if a page requires login access
        if ((($this->login_level >= pdNavMenuItem::MENU_LOGIN_REQUIRED)
             || (strpos($this->relative_url, 'Admin/') !== false))
            && ($this->access_level < 1)) {
            $this->loginError = true;
            return;
        }
    }

    public function __destruct() {
        if (is_object($this->db) && $this->db->isOpen())
            $this->db->close();
    }

    /**
     * Assigns $this->access_level according to whether the user is logged
     * in or not.
     */
    private function check_login() {
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

        // make sure user exists
        if ($q === false) return;

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

    private function stripSlashesArray($arr) {
        assert('is_array($arr)');

        $new_arr = array();
        foreach ($arr as $key => $value) {
            if (is_array($arr[$key])) {
                $new_arr[stripslashes($key)]
                    = $this->stripSlashesArray($arr[$key]);
            }
            else
                $new_arr[stripslashes($key)] = stripslashes($value);
        }

        return $new_arr;
    }

    protected function loadHttpVars($get = true, $post = true) {
        $arr = null;
        if ($get && ($_SERVER['REQUEST_METHOD'] == 'GET')) {
            if (!isset($_GET)) return;
            $arr =& $_GET;
        }
        else if ($post && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
            if (!isset($_POST)) return;
            $arr =& $_POST;
        }

        if (!is_array($arr) || (count($arr) == 0)) return;

        $ob_vars =& get_object_vars($this);

        foreach (array_keys($arr) as $key) {
            if (in_array($key, $ob_vars)) {
                if (is_array($arr[$key]))
                    $this->$key = $this->stripSlashesArray($arr[$key]);
                else
                    $this->$key = stripslashes($arr[$key]);
            }
        }
    }

    private function htmlPageHeader() {
        $result = self::HTML_TOP_CONTENT;

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

        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        $result .= '<link rel="stylesheet" href="' . $url_prefix
            . 'style.css" />' . "\n"
            . "</head>\n\n<body>\n";

        if($this->useStdLayout) {
            $result .= $this->pageHeader();
            $result .= $this->navMenu();
            $result .= '<div id="content"><p/>';
        }

        return $result;
    }

    private function htmlPageFooter() {
        $result = '';
        if($this->useStdLayout) {
            $result .= '</div>' . $this->pageFooter();
        }

        // set up for google analytics
        //
        // note this code is added only on the real site
        if (strpos($_SERVER['PHP_SELF'], '~papersdb') !== false) {
            $result .= self::GOOGLE_ANALYTICS;
        }

        $result .= "<script type=\"text/JavaScript\">\n"
            . "//<![CDATA[\n"
            . $this->js
            . "//]]>"
            . "</script>\n";

        if ($this->hasHelpTooltips) {
            if (strstr($this->relative_url, '/'))
                $jsFile = '../js/wz_tooltip.js';
            else
                $jsFile = 'js/wz_tooltip.js';

            $result
                .= '<script type="text/javascript" src="'
                . $jsFile . '"></script>';
        }

        $result .= '</body></html>';

        return $result;
    }

    /**
     * Renders the page.
     */
    public function toHtml() {
        if (isset($this->redirectUrl) && ($this->redirectTimeout == 0)) {
            session_write_close();
            header('Location: ' . $this->redirectUrl);
            return;
        }

        $result = $this->htmlPageHeader();

        if (ob_get_length() > 0) {
            $result .= ob_get_contents();
            ob_end_clean();
        }

        if ($this->loginError)
            $result .= $this->loginErrorMessage();
        else if ($this->pageError)
            $result .= $this->errorMessage();
        else if (($this->renderer != null) && ($this->table != null))
            $result .= $this->renderer->toHtml($this->table->toHtml());
        else if ($this->renderer != null)
            $result .= $this->renderer->toHtml();
        else if ($this->table != null)
            $result .= $this->table->toHtml();

        $result .= $this->htmlPageFooter();

        return $result;
    }

    private function navMenu() {
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        $result = '<div id="nav"><ul>';

        foreach ($this->nav_menu->nav_items as $page_id => $item) {
            if ($page_id == 'Home') {
                $result .= '<li><a href="' . $url_prefix . $item->url . '"';

                if ($this->page_id == 'home')
                    $result .= ' class="selected"';

                $result .= '>' . $item->page_title . '</a></li>';
                continue;
            }
            else if (count($item->sub_items) == 0)
                continue;

            $result .= '<li>' . $item->page_title . '<ul>';

            foreach ($item->sub_items as $sub_page_id => $sub_item) {
            	// derived class can override nav menu settings, check for
            	// each page to be enabled before displaying it
                if (!$sub_item->display
                    || ($sub_item->access_level <= pdNavMenuItem::MENU_NEVER))
                    continue;

            	if (($sub_page_id == $this->page_id) || !$sub_item->enabled) {
                    $result .= '<li><a href="#" class="selected">';
            	}
            	else {
                    $url = $url_prefix . $sub_item->url;

                    // if not at the login page add redirection option to the login URL
    	            if (($sub_page_id == 'login')
                        && (strpos($_SERVER['PHP_SELF'], 'login.php') === false)) {
            	        $url .= '?redirect=' . $_SERVER['PHP_SELF'];

    	                if ($_SERVER['QUERY_STRING'] != '')
                            $url .= '?' . $_SERVER['QUERY_STRING'];
            	    }

                    $result .= '<li><a href="' . $url . '">';
            	}

            	$result .= $sub_item->page_title . '</a>';
            }

            $result .= '</ul></li>';
        }

        $result .= "</ul>\n" . $this->quickSearchFormCreate() . '</div>';
        return $result;
    }

    private function loginErrorMessage() {
        return '<br/>'
            . '<h4>You must be logged in to access this page.</h4>'
            . '</div>';
    }

    private function errorMessage() {
        $pos = strpos($_SERVER['PHP_SELF'], 'papersdb');
        $url = substr($_SERVER['PHP_SELF'], 0, $pos) . 'papersdb';

        return '<br/>'
            . '<h4>An error has occurred</h4><br/>'
            . 'Please return to the <a href="' . $url . '">main page<a>.';
    }

    private function pageHeader() {
        if ($this->access_level > 0) {
            $status = 'Logged in as: ' . $_SESSION['user']->login;

            if ($this->access_level >= 2) {
                $status .= ', DB : ' . DB_NAME;
            }
        }
        else {
            $status = 'Not Logged In';
        }

        return <<<END
<div id="container">
<div id="statusbar"><h1>{$status}</h1></div>
<ul id="titlebar">
  <li id="compsci">
    <a href="http://www.uofaweb.ualberta.ca/science/">
    FACULTY OF SCIENCE</a>
  </li>
  <li id="uofa">
    <a href="http://www.ualberta.ca/">
    UNIVERSITY OF ALBERTA</a>
  </li>
</ul>
<div id="header"><h1>PapersDB</h1></div>
END;
    }

    private function pageFooter() {
        $uofa_logo = 'images/uofa_logo.gif';
        $aicml_logo = 'images/aicml.gif';

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
</div>
END;
    }

    protected function helpTooltip($text, $varname, $class = 'help') {
        $this->hasHelpTooltips = true;
        return '<span class="' . $class . '">'
            . '<a href="javascript:void(0);" onmouseover="this.T_WIDTH=300;'
            . 'return escape(' . $varname . ')">' . $text . '</a></span>';
    }

    protected function &confirmForm($name, $action = null, $label = 'Delete') {
        $form = new HTML_QuickForm($name, 'post', $action, '_self',
                                   'multipart/form-data');
        $form->addGroup(
            array(
                HTML_QuickForm::createElement(
                    'button', 'cancel', 'Cancel',
                    array('onclick' => 'history.back()')),
                HTML_QuickForm::createElement(
                    'submit', 'submit', $label)
                ),
            null, null, '&nbsp;', false);
        return $form;
    }

    private function quickSearchFormCreate() {
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
        $renderer = new HTML_QuickForm_Renderer_Default();
        $form->accept($renderer);

        return $renderer->toHtml();
    }

    protected function navMenuItemDisplay($page_id, $enable) {
       	$nav_item = $this->nav_menu->findPageId($page_id);
       	if ($nav_item == null) return;
        $nav_item->display = $enable;
    }

    protected function navMenuItemEnable($page_id, $enable) {
       	$nav_item = $this->nav_menu->findPageId($page_id);
       	if ($nav_item == null) return;
        $nav_item->enabled = $enable;
    }

    protected function getPubIcons($pub, $flags = 0xf) {
        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if (($flags & 0x1) && (strtolower($pub->paper) != 'no paper')) {
            $html .= '<a href="' . $pub->paperAttGetUrl() . '">';

            if (preg_match("/\.(pdf|PDF)$/", $pub->paper)) {
                $html .= '<img src="' . $url_prefix
                    . 'images/pdf.gif" alt="PDF" '
                    . 'height="18" width="17" border="0" align="top" />';
            }
            else if (preg_match("/\.(ppt|PPT)$/", $pub->paper)) {
                $html .= '<img src="' . $url_prefix
                    . 'images/ppt.gif" alt="PPT" height="18" '
                    . 'width="17" border="0" align="top" />';
            }
            else if (preg_match("/\.(ps|PS)$/", $pub->paper)) {
                $html .= '<img src="' . $url_prefix
                    . 'images/ps.gif" alt="PS" height="18" '
                    . 'width="17" border="0" align="top" />';
            }
            $html .= '</a>';
        }

        if ($flags & 0x2) {
            $html .= '<a href="' . $url_prefix
                . 'view_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="' . $url_prefix
                .'images/viewmag.gif" title="view" alt="view" '
                . ' height="16" width="16" border="0" align="top" /></a>';
        }

        if (($flags & 0x4) && ($this->access_level > 0)) {
            $html .= '<a href="' . $url_prefix
                . 'Admin/add_pub1.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="' . $url_prefix
                . 'images/pencil.gif" title="edit" alt="edit" '
                . ' height="16" width="16" border="0" align="top" />'
                . '</a>';
        }

        if (($flags & 0x8) && ($this->access_level > 0)) {
            $html .= '<a href="' . $url_prefix
                . 'Admin/delete_publication.php?pub_id='
                . $pub->pub_id . '">'
                . '<img src="' . $url_prefix
                . 'images/kill.gif" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="top" /></a>';
        }

        return $html;
    }

    protected function getPubAddAttIcons($att) {
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

    protected function getAuthorIcons($author, $flags = 0x7) {
        $html = '';
        $url_prefix = '';
        if (strstr($this->relative_url, '/'))
            $url_prefix = '../';

        if ($flags & 0x1)
            $html .= '<a href="' . $url_prefix
                . 'view_author.php?author_id='
                . $author->author_id . '">'
                . '<img src="' . $url_prefix
                . 'images/viewmag.gif" title="view" alt="view" height="16" '
                . 'width="16" border="0" align="top" /></a>';

        if ($this->access_level > 0) {
            if ($flags & 0x2)
                $html .= '<a href="' . $url_prefix
                    . 'Admin/add_author.php?author_id='
                    . $author->author_id . '">'
                    . '<img src="' . $url_prefix
                    . 'images/pencil.gif" title="edit" alt="edit" '
                    . 'height="16" width="16" border="0" align="top" /></a>';

            if ($flags & 0x4)
                $html .= '<a href="' . $url_prefix
                    . 'Admin/delete_author.php?author_id='
                    . $author->author_id . '">'
                    . '<img src="' . $url_prefix
                    . 'images/kill.gif" title="delete" alt="delete" '
                    . 'height="16" width="16" border="0" align="top" /></a>';
        }

        return $html;
    }

    protected function getCategoryIcons($category, $flags = 0x3) {
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
                . 'images/pencil.gif" title="edit" alt="edit" '
                . 'height="16" width="16" border="0" align="top" /></a>';

        if ($flags & 0x2)
            $html .= '<a href="' . $url_prefix
                . 'Admin/delete_category.php?cat_id='
                . $category->cat_id . '">'
                . '<img src="' . $url_prefix
                . 'images/kill.gif" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="top" /></a>';

        return $html;
    }

    protected function getVenueIcons($venue, $flags = 0x3) {
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
                . 'images/pencil.gif" title="edit" alt="edit" '
                . 'height="16" width="16" border="0" align="top" /></a>';

        if ($flags & 0x2)
            $html .= '<a href="' . $url_prefix
                . 'Admin/delete_venue.php?venue_id='
                . $venue->venue_id . '">'
                . '<img src="' . $url_prefix
                . 'images/kill.gif" title="delete" alt="delete" '
                . 'height="16" width="16" border="0" align="top" /></a>';

        return $html;
    }

    protected function displayPubList($pub_list, $enumerate = true, $max = -1,
                                      $additional = null, $options = null) {
        assert('is_array($pub_list)');

        if (isset($pub_list['type']) && ($pub_list['type'] == 'category')) {
            return $this->displayPubListByCategory($pub_list, $enumerate, $max,
                                                   $options);
        }

        if (count($pub_list) == 0) {
            return 'No Publications';
        }

        $col_desciptions = pdPublication::collaborationsGet($this->db);

        $result = '';
        $count = 0;
        foreach ($pub_list as $pub_id => $pub) {
            ++$count;
            $pub->dbload($this->db, $pub->pub_id);

            $cells = array();
            $table = new HTML_Table(array('class' => 'publist',
                                          'cellpadding' => '0',
                                          'cellspacing' => '0'));
            $table->setAutoGrow(true);

            $citation = $pub->getCitationHtml() . '&nbsp;'
                . $this->getPubIcons($pub);

            if ((is_array($options) && !empty($options['show_internal_info'])
                 && $options['show_internal_info'])
                || (isset($_SESSION['user'])
                    && ($_SESSION['user']->showInternalInfo()))) {
                $citation .= '<br/><span style="font-size:80%">';
                if (isset($pub->ranking))
                    $citation .= 'Ranking: ' . $pub->ranking;

                if (is_array($pub->collaborations)
                    && (count($pub->collaborations) > 0)) {

                    $values = array();
                    foreach ($pub->collaborations as $col_id) {
                        $values[] = $col_desciptions[$col_id];
                    }

                    $citation .= '<br/>Collaboration:' . implode(', ', $values);
                }
                $citation .= '</span>';
            }

            if (isset($additional[$pub_id]))
                $citation .= '<br/><span style="font-size:90%;color:#006633;font-weight:bold;">'
                    . $additional[$pub_id] . '</span>';

            if ($enumerate)
                $cells[] = $count . '.';

            $cells[] = $citation;

            $table->addRow($cells);

            if ($enumerate)
                $table->updateColAttributes(0, array('class' => 'item'), NULL);

            $result .= $table->toHtml();
            unset($table);

            if (($max > 0) && ($count >= $max)) break;
        }

        return $result;
    }

    private function displayPubListByCategory($pub_list, $enumerate = true,
                                              $max = -1, $options = null) {
        assert('is_array($pub_list)');
        $result = '';
        $count = 0;

        $col_desciptions = pdPublication::collaborationsGet($this->db);

        foreach (pdPubList::catDisplayOrder() as $category) {
            $pubs =& $pub_list[$category];

            if (empty($pubs)) continue;

            if ($category == 'Other')
                $result .= "<h3>Other Categories</h3>\n";
            else
                $result .= '<h3>' . $category . "</h3>\n";

            foreach ($pubs as $pub) {
                ++$count;
                $pub->dbLoad($this->db, $pub->pub_id);

                $cells = array();
                $table = new HTML_Table(array('class' => 'publist',
                                              'cellpadding' => '0',
                                              'cellspacing' => '0'));
                $table->setAutoGrow(true);

                $citation = $pub->getCitationHtml() . '&nbsp;'
                    . $this->getPubIcons($pub);

                if ((is_array($options) && !empty($options['show_internal_info'])
    	             && $options['show_internal_info'])
                    || (isset($_SESSION['user'])
            	        && ($_SESSION['user']->showInternalInfo()))) {
                    $citation .= '<br/><span style="font-size:80%">';
                    if (isset($pub->ranking))
                        $citation .= 'Ranking: ' . $pub->ranking;

                    if (is_array($pub->collaborations)
                        && (count($pub->collaborations) > 0)) {

                        $values = array();
                        foreach ($pub->collaborations as $col_id) {
                            $values[] = $col_desciptions[$col_id];
                        }

                        $citation .= '<br/>Collaboration:'
                            . implode(', ', $values);
                    }
                    $citation .= '</span>';
                }

                if ($enumerate)
                    $cells[] = $count . '.';

                $cells[] = $citation;

                $table->addRow($cells);

                if ($enumerate)
                    $table->updateColAttributes(
                        0, array('class' => 'item'), NULL);

                $result .= $table->toHtml();
                unset($table);

                if (($max > 0) && ($count >= $max)) break;
            }
        }

        return $result;
    }

    protected function alphaSelMenu($viewTab, $page) {
        $text = '<div id="selalpha"><ul>';
        for ($c = 65; $c <= 90; ++$c) {
            if ($c == ord($viewTab))
                $text .= '<li><a href="#" class="selected">' . chr($c). '</a></li>';
            else
                $text .= '<li><a href="' . $page . '?tab='. chr($c)
                    . '">' . chr($c) . "</a></li>\n";
        }
        $text .= '</ul></div><br/>';

        return $text;
    }
}

?>
