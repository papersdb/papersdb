<html>
<head>
<title>Papers Database Administrator Page</title>
<meta http-equiv="Content-Type" content="text/html;">
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
<body bgcolor="#666666" link="#000099" vlink="#000099">
<center>
<?
	require('../functions.php');
	/* Connecting, selecting database */
	$link = connect_db();
	$user_query = "SELECT * FROM user WHERE login LIKE \"".$_SERVER['PHP_AUTH_USER']."\"";
	$user_result = query_db($user_query);
	$user_array = mysql_fetch_array($user_result, MYSQL_ASSOC);

?>
<table border="0" cellpadding="0" cellspacing="0" width="500">
 <tr>
   <td><img src="template/spacer.gif" width="119" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="46" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="40" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="36" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="12" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="16" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="48" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="30" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="29" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="124" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="1" border="0" alt=""></td>
  </tr>

  <tr>
   <td colspan="10"><img name="paperbox_r1_c1" src="template/paperbox_r1_c1.jpg" width="500" height="112" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="112" border="0" alt=""></td>
  </tr>
  <tr>
   <td><a href="../advanced_search.php?admin=true"><img name="paperbox_r2_c1" src="template/paperbox_r2_c1.jpg" width="119" height="36" border="0" alt=""></a></td>
   <td colspan="5"><img name="paperbox_r2_c2" src="template/paperbox_r2_c2.jpg" width="150" height="36" border="0" alt=""></td>
   <td colspan="4"><a href="user.php"><img name="paperbox_r2_c7" src="template/paperbox_r2_c7.jpg" width="231" height="36" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="36" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="2"><a href="../list_publication.php?admin=true"><img name="paperbox_r3_c1" src="template/paperbox_r3_c1.jpg" width="165" height="34" border="0" alt=""></a></td>
   <td rowspan="2" colspan="3"><img name="paperbox_r3_c3" src="template/paperbox_r3_c3.jpg" width="88" height="68" border="0" alt=""></td>
   <td colspan="5"><a href="add_venue.php?status=view"><img name="paperbox_r3_c6" src="template/paperbox_r3_c6.jpg" width="247" height="34" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="34" border="0" alt=""></td>
  </tr>
  <tr>
   <td><a href="../list_author.php?admin=true"><img name="paperbox_r4_c1" src="template/paperbox_r4_c1.jpg" width="119" height="34" border="0" alt=""></a></td>
   <td><img name="paperbox_r4_c2" src="template/paperbox_r4_c2.jpg" width="46" height="34" border="0" alt=""></td>
   <td rowspan="2" colspan="3"><img name="paperbox_r4_c6" src="template/paperbox_r4_c6.jpg" width="94" height="71" border="0" alt=""></td>
   <td colspan="2"><img name="paperbox_r4_c9" src="template/paperbox_r4_c9.jpg" width="153" height="34" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="34" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="3"><img name="paperbox_r5_c1" src="template/paperbox_r5_c1.jpg" width="205" height="37" border="0" alt=""></td>
   <td colspan="2"><img name="paperbox_r5_c4" src="template/paperbox_r5_c4.jpg" width="48" height="37" border="0" alt=""></td>
   <td><img name="paperbox_r5_c9" src="template/paperbox_r5_c9.jpg" width="29" height="37" border="0" alt=""></td>
   <td><img name="paperbox_r5_c10" src="template/paperbox_r5_c10.jpg" width="124" height="37" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="37" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="10" background="template/paperbox_r6_c1.jpg" valign="top">
   <BR>
      <?
   	if($user_array['login'] == "")
		echo "<center><FONT COLOR=\"#DD0000\"><b><u>ATTENTION</u><BR>YOUR LOGIN INFORMATION MUST BE UPDATED!<BR>".
		"<a href=\"user.php\">CLICK HERE TO DO SO NOW</a></b></FONT></center>";
	else
		echo "&nbsp;<font color=\"#FFFFFF\">Welcome <B>".$user_array['name']."</B>.</font><BR>";
		$pub_query = "SELECT * FROM publication WHERE submit LIKE \"".$user_array['name']."\" ORDER BY updated DESC";
		$pub_result = query_db($pub_query);
		for($r = 0; $r < 3; $r++){
		$pub_array = mysql_fetch_array($pub_result, MYSQL_ASSOC);
		if(($pub_array['title'] != "")&&($r == 0))
			echo "&nbsp;You have most recently submitted the following papers:";
		if($pub_array['title'] != "")
			echo "<BR>&nbsp;<li><b><a href=\"../view_publication.php?admin=true&pub_id=".$pub_array['pub_id']."\">".$pub_array['title']."</a></b>";
		}

		disconnect_db($link);
   ?>
   </td>
   <td><img src="template/spacer.gif" width="1" height="192" border="0" alt=""></td>
  </tr>
  <tr>
   <td colspan="4"><a href="http://www.aicml.ca" target="_blank"><img name="paperbox_r7_c1" src="template/paperbox_r7_c1.jpg" width="241" height="55" border="0" alt=""></a></td>
   <td colspan="3"><img name="paperbox_r7_c5" src="template/paperbox_r7_c5.jpg" width="76" height="55" border="0" alt=""></td>
   <td colspan="3"><a href="http://www.ualberta.ca" target="_blank"><img name="paperbox_r7_c8" src="template/paperbox_r7_c8.jpg" width="183" height="55" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="55" border="0" alt=""></td>
  </tr>
</table>
</center>
</body>
</html>
