<?php
    /* Headeradmin.php
      This is the admin header that you see on all the pages that is open 
	  to the public but has a user logged in.
	*/ 
	echo "<body link=\"#000099\" vlink=\"#000099\">";
	echo "<!--PICOSEARCH_SKIPLINKSTART-->";
	?>
<table border="0" cellpadding="0" cellspacing="0" width="500">
  <tr>
   <td><img src="buttons/spacer.gif" width="82" height="1" border="0" alt=""></td>
   <td><img src="buttons/spacer.gif" width="132" height="1" border="0" alt=""></td>
   <td><img src="buttons/spacer.gif" width="95" height="1" border="0" alt=""></td>
   <td><img src="buttons/spacer.gif" width="113" height="1" border="0" alt=""></td>
   <td><img src="buttons/spacer.gif" width="78" height="1" border="0" alt=""></td>
   <td><img src="buttons/spacer.gif" width="1" height="1" border="0" alt=""></td>
  </tr>

  <tr>
   <td colspan="5"><a href="./Admin/"><img name="header_r1_c1" src="buttons/header_r1_c1.gif" width="500" height="94" border="0" alt=""></a></td>
   <td><img src="buttons/spacer.gif" width="1" height="94" border="0" alt=""></td>
  </tr>
  <tr>
   <td><a href="advanced_search.php?admin=true"><img name="header_r2_c1" src="buttons/header_r2_c1.gif" width="82" height="26" border="0" alt=""></a></td>
   <td><a href="list_publication.php?type=view&admin=true"><img name="header_r2_c2" src="buttons/header_r2_c2.gif" width="132" height="26" border="0" alt=""></a></td>
   <td><a href="list_author.php?type=view&admin=true"><img name="header_r2_c3" src="buttons/header_r2_c3.gif" width="95" height="26" border="0" alt=""></a></td>
   <td><a href="Admin/quickedit.php"><img name="header_r2_c4" src="buttons/header_r2_c4.gif" width="113" height="26" border="0" alt=""></a></td>
   <td><a href="Admin/login.php"><img name="header_r2_c5" src="buttons/header_r2_c5.gif" width="78" height="26" border="0" alt=""></td>
   <td><img src="buttons/spacer.gif" width="1" height="26" border="0" alt=""></td>
  </tr>
</table>
		<?
		
	echo "<!--PICOSEARCH_SKIPLINKEND--> ";
	echo "</body>";
?>
