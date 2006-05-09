<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>

<? 
	/* direct.php
		This page is used so that the user can login
		into any page on the site. 
		
		ie. A user forgets to login on the menu page,
		and then is browsing a publication and notices 
		a mistake, he/she only has to click the login button
		and then will be taken to the same page, except with 
		admin status, so he/she has the option edit.
	
	
	*/
	if(!strrchr($q, "?"))
		$q .= "?";

?>
<script language="JavaScript" type="text/JavaScript">
	location.href = "http://" + "<? echo $q; ?>" + "&admin=true";
</script>

</body>
</html>

