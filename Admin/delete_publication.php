<?php ;

// $Id: delete_publication.php,v 1.2 2006/06/13 23:56:04 aicmltec Exp $

/**
 * \file
 *
 * \brief Deletes a publication from the database.
 *
 * This page confirms that the user would like to delete the following
 * publication and then removes it from the database once confirmation has been
 * given.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';

/**
 * Renders the whole page.
 */
class delete_publication extends pdHtmlPage {
    function delete_publication() {
        global $logged_in;

        parent::pdHtmlPage('delete_publication');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        if (isset($_GET['pub_id']) && ($_GET['pub_id'] != ''))
            $pub_id = intval($_GET['pub_id']);
        else {
            $this->contentPre .= 'No pub id defined';
            $this->pageError = true;
            return;
        }

        $form =& $this->confirmForm('deleter');
        $form->addElement('hidden', 'pub_id', $pub_id);

        if ($form->validate()) {
            $values = $form->exportValues();

            $db =& dbCreate();
            $pub = new pdPublication();
            $pub->dbLoad($db, $values['pub_id']);
            if (!$result) {
                $db->close();
                $this->pageError = true;
                return;
            }

            $title = $pub->title;
            $pub->dbDelete($db);

            $this->contentPre .= 'You have successfully removed the following '
                . 'from the database: <p/><b>' . $title . '</b>';
        }
        else {
            $db =& dbCreate();
            $pub = new pdPublication();
            $result = $pub->dbLoad($db, $pub_id);
            if (!$result) {
                $db->close();
                $this->pageError = true;
                return;
            }

            $renderer =& new HTML_QuickForm_Renderer_QuickHtml();
            $form->accept($renderer);

            $table = new HTML_Table(array('width' => '100%',
                                          'border' => '0',
                                          'cellpadding' => '6',
                                          'cellspacing' => '0'));

            $this->contentPre .= '<h3>Delete Publication</h3><br/>'
                . 'Delete the following paper?';

            $table->addRow(array('Title:', $pub->title));
            $table->addRow(array('Category:', $pub->category));
            $table->addRow(array('Paper:', $pub->paper));
            $table->addRow(array('Authors:', $cell));
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2">
			<?
				while ($author_line = mysql_fetch_array($author_result, MYSQL_ASSOC)) {
					echo "<a href=\"./view_author.php?popup=true&author_id=$author_line[author_id]\" target=\"_blank\" onClick=\"window.open('./view_author.php?popup=true&author_id=$author_line[author_id]', 'Help', 'width=500,height=250,scrollbars=yes,resizable=yes'); return false\">";
					echo $author_line[name];
					echo "</a><br>";
				}
			?>
			</font>
		</td>
	  </tr>
	  <tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Date Published: </b></font></td>
		<td width="75%"><font face="Arial, Helvetica, sans-serif" size="2"><? echo $pub_array[published] ?></font></td>
	  </tr>
	  <tr>
	  <form name="deleter" action="./delete_publication.php?pub_id=<?php echo $pub_id; ?>&confirm=yes" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
		<td width="100%" colspan="2">
		  <input type="SUBMIT" name="Confirm" value="Delete" class="text">
		  <input type="button" value="Cancel" onclick="history.back()">
		 &nbsp; &nbsp; &nbsp;</td>
	  </form>
	  </tr>
	</table>
	<? back_button(); ?>
</body>
</html>

<?
	/* Free resultset */
	mysql_free_result($pub_result);
	mysql_free_result($cat_result);
	mysql_free_result($author_result);

	/* Closing connection */
	disconnect_db($link);
?>