<?php

/**
 * Contains a base class for all view pages.
 *
 * @package PapersDB
 * @subpackage HTML_Generator
 */

/** Requried classes to build the navigation menu. */
require_once 'defines.php';
require_once 'functions.php';
require_once 'htmlUtils.php';
require_once 'pdDb.php';
require_once 'pdUser.php';
require_once 'pdNavMenu.php';

require_once 'HTML/QuickForm.php';
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
	protected $css;
	protected $js;
	protected $useStdLayout;
	protected $hasHelpTooltips;
	protected $form_controller;
	protected $nav_menu;
	protected $use_mootools = false;
	private   $javascriptFiles = array();
	private   $styleSheets = array('css/style.css', 'css/custom.css');

	const HTML_TOP_CONTENT = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>';

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
	 * Constructor for base class.
	 *
	 * @param string $page_id
	 * @param string $title
	 * @param string $relative_url
	 * @param constant $login_level see pdNavMenuItem.
	 * @param boolean $useStdLayout
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
			$this->db = pdDb::defaultNew();
		}

		$this->check_login();
		$this->nav_menu = new pdNavMenu($this->access_level, $page_id);

		if (isset($page_id)) {
			$nav_item = $this->nav_menu->findPageId($page_id);

			if ($nav_item != null) {
				$this->page_id      = $page_id;
				$this->page_title   = $nav_item->page_title;
				$this->relative_url = $nav_item->url;
				$this->login_level  = $nav_item->access_level;
			}
		}

		if (!isset($page_id) || ($nav_item == null)) {
			$this->page_title   = $title;
			$this->relative_url = relativeUrlGet();
			$this->login_level  = $login_level;
		}

		if ($relative_url != null) {
			$this->relative_url = $relative_url;
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
		|| (strpos($this->relative_url, 'Admin/') !== false)
		|| (strpos($this->relative_url, 'diag/') !== false))
		&& ($this->access_level < 1)) {
			$this->loginError = true;
			return;
		}
	}

	public function __destruct() {
	}

	/**
	 * Assigns $this->access_level according to whether the user is logged
	 * in or not.
	 */
	private function check_login() {
		$this->access_level = pdUser::check_login($this->db);
	}

	public function addJavascriptFiles($arr) {
		assert('is_array($arr)');
		foreach ($arr as $file) {
			assert('file_exists($file)');
		}
		$this->javascriptFiles = array_merge($this->javascriptFiles, $arr);
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

	/**
	 * Loads class variables (those defined in the derived class) with
	 * variables passed in URL query string (GET) and / or HTTP POST.
	 * *
	 * @param $get If set to true then loads variables from URL query string.
	 *
	 * @param $post If set to true then loads variables from HTTP POST.
	 */
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

	protected function addStyleSheets($styleSheets) {
		if (is_array($styleSheets)) {
			$this->styleSheets = array_merge($this->styleSheets, $styleSheets);
		}
		else {
			$this->styleSheets = array_merge($this->styleSheets, (array)$styleSheets);
		}
	}

	private function htmlPageHeader() {
		$result = self::HTML_TOP_CONTENT;

		$result .= '<title>';

		// change the HTML title tag if this is the index page
		if ($this->page_title == 'Home')
		$result .= 'PapersDB';
		else
		$result .= $this->page_title;

		$result .= '</title>'
		. '<meta http-equiv="Content-Type" '
		. 'content="text/html; charset=utf-8" />'
		. '<meta http-equiv="Content-Language" content="en-us" />' . "\n";

		if ($this->redirectUrl != null) {
			$result .= '<meta http-equiv="refresh" content="5;url='
			. $this->redirectUrl . '" />' . "\n";
		}

		$url_prefix = '';
		if (strstr($this->relative_url, '/')) {
			$url_prefix = '../';
		}

		foreach ($this->styleSheets as $styleSheet) {
			$result .= '<link rel="stylesheet" href="' . $url_prefix
			. $styleSheet . '" type="text/css" />' . "\n";
		}

		if (isset($this->css)) {
			$result .= "<style type=\"text/css\">\n" . $this->css
			. "\n</style>\n";
		}

		if ($this->use_mootools)
		$result .= "<script type=\"text/javascript\" src=\"" . $url_prefix
		. "js/mootools-release-1.11.js\"></script>\n";

		if (!empty($this->js)) {
			$result .= "<script type=\"text/javascript\">\n"
			. "//<![CDATA[\n"
			. $this->js
			. "\n//]]>\n"
			. "</script>\n";
		}

		if (count($this->javascriptFiles) > 0) {
			foreach ($this->javascriptFiles as $filename) {
				$result .= "<script type=\"text/javascript\" src=\"$filename\"></script>\n";
			}
		}

		$result .= "\n</head>\n<body>\n";

		if($this->useStdLayout) {
			$result .= $this->pageHeader();
			$result .= "<div id=\"content\">\n";
		}

		return $result;
	}

	private function htmlPageFooter() {
		$result = '';
		if($this->useStdLayout) {
			$result .= $this->pageFooter();
		}

		$result .= '</div>';

		// set up for google analytics
		//
		// note this code is added only on the real site
		if (strpos($_SERVER['PHP_SELF'], '~papersdb') !== false) {
			$result .= self::GOOGLE_ANALYTICS;
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

		if (isset($this->form) && empty($this->renderer)) {
			$this->renderer =& $this->form->defaultRenderer();

			$this->renderer->setFormTemplate(
            '<form{attributes}><table>{content}</table></form>');
			$this->renderer->setHeaderTemplate(
            '<tr><td colspan="2"><b>{header}</b></td></tr>');
			$this->form->accept($this->renderer);
		}

		if ($this->loginError) {
			$result .= $this->loginErrorMessage();
		}
		else if ($this->pageError) {
			$result .= $this->errorMessage();
		}
		else if (($this->renderer != null) && ($this->table != null)) {
			$result .= $this->renderer->toHtml($this->table->toHtml());
		}
		else if (isset($this->renderer)) {
			$result .= $this->renderer->toHtml();
		}
		else if ($this->table != null) {
			$result .= $this->table->toHtml();
		}

		$result .= '</div>' . $this->navMenu()
		.  $this->htmlPageFooter();

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
			else if (count($item->sub_items) == 0) {
				continue;
			}

			$result .= "<li><a href=\"#\">$item->page_title</a>";

			$list_items = array();
			foreach ($item->sub_items as $sub_page_id => $sub_item) {
				// derived class can override nav menu settings, check for
				// each page to be enabled before displaying it
				if (!$sub_item->display
				|| ($sub_item->access_level <= pdNavMenuItem::MENU_NEVER))
				continue;

				$list_item = '<li>';

				if (($sub_page_id == $this->page_id) || !$sub_item->enabled) {
					$list_item .= '<a href="#" class="selected">';
				}
				else {
					$url = $url_prefix . $sub_item->url;

					// if not at the login page add redirection option to the login URL
					if (($sub_page_id == 'login')
					&& (strpos($_SERVER['PHP_SELF'], 'login.php') === false)) {
						$url .= '?redirect=' . $_SERVER['PHP_SELF'];

						if ($_SERVER['QUERY_STRING'] != '') {
							$url .= '?' . $_SERVER['QUERY_STRING'];
						}
					}

					$list_item .= '<a href="' . $url . '">';
				}

				$list_item .= $sub_item->page_title . '</a></li>';
				$list_items[] = $list_item;
			}

			if (count($list_items) > 0) {
				$result .= "<ul>\n" . implode('', $list_items) . "</ul>\n";
			}

			$result .= '</li>';
		}

		$result .= "</ul>\n" . $this->quickSearchFormCreate() . '</div>';
		return $result;
	}

	private function loginErrorMessage() {
		return '<br/>'
		. '<h4>You must be logged in to access this page.</h4>';
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
				$status .= ', DB : ' . $this->db->getDbName();
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
		$aicml_logo = 'images/aicml_logo.png';

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
        <a href="http://www.aicml.ca/">
          <img src="{$aicml_logo}" width="162" alt="AICML Logo" />
        </a>
      </td>
      <td>
          <ul id="copyright">
            <li>Copyright &copy; 2002-2008</li>
          </ul>
      </td>
    </tr>
  </table>
</div>
END;
	}

	protected function helpTooltip($text, $varname, $class = 'help') {
		$this->hasHelpTooltips = true;
		return '<span class="' . $class . '">'
		. '<a href="javascript:void(0);" onmouseover="this.T_WIDTH=300;'
		. 'return escape(' . $varname . ')">' . $text . '</a></span>';
	}

	protected function confirmForm($name, $action = null, $label = 'Delete') {
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
