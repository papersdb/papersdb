<?php ;

// $Id: pdHtmlPage.php,v 1.45 2006/09/15 19:17:31 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';
require_once 'includes/pdNavMenu.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/QuickForm/Controller.php';
require_once 'HTML/QuickForm/Action/Display.php';
require_once 'HTML/Table.php';


/**
 * \brief Base class for all HTML pages in PapersDB.
 *
 * Page can be made up of:
 *   - form
 *   - renderer
 *   - table
 *   - form controller
 */
class pdHtmlPage {
    var $page_id;
    var $page_title;
    var $relative_url;
    var $redirectUrl;
    var $redirectTimeout;
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

    /**
     * Constructor.
     */
    function pdHtmlPage($page_id, $title = null, $relative_url = null,
                        $login_level = PD_NAV_MENU_NEVER,
                        $useStdLayout = true) {
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

        if ($urlPrefix != null)
            $this->urlPrefix = $urlPrefix;

        $this->redirectUrl     = $redirectUrl;
        $this->redirectTimeout = 0;
        $this->db              = null;
        $this->table           = null;
        $this->form            = null;
        $this->renderer        = null;
        $this->loginError      = false;
        $this->pageError       = false;
        $this->useStdLayout    = $useStdLayout;
        $this->hasHelpTooltips = false;

        if (MAINTENANCE == 1) {
            if (!isset($_GET['test']) || ($_GET['test'] != 1)) {
                echo 'PapersDB is under maintenance, please check back later';
                exit;
            }
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
        if($this->useStdLayout) {
            $result = '</div>';
            $result .= $this->pageFooter();
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
        if (strpos($_SERVER['PHP_SELF'], '~papersdb')) {
            $result
                .= '<script src="http://www.google-analytics.com/urchin.js" '
                . 'type="text/javascript">' . "\n"
                . '</script>' . "\n"
                . '<script type="text/javascript">' . "\n"
                . '_uacct = "UA-584619-1";' . "\n"
                . 'urchinTracker();' . "\n"
                . '</script>' . "\n";
        }

        $result .= '</body></html>';

        return $result;
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
            if (isset($this->contentPre))
                $result .= $this->contentPre;

            // debug
            //$result .= '<pre>' . print_r($this->table, true) . '</pre>';

            if ($this->renderer != null) {
                if ($this->table != null)
                    $result .= $this->renderer->toHtml($this->table->toHtml());
                else
                    $result .= $this->renderer->toHtml();
            }
            else if ($this->table != null) {
                $result .= $this->table->toHtml();
            }

            if (isset($this->contentPost))
                $result .= $this->contentPost;
        }
        $result .= $this->htmlPageFooter();

        return $result;
    }

    function navMenu() {
        global $access_level;

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
            // the third and takes care of displaying the guest links
            if ((($access_level > 0)
                 && ($item->access_level > PD_NAV_MENU_ALWAYS)
                 && ($item->access_level < PD_NAV_MENU_LEVEL_ADMIN))
                || (($access_level >= 2)
                    && ($item->access_level == PD_NAV_MENU_LEVEL_ADMIN))
                || (($access_level == 0)
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
                    $options[$item->page_title] .= '?redirect=' . $_SERVER['PHP_SELF'];

                    if ($_SERVER['QUERY_STRING'] != '')
                        $options[$item->page_title] .= '?' . $_SERVER['QUERY_STRING'];
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

        $form = $this->quickSearchFormCreate();
        $renderer = new HTML_QuickForm_Renderer_QuickHtml();
        $form->accept($renderer);

        $result .= "</ul>\n"
            . $renderer->toHtml($renderer->elementToHtml('search') . ' '
                                . $renderer->elementToHtml('Quick'))
            . "</div>";
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
        global $access_level;

        if ($access_level > 0) {
            $status = 'Logged in as: ' . $_SESSION['user']->login;

            if ($access_level >= 2) {
                $status .= ', DB : ' . DB_NAME;
            }
        }
        else {
            $status = 'Not Logged In';
        }

        return <<<END
            <div id="statusbar">
            <table border="0" cellspacing="0" cellpadding="0" align="center"
            width="100%">
            <tr>
            <td nowrap>{$status}</td>
            </tr>
            </table>
            </div>
            <div id="titlebar">
            <a href="http://www.uofaweb.ualberta.ca/science/">
            <img src="http://www.cs.ualberta.ca/library/images/science.gif"
            alt="Faculty of Science Home Page" width="525" height="20"
            border="0"/></a>
            <a href="http://www.ualberta.ca/">
            <img src="http://www.cs.ualberta.ca/library/images/uofa_top.gif"
            alt="University of Alberta Home Page" width="225" height="20"
            border="0"/></a>
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
            <table width="800px">
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
            <li>Copyright &copy; 2002-2006</li>
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

    function confirmForm($name, $action = null) {
        $form = new HTML_QuickForm($name, 'post', $action, '_self',
                                   'multipart/form-data');

        $form->addElement('submit', 'submit', 'Delete');
        $form->addElement('button', 'cancel', 'Cancel',
                          array('onclick' => 'history.back()'));
        return $form;
    }

    function quickSearchFormCreate() {
        if (strstr($this->relative_url, '/'))
            $script = '../search_publication_db.php';
        else
            $script = 'search_publication_db.php';

        $form = new HTML_QuickForm('quickPubForm', 'get', $script);
        $form->addElement('text', 'search', null,
                          array('size' => 12, 'maxlength' => 80));
        $form->addElement('submit', 'Quick', 'Search');

        return $form;
    }

    function navMenuItemDisplay($page_id, $enable) {
        assert('isset($this->nav_menu->nav_items[$page_id])');
        $this->nav_menu->nav_items[$page_id]->display = $enable;
    }

    function navMenuItemEnable($page_id, $enable) {
        assert('isset($this->nav_menu->nav_items[$page_id])');
        $this->nav_menu->nav_items[$page_id]->enabled = $enable;
    }
}

?>
