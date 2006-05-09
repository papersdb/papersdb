<html>
<head>
<title>Delete Additional Material</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<?
	/* delete.php
		This page confirms if the user
		would like to delete the additional
		material. Unlike the paper, it never replaces,
		it either adds a material, or removes a material.


	*/
?>
<script language="JavaScript" type="text/JavaScript">
function closewindow(a)  {
	if(a == 0){
		window.close();
		}
	else if (a == 1){
		window.opener.location.reload(true);
		window.close();
		}
}
</script>

<?
	require('../functions.php');
	$link = connect_db();
	$info = split("/", $info);
	$pub_id = $info[0];
	$i = $info[1];
	if($confirm == "true"){
		$outcome = removematerial($pub_id, $i);
		echo "<script language=\"JavaScript\" type=\"text/JavaScript\"> closewindow(1); </script>";
	}
	$disconnect_db($link);
?>



<body>
<center>

Are you sure you want to delete this additional Material?<BR><BR>
<form>
<input type="button" value="Yes" onclick="window.location='delete.php?info=<? echo $pub_id."/".$i ?>&confirm=true'">
<input type="button" value="No" onclick="closewindow(0);">
</form>

</center>
</body>
</html>
