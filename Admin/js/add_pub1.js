<script language="JavaScript" type="text/JavaScript">

 // $Id: add_pub1.js,v 1.2 2007/04/27 18:27:03 loyola Exp $

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

function catVenuefindOptions(ary, keys) {
    var key = keys.shift();
    if (!key in ary) {
        return {};
    }
    else if (0 == keys.length) {
        return ary[key];
    }
    else {
        return catVenuefindOptions(ary[key], keys);
    }
}

function catVenuefindSelect(form, groupName, selectIndex) {
    if (groupName+'['+ selectIndex +']' in form) {
        return form[groupName+'['+ selectIndex +']'];
    } else {
        return form[groupName+'['+ selectIndex +'][]'];
    }
}

function catVenueunescapeEntities(str) {
    var div = document.createElement('div');
    div.innerHTML = str;
    return div.childNodes[0] ? div.childNodes[0].nodeValue : '';
}

function catVenuereplaceOptions(ctl, optionList) {
    var j = 0;
    ctl.options.length = 0;
    for (i in optionList) {
        var optionText = (-1 == optionList[i].indexOf('&'))
            ? optionList[i]: catVenueunescapeEntities(optionList[i]);
        ctl.options[j++] = new Option(optionText, i, false, false);
    }
}

function catVenuesetValue(ctl, value) {
    var testValue = {};
    if (value instanceof Array) {
        for (var i = 0; i < value.length; i++) {
            testValue[value[i]] = true;
        }
    }
    else {
        testValue[value] = true;
    }

    for (var i = 0; i < ctl.options.length; i++) {
        if (ctl.options[i].value in testValue) {
            ctl.options[i].selected = true;
        }
    }
}

function catVenueSwapOptions(form) {
    var cat_id = form.elements['cat_id'].value;

    var ctl = form.elements['venue_id'];
    ctl.options.length = 0;

    var j = 0;
    for (i in catVenueOptions[cat_id]) {
        var optionText;
        if (catVenueOptions[cat_id][i].indexOf('&') != -1) {
            catVenueOptions[cat_id][i];
        }
        else {
            catVenueunescapeEntities(catVenueOptions[cat_id][i]);
        }
        ctl.options[j++] = new Option(optionText, i, false, false);
    }
}

function catVenueOnReset(form, groupNames) {
    for (var i = 0; i < groupNames.length; i++) {
        try {
            for (var j = 0; j <= catVenueoptions[groupNames[i]].length; j++) {
                catVenuesetValue(catVenuefindSelect(form, groupNames[i], j), catVenuedefaults[groupNames[i]][j]);
                if (j < catVenueoptions[groupNames[i]].length) {
                    catVenuereplaceOptions(catVenuefindSelect(form, groupNames[i], j + 1),
                                       catVenuefindOptions(catVenueoptions[groupNames[i]][j], catVenuedefaults[groupNames[i]].slice(0, j + 1)));
                }
            }
        } catch (e) {
            if (!(e instanceof TypeError)) {
                throw e;
            }
        }
    }
}

// is this function needed?
function catVenueSetupOnReset(form, groupNames) {
    setTimeout(function() { catVenueOnReset(form, groupNames); }, 25);
}

function catVenueOnReload() {
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

catVenueOptions = [ {
    '1': {
        '' :  ['--Select Venue--', 1 ],
        '-1': ['--No venue--', 1],
        '5':  ['AAAI - National Conference on Artificial Intelligence (AAAI)', 0],
        '119': ['AAMAS - Joint Conference on Autonomous Agents and Multi-Agent Systems (AAMAS)', 1],
    },
    '3': {
        '' :  ['--Select Venue--', 1 ],
        '-1': ['--No venue--', 1],
        '167': ['Adaptive Behavior', 1],
        '39': ['AIJ - Artificial Intelligence (AIJ)', 0],
        '168': ['Artificial Intelligence', 0]
    }
} ];

catVenuedefaults['venue_id'] = [0, ''];
</script>
