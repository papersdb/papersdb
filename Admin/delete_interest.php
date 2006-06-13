<?php ;

// $Id: delete_interest.php,v 1.2 2006/06/13 20:04:37 aicmltec Exp $

/**
 * \file
 *
 * \brief Deletes author interests from the database.
 *
 * This page won't be used often, but is necessary in order to remove any
 * author interests that were added by mistake or aren't being used at all. It
 * is just a simple form that selects the interest you would like to delete,
 * and then removes it from the database.
 */

ini_set("include_path", ini_get("include_path") . ":..");

require_once 'includes/pdHtmlPage.php';
require_once 'includes/pdAuthInterest.php';

/**
 * Renders the whole page.
 */
class delete_interest extends pdHtmlPage {
    function delete_interest() {
        global $logged_in;

        parent::pdHtmlPage('delete_interest');

        if (!$logged_in) {
            $this->loginError = true;
            return;
        }

        $db =& dbCreate();
        $interest_list = new pdAuthInterest();
        $interest_list->dbLoad($db);

        $form =& $this->confirmForm('deleter');
        $form->addElement('select', 'interests', null,
                          $interest_list->asArray(),
                          array('multiple' => 'multiple', 'size' => 4));


	/* Connecting, selecting database */
	$link = connect_db();
	if ($confirm == "yes"){
	  $interest_query = "SELECT interest FROM interest where interest_id = $interest";
	  $interest_result = mysql_query($interest_query) or die("Query failed : " . mysql_error());
	  $interest_line = mysql_fetch_array($interest_result, MYSQL_ASSOC);
	  $interest_line = $interest_line[interest];
	  /* This is where the actual deletion happens. */
	  if ($interest != null){
		$query = "DELETE FROM interest WHERE interest_id =$interest";
		query_db($query);
		$query = "DELETE FROM author_interest WHERE interest_id = $interest";
		query_db($query);
		echo "<body>You have successfully removed the following interest from the database: <b>$interest_line</b>";
		echo "<br><a href=\"delete_interest.php\">Delete another interest</a>";
		echo "<br><a href=\"./\">Back to Admin Page</a>";
		echo "<br><br></body></html>";
		disconnect_db($link);
		exit();
	  }
	}
$interest_query = "SELECT * FROM interest";
$interest_result = mysql_query($interest_query) or die("Query failed : " . mysql_error());
$interest_line = mysql_fetch_array($interest_result, MYSQL_ASSOC);

?>


<body><h3>Delete Interest </h3><br>
<form name="deleter" action="./delete_interest.php?confirm=yes" method="POST" enctype="application/x-www-form-urlencoded" target="_self">
	<table width="750" border="0" cellspacing="0" cellpadding="6">
	<tr>
		<td width="25%"><font face="Arial, Helvetica, sans-serif" size="2"><b>Select an interest to delete: </b></font></td>
		<td width="75%">
			<select name="interest" onChange="dataKeep();">
				<option value="">--- Please Select a Interest ---</option>
				<?

					while ($interest_line = mysql_fetch_array($interest_result, MYSQL_ASSOC)) {
						echo "<option value=\"" . $interest_line[interest_id] . "\"" . "";
						echo ">" . $interest_line[interest] . "</option>";
				 	}

				?>
			</select>
		</td>
	  </tr>
	  <tr>

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

	/* Closing connection */
	disconnect_db($link);
?>