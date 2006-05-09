<html>
<head>
<title>Papers Database</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
<!--
body {
	background-color: #666666;
}
.style2 {
	color: #FFFFFF;
	font-size: smaller;
}
body,td,th {
	color: #FFFFFF;
}
a:link {
	color: #FFFFFF;
}
a:visited {
	color: #FFFFFF;
}
a:hover {
	color: #CCCCCC;
}
a:active {
	color: #CCCCCC;
}
-->
</style>
</head>
<body><center>

<? require('functions.php');

/* index.php
   Main page for public access, provides a login, and a function that
   selects the most recent publications added.
*/
?>

<table border="0" cellpadding="0" cellspacing="0" width="500">
<form name="pubForm" action="search_publication_db.php" method="POST" enctype="multipart/form-data">
		    <input type="hidden" name="titlecheck" value="true">
			<input type="hidden" name="authorcheck" value="true">
			<input type="hidden" name="halfabstractcheck" value="true">
			<input type="hidden" name="datecheck" value="true">
 <tr>
   <td><img src="template/spacer.gif" width="102" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="120" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="23" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="65" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="128" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="62" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="1" border="0" alt=""></td>
  </tr>

  <tr>
   <td rowspan="2" colspan="5"><img name="public_r1_c1" src="template/public_r1_c1.jpg" width="438" height="112" border="0" alt=""></td>
   <td><a href="./Admin/"><img name="public_r1_c6" src="template/public_r1_c6.jpg" width="62" height="31" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="31" border="0" alt=""></td>
  </tr>
  <tr>
   <td><img name="public_r2_c6" src="template/public_r2_c6.jpg" width="62" height="81" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="81" border="0" alt=""></td>
  </tr>
  <tr>
   <td><img name="public_r3_c1" src="template/public_r3_c1.jpg" width="102" height="34" border="0" alt=""></td>
   <td colspan="5" background="template/public_r3_c2.jpg">

			&nbsp;<input type="text" name="search" size="38" maxlength="250" value="">
			<input type="SUBMIT" name="Quick" value="Go" class="text">

	</td>
  </tr>
  <tr>
   <td colspan="2"><a href="./advanced_search.php"><img name="public_r4_c1" src="template/public_r4_c1.jpg" width="222" height="38" border="0" alt=""></a></td>
   <td rowspan="4" colspan="4"><img name="public_r4_c3" src="template/public_r4_c3.jpg" width="278" height="150" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="38" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="2"><a href="./list_publication.php"><img name="public_r5_c1" src="template/public_r5_c1.jpg" width="222" height="39" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="39" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="2"><a href="./list_author.php"><img name="public_r6_c1" src="template/public_r6_c1.jpg" width="222" height="39" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="39" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="2"><img name="public_r7_c1" src="template/public_r7_c1.jpg" width="222" height="34" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="34" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="6" background="template/public_r8_c1.jpg" valign="top">
   <?
   		$link = connect_db();
		$pub_query = "SELECT * FROM publication ORDER BY updated DESC";
		$pub_result = mysql_query($pub_query) or die("Query failed : " . mysql_error());
                $stringlength=0;
		echo "<table>";
		while (($pub_line = mysql_fetch_array($pub_result, MYSQL_ASSOC))&&($stringlength <= 300)){
			if(strlen($pub_line[title]) < 60) $stringlength += 60;
                        else if(strlen($pub_line[title]) <= 120) $stringlength += 120;
			else if(strlen($pub_line[title]) > 120) $stringlength += 180;
			if($stringlength > 300) break;
			echo "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
			echo "<td><li></td>";
			echo "<td><a href=\"view_publication.php?pub_id=".$pub_line[pub_id]."\">";
			echo "<b>".$pub_line[title]."</b></a></td></tr>";
		}

   		echo "</table>";

   ?>
   </td>
   <td><img src="template/spacer.gif" width="1" height="150" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="3"><a href="http://www.aicml.ca" target="_blank"><img name="public_r9_c1" src="template/public_r9_c1.jpg" width="245" height="54" border="0" alt=""></a></td>
   <td><img name="public_r9_c4" src="template/public_r9_c4.jpg" width="65" height="54" border="0" alt=""></td>
   <td colspan="2"><a href="http://www.ualberta.ca" target="_blank"><img name="public_r9_c5" src="template/public_r9_c5.jpg" width="190" height="54" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="54" border="0" alt=""></td>
  </tr>
  </form>
</table>
</center>
<BR><BR>
<font size=2 color="#CCCCCC">For any questions/comments about the <br> Papers Database please e-mail <a href="mailto:paulsen@cs.ualberta.ca">Jason Paulsen</a></font>
</body>
</html>
