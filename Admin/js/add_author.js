// $Id: add_author.js,v 1.1 2007/04/04 20:15:12 aicmltec Exp $

<script language="JavaScript" type="text/JavaScript">
var addAuthorPageHelp=
     "To add an author you need to input the author's first name, "
     + "last name, email address and organization. You must also "
     + "select interet(s) that the author has. To do this you can "
     + "select interest(s) allready in the database by selecting "
     + "them from the listbox. You can select multiple interests "
     + "by control-clicking on them. If you do not see the "
     + "appropriate interest(s) you can add interest(s) using "
     + "the Add Interest link.<br/><br/>"
     + "Clicking the Add Interest link will bring up additional fields "
     + "everytime you click it. You can then type in the name of the "
     + "interest into the new field provided.";

var authTitleHelp=
    "The title of an author. Will take the form of one of: "
    + "<ul>"
    + "<li>Prof</li>"
    + "<li>PostDoc</li>"
    + "<li>PhD student</li>"
    + "<li>MSc student</li>"
    + "<li>Colleague</li>"
    + "<li>etc</li>"
    + "</ul>";

function dataKeep(num) {
    var qsArray = new Array();
    var qsString = "";
    var form = document.forms["authorForm"];

    for (i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];
        if ((element.value != "") && (element.value != null)
            && (element.type != "button") && (element.type != "reset")
            && (element.type != "submit")) {
            if (element.name == "interests[]") {
                var interest_count = 0;

                for (j = 0; j < element.length; j++) {
                    if (element[j].selected == 1) {
                        qsArray.push("interests[" + interest_count + "]="
                                     + element[j].value);
                        interest_count++;
                    }
                }
            }
            else if (element.name == "numNewInterests") {
                qsArray.push(element.name + "=" + num);
            }
            else  if (element.name == "authors_in_db[]") {
                qsArray.push(element.name + "=" + element.value);
            } else {
                qsArray.push(element.name + "=" + element.value);
            }
        }
    }

    if (qsArray.length > 0) {
        qsString = qsArray.join("&");
        qsString.replace(" ", "%20");
        qsString.replace("\"", "?");
    }

    location.href = "http://{host}{self}?" + qsString;
}

// client side check to make sure new author not already in DB.
function author_check(name, num) {
    var form = document.forms["authorForm"];

    if (form.elements["author_id"].length > 0) return;

    if (name.length != 2) return false;

    var authors_in_db = form.elements["authors_in_db"];
    var newAuthorName = name[1] + ", " + name[0].substr(0, 1);
    var authName;

    newAuthorName = newAuthorName.toLowerCase();

    for (i=0; i < authors_in_db.length; i++) {
        authName = authors_in_db.options[i].text.toLowerCase();
        if (authName.indexOf(newAuthorName) >= 0)
            return false;
    }
    return true;
}
</script>
