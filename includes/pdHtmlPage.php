<?php ;

// $Id: pdHtmlPage.php,v 1.4 2006/06/09 22:08:58 aicmltec Exp $

/**
 * \file
 *
 * \brief
 */

require_once 'includes/functions.php';
require_once 'includes/check_login.php';

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/advmultiselect.php';
require_once 'HTML/Table.php';

define('PD_HTML_PAGE_LOGIN_LEVEL_ALWAYS',   0);
define('PD_HTML_PAGE_LOGIN_LEVEL_NONE',     1);
define('PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED', 2);


/**
 * \brief Base class for all HTML pages in PapersDB.
 */
class pdHtmlPage {
    var $page_id;
    var $title;
    var $relativeUrl;
    var $redirectUrl;
    var $loginLevel;
    var $db;
    var $loginError;
    var $pageError;
    var $table;
    var $form;
    var $renderer;
    var $js;
    var $contentPre;
    var $contentPost;

    /**
     * Constructor.
     */
    function pdHtmlPage($page_id, $redirectUrl = '') {
        if (($page_id != null) && ($page_id != '')
            && (isset($this->page_info[$page_id]))) {
            $this->page_id     = $page_id;
            $this->title       = $this->page_info[$page_id][0];
            $this->relativeUrl = $this->page_info[$page_id][1];
            $this->loginLevel  = $this->page_info[$page_id][2];
        }
        $this->redirectUrl = $redirectUrl;
        $this->db          = null;
        $this->table       = null;
        $this->form        = null;
        $this->renderer    = null;
        $this->loginError  = false;
        $this->pageError   = false;
    }

    function htmlPageHeader() {
        $result = $this->htmlHeader($this->page_id, $this->title);
        $result .= $this->js;
        $result .= '<body>';
        $result .= $this->pageHeader();
        $result .= $this->navMenu($this->page_id);
        $result .= '<div id="content">';

        return $result;
    }

    function htmlPageFooter() {
        $result = '</div>';
        $result .= $this->pageFooter();


        if (strstr($this->relativeUrl, '/'))
            $jsFile = '../wz_tooltip.js';
        else
            $jsFile = 'wz_tooltip.js';

        $result .= '<script language="JavaScript" type="text/javascript" src="'
            . $jsFile . '"></script>';

        $result .= '</body></html>';

        return $result;
    }

    /**
     * Renders the page.
     */
    function toHtml() {
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
                $result .= $this->renderer->toHtml(($this->table->toHtml()));
            }
            else {
                assert ('($this->form == null)');
                if ($this->table != null)
                    $result .= $this->table->toHtml();
            }

            if (isset($this->contentPost))
                $result .= $this->contentPost;
        }
        $result .= '</div>' . $this->htmlPageFooter();

        return $result;
    }

    // private date to this class
    //
    // used to build the navigation menu and other things.
    //
    // kinda kludgey but works
    //
    var $page_info = array(
        'add_publication'  => array('Add Publication',
                                    'Admin/add_publication.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'add_author'       => array('Add Author',
                                    'Admin/add_author.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'add_venue'        => array('Add Venue', 'Admin/add_venue.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'delete_author'    => array('Delete Author',
                                    'Admin/delete_author.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'delete_venue'     => array('Delete Venue', 'Admin/delete_venue.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'edit_user'        => array('Edit User', 'Admin/edit_user.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'advanced_search'  => array('Advanced Search', 'advanced_search.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_NONE),
        'all_publications' => array('All Publications', 'list_publication.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_NONE),
        'all_authors'      => array('All Authors', 'list_author.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_NONE),
        'all_venues'       => array('All Venues', 'list_venues.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_NONE),
        'logout'           => array('Logout', 'logout.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED),
        'login'            => array('Login or Register', 'login.php',
                                    PD_HTML_PAGE_LOGIN_LEVEL_ALWAYS)
        );

    function htmlHeader($page, $title, $redirect = '') {
        $result =
            '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" '
            . 'lang="en">'
            . '<head>'
            . '<title>' . $this->title . '</title>'
            . '<meta http-equiv="Content-Type" '
            . 'content="text/html; charset=iso-8859-1" />';

        if ($this->redirectUrl != '') {
            $result .= '<meta http-equiv="refresh" content="5;url='
                . $this->redirectUrl . '" />';
        }

        if (strstr($this->relativeUrl, '/'))
            $cssFile = '../style.css';
        else
            $cssFile = 'style.css';

        $result .= '<link rel="stylesheet" href="' . $cssFile . '" /></head>';
        return $result;
    }

    function navMenu() {
        global $logged_in;

        $url_prefix = '';
        if (isset($this->page_id) && strstr($this->relativeUrl, '/'))
            $url_prefix = '../';

        foreach ($this->page_info as $name => $info) {
            if (($logged_in && ($info[2] > 0))
                || (!$logged_in
                    && ($info[2] < PD_HTML_PAGE_LOGIN_LEVEL_REQUIRED))) {
                if ($name == $this->page_id) {
                    $options[$info[0]] = '';
                }
                else if (($this->page_id != '')
                         && (strstr($this->relativeUrl, '/')))
                    $options[$info[0]] = $url_prefix . $info[1];
                else
                    $options[$info[0]] = $info[1];
            }
        }

        $result = '<div id="nav">'
            . '<h2>navigation</h2>'
            . '<ul>';

        if (is_array($options))
            foreach ($options as $key => $value) {
                if ($value == '')
                    $result .= '<li>' . $key . '</li>';
                else
                    $result
                        .= '<li><a href="' . $value . '">' . $key . '</a></li>';
            }

        $form = quickSearchFormCreate();
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
        return '<br/>'
            . '<h4>There was a problem handling your request.'
            . '<br/>Please go back and try again.</h4>'
            . '<br/>';
    }

    function pageHeader() {
        return <<<END
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
            <h1>Papers Database</h1>
            </div>

END;
    }

    function pageFooter() {
        return <<<END
            <div id="footer">
            For any questions/comments about the Papers Database please e-mail
            <a href="mailto:papersdb@cs.ualberta.ca">PapersDB Administrator</a>
            <div class="ualogo">
            <a href="http://www.ualberta.ca">
            <img src="http://www.cs.ualberta.ca/library/images/uofa_logo.gif"
            width="162" height="36" alt="University of Alberta Logo" />
            </a>
            </div>
            <div id="copyright">
            <ul>
            <li>Copyright &copy; 2002-2006</li>
                                     </ul>
                                     </div>
                                     </div>

END;
    }
}

?>
