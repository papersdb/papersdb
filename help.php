<html>
<head>
<title>Publication DB Help</title></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<?
  /* help.php
     This page is for giving help information on all the fields necessary when 
	 entering in a new publication or author or venue.
	 It is not in the database because this help information does not ever need to be
	 dynamically changed by someone who isn't the webmaster.
   */ 
require('functions.php');
echo "<b>Help for: ";
if (isset($helpcat) && $helpcat != "")
	echo ucfirst($helpcat);
else
	echo "PapersDB";
echo "</b><br><br>";

if ($helpcat=='category'){
	echo "Category describes the type of document that you are submitting to the site. For example
	this could be a journal entry, a book chapter, etc.<br><br>";
	echo "Please use the drop down menu to select an appropriate category to classify your paper.
	if you cannot find an appropriate category you can use the Add Category link to update
	the category listings.<br><br>Clicking Add 
	Category will bring up another window that will allow you to specifiy a new category by
	entering the Category Name and then selecting related fields.";
}
else if ($helpcat=='title'){
	echo "Title should contain the title given to your document.<br><br>";
	echo "Please enter the title of your document in the field provided.";
}	
else if ($helpcat=='nummaterials'){
	echo "This field can be used to add additional documents that your document makes reference to. <br><br>";
	echo "To use this feature select the number of additional references you would like to add along with
	your paper by using the drop down menu. Once you have selected additional materials you will see the 
	appropriate number of additional fields appear. You will then need to use the browse button to select
	the documents you wish to add.";
}
else if ($helpcat=='paper'){
	echo "This field is used for actually uploading your paper to the database.<br><br>";
	echo "To do this select the paper you wish to add to the database by clicking the browse button
	and then selecting the paper.";		
}
else if ($helpcat=='authors'){
	echo "This field is to store the author(s) of your document in the database.<br><br>";
	echo "To use this field select the author(s) of your document from the listbox.
	You can select multiple authors by holding down the control key and clicking. If you do not
	see the name of the author(s) of the document listed in the listbox then you must add them
	with the Add Author button.";
}
else if ($helpcat=='abstract'){
	echo "Abstract is an area for you to provide an abstract of the document you are submitting.<br><br>";
	echo "To do this enter a plain text abstract for your paper in the field provided.";
}
else if ($helpcat=='keywords'){
	echo "Keywords is a field where you can enter keywords that will be used to possibly locate your paper
	by others searching the database. You may want to enter multiple terms that are associated with your 
	document. Examples may include words like: medical imaging; robotics; data mining.<br><br>";
	echo "Please enter keywords used to describe your paper, each keyword should be seperated by a ;";
}
else if ($helpcat=='date'){
	echo "The date field is used to describe the date of your paper.<br><br>";	
	echo "Please use the drop down menu to select the date of publication for your paper.";	
}
else if ($helpcat=='Author Title') {
	echo "The title of an author.  Will take the form of one of: {Prof, PostDoc, PhD student, MSc student, Colleague, etc...}.";	
}
else if($helpcat=='AddAuthor'){
	echo "This window is used to add an author to the database. In order to add an author you need to input
	the author's first name, last name, email address and organization. You must also select interet(s)
	that the author has. To do this you can select interest(s) allready in the database by selecting them
	from the listbox. You can select multiple interests by control-clicking on them. If you do not see
	the appropriate interest(s) you can add interest(s) using the Add Interest link.<br><br>
	Clicking the Add Interest link will bring up additional fields everytime you click it. You can
	then type in the name of the interest into the new field provided.";
}
else if($helpcat=='Add Category'){
	echo "This window is used to add a new category of papers to the database. The category should be
	used to describe the type of paper being submitted. Examples of paper types include: journal entries, 
	book chapters, etc. <br><br>
	When you add a category you can also select related field(s) by clicking on the selection boxes. If you
	do not see the appropriate related field(s) you can add field(s) by clicking on the Add Field button to bring
	up additional fields where you can type in the name of the related field you wish to add.";
}
else if($helpcat=='Edit Author'){
	echo "This section is used for editing information about an author. <br><br>To edit a field simply find the
	appropriate field you wish to edit and make the necessary changes. If you wish to edit the interests
	field you can control-click to add more than one interest. If you do not see the appropriate interest
	you can add new ones by clicking on the Add Interest button and then inputing the new interest. 
	Once you have successfully made your changes press the Edit Author button to make your changes permanent.";
}
else if($helpcat=='Additional Fields'){
	echo "This section is used to define additional fields associated with the category. These items were defined
	by the user. For example if the category is a book you might find an additional field called edition which would
	be the edition number of the book. These fields are different for each category.<br><br>
	To use this field you should input some text to describe the additional field. Alternatively you can leave this
	field blank if it is not applicable.";	
}

else if($helpcat=='comments'){
	echo "This section is used to for the author to add any additional information about the paper. Whether it be links
	to another paper thats related or conferences where the paper was presented or even links to a affiliated website.";	
}
else {
	echo "Publications Database is a web-based repository for papers. It is used to hold information about 
	papers including: their name, category, author(s), date, and more. The system allows the user to submit
	papers edit information about papers, store papers, view papers, and much more.";
}

?>

<br><br><br>
<a href="javascript:close();">Close Window</a>
</body>
</html>
