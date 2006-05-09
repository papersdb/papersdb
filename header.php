<head><style type="text/css">
<!--
a:link {
	color: #000099;
}
a:visited {
	color: #000099;
}
a:hover {
	color: #0066FF;
}
-->
</style></head>
<? /* Header.php
      This is the public header that you see on all the pages except the main page.
	*/ ?>
<table border="0" cellpadding="0" cellspacing="0" width="500">
<form name="header" action="search_publication_db.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="titlecheck" value="true">
			<input type="hidden" name="authorcheck" value="true">
			<input type="hidden" name="halfabstractcheck" value="true">
			<input type="hidden" name="datecheck" value="true"> 
  <tr>
   <td><img src="template/spacer.gif" width="70" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="167" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="116" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="82" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="65" height="1" border="0" alt=""></td>
   <td><img src="template/spacer.gif" width="1" height="1" border="0" alt=""></td>
  </tr>

  <tr>
   <td colspan="5"><a href= "./"><img name="header_r1_c1" src="template/header_r1_c1.gif" width="500" height="94" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="94" border="0" alt=""></td>
  </tr>
  <tr>
   <td><a href="./advanced_search.php"><img name="header_r2_c1" src="template/header_r2_c1.gif" width="70" height="26" border="0" alt=""></a></td>
   <td background="template/header_r2_c2.gif"><input type="text" name="search" size="18" maxlength="250" value=""></td> 
   <td><a href="./list_publication.php?type=view"><img name="header_r2_c3" src="template/header_r2_c3.gif" width="116" height="26" border="0" alt=""></a></td>
   <td><a href="./list_author.php?type=view"><img name="header_r2_c4" src="template/header_r2_c4.gif" width="82" height="26" border="0" alt=""></a></td>
   <td><a href="./Admin/direct.php?q=<? echo $_SERVER["HTTP_HOST"]; echo $PHP_SELF; if($_SERVER['QUERY_STRING'] != "") echo "?".$_SERVER['QUERY_STRING']; ?>"><img name="header_r2_c5" src="template/header_r2_c5.gif" width="65" height="26" border="0" alt=""></a></td>
   <td><img src="template/spacer.gif" width="1" height="26" border="0" alt=""></td>
  </tr>
  </form>
</table>
