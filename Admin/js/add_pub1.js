<script language="JavaScript" type="text/JavaScript">

 // $Id: add_pub1.js,v 1.3 2007/04/27 22:15:52 aicmltec Exp $

var venueHelp=
     "Where the paper was published -- specific journal, conference, "
     + "workshop, etc. If many of the database papers are in the same "
     + "venue, you can create a single <b>label</b> for that "
     + "venue, to specify name of the venue, location, date, editors "
     + "and other common information. You will then be able to use "
     + "and re-use that information.";

var categoryHelp=
    "Category describes the type of document that you are submitting "
    + "to the site. For examplethis could be a journal entry, a book "
    + "chapter, etc.<br/><br/>"
    + "Please use the drop down menu to select an appropriate "
    + "category to classify your paper. If you cannot find an "
    + "appropriate category you can select 'Add New Category' from "
    + "the drop down menu and you will be asked for the new category "
    + "information on a subsequent page.<br/><br/>";

var titleHelp=
    "Title should contain the title given to your document.";

var abstractHelp=
    "Abstract is an area for you to provide an abstract of the "
    + "document you are submitting.<br/><br/>"
    + "To do this enter a plain text abstract for your paper in the "
    + "field provided. HTML tags can be used.";


var keywordsHelp=
    "Keywords is a field where you can enter keywords that will be "
    + "used to possibly locate your paper by others searching the "
    + "database. You may want to enter multiple terms that are "
    + "associated with your document. Examples may include words "
    + "like: medical imaging; robotics; data mining.<br/><br/>"
    + "Please enter keywords used to describe your paper, each "
    + "keyword should be seperated by a semicolon.";

var userInfoHelp=
    "A place for the user to enter his/her own information";

function catVenueUnescapeEntities(str) {
    var div = document.createElement('div');
    div.innerHTML = str;
    return div.childNodes[0] ? div.childNodes[0].nodeValue : '';
}

function catVenueSwapOptions(form) {
    var cat_id = form.elements['cat_id'].value;

    if (!(cat_id in catVenueOptions)) {
        cat_id = 0;
    }

    var ctl = form.elements['venue_id'];
    ctl.options.length = 0;

    var j = 0;
    for (var i in catVenueOptions[cat_id]) {
        var optionText;
        if (catVenueOptions[cat_id][i][0].indexOf('&') != -1) {
            optionText = catVenueOptions[cat_id][i][0];
        }
        else {
            optionText = catVenueUnescapeEntities(catVenueOptions[cat_id][i][0]);
        }
        ctl.options[j++] = new Option(optionText, i, false, false);
    }
}

function catVenueOnReload() {
    var form = document.forms['add_pub1'];
    catVenueSwapOptions(form);

    var ctl = form.elements['venue_id'];

    for (var i in ctl.options) {
        if (ctl.options[i].value == catVenueDefault) {
            ctl.options[i].selected = true;
        }
    }

    if (catVenuePrevOnload) {
        catVenuePrevOnload();
    }
}

var catVenuePrevOnload = null;
if (window.onload) {
    catVenuePrevOnload = window.onload;
}
window.onload = catVenueOnReload;

var catVenueoptions = {};
var catVenuedefaults = {};


catVenueOptions = {cat_venue_options};
catVenueDefault = {cat_venue_default};
</script>
