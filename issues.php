<?
	include 'header.php';
?>

<html>
<head>
<title>Known Issues</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
	<table width="750" border="0" cellspacing="0" cellpadding="6">
		<tr align="left">
			<td>
				<font face="Arial, Helvetica, sans-serif" size="2">
					<ul>
						<b>Known Issues...</b>
						<li>Functionality is not cross-browser (please <font color="#990000"><b>***DO NOT USE Netscape 4/6***</b></font>).</li>
						<li>When editing a paper, if the user changes the category that the paper belongs to, the sub-fields will be messed up due to the dependency between additional info fields and the category ID. </li>
						<li>Help menu does not work for user defined related fields in Add Publication.</li>
						<li>lib_functions.php:195.  What the hell?</li>
					</ul>
				</font>
			</td>
		</tr>
		<tr>
			<td>
				<font face="Arial, Helvetica, sans-serif" size="2">
					<ul>
						<b>To Do List...</b>
						<li>Editing publications.</li>
						<li>Prettify.</li>
						<li>Database backup functions.</li>
					</ul>
				</font>
			</td>
		</tr>
		<tr>
			<td>
				<font face="Arial, Helvetica, sans-serif" size="2">
					<ul>
						<b>Later Considerations...</b>
						<li>Upload papers from a URL.</li>
						<li>Add search capabilities (dependant on decisions made outside of the scope of the publications DB - that is, on decisions made by the AICML web development team.</li>
						<li>Security is nonexistant as it stands.</li>
						<li>The lack of CSS use here is galling.  Worth the effort to fix it?</li>
						<li>Add capabilities for a non-web-browser interface?</li>
						<li>Incorporate functionality for bath adding multiple publications?</li>
					</ul>
				</font>
			</td>
		</tr>
	</table>
</body>
</html>

<!--
Notes from Genier:
 > > 2. I was unable to add a new author.
 > >   (I got the pop-up, but it never "took")
 > What browser were you using?
Mozilla, on a RedHat linux system.
(I tried to add "Jack Newton".
Here, I also tried to add the field "Artifical Intelligence" to that
pull-down.  Perhaps that was the problem?) 

I just now tried it using IE on WindowsXP, and it did work.
(Added Yuhong Guo.)

 > > 4. Can I have "cross links" -- paper 1 refering to paper 2?
 > I'm not sure what you mean by cross links.  If you just mean having paper 
 > 1 refer to paper 2 because they're related, future functionality will 
 > include a search function.  People will be able to find related papers by 
 > searching on keywords.

Some results begin as a workshop paper, then appear as a conference 
paper, before maturing into a journal paper.


-->
