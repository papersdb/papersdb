<html>
To do:<P>
<li> Security - Only users who submitted the publication can edit it again. The database keeps track of who added what publication and a simple php function tells us the current logged in user.
<li> Security - Something I perhaps overlooked? Is using htpasswd and htaccess secure enough? maybe store logins in the actual sql database?
<li> Bug (jay) - Clicking "Add new publication" while in IE after you enter everything in, does nothing. Problem with the javascript is my guess.<BR>
<b> turns out ie won't allow files that dont exist in the input, so i'm in the process of adding the option "no paper". Should be done by end of the week. </b>
<li> Bug (jay) - Sometimes does not save/upload the file selected when adding a publication. I have never been able to see this bug in action, but Russ seems to.
<li> Bug (jay) - Does not display unique venue, only displays the ID of the venue as -2. Again, I can't seem to find it, Russ found this problem once or twice, but hasnt happened of late.
<li> More Bugs (jeff) - I am sure there are more out there, I just haven't done enough thorough testing to find them.
<li> Search - Pass the search query through the address and not as a hidden form, so that way users can link a search (remember to use quote_smart() to guard against injection attacks
<li> Compatability Issues - Have back up functions for users who do not have Java and make sure everything looks clean with all browsers.

<P>
Lower priority:<P>
<li> Search - Add a "sort by" function
<li> Config file for SQL dynamic info
<li> Code clean up - this includes deleting commented code, adding commented code and using functions more globally.
<li> Quick Editor - Make this feature more realistic in terms of being able to be used by numerous users.
<li> Restructure add_publication.php, take out add author and category and move them to there own page such as venue.
<P>

<b>JAY - March 28th</b>
</html>
