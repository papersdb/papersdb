// $Id: add_category.js,v 1.3 2007/05/11 20:12:10 aicmltec Exp $

var addCategoryPageHelp =
     "This window is used to add a new category of papers to the "
     + "database. The category should be used to describe the type of "
     + "paper being submitted. Examples of paper types include: "
     + "journal entries, book chapters, etc. <br/><br/> "
     + "When you add a category you can also select related field(s) "
     + "by clicking on the selection boxes. If you do not see the "
     + "appropriate related field(s) you can add field(s) by clicking "
     + "on the Add Field button to bring up additional fields where "
     + "you can type in the name of the related field you wish to add.";

function dataKeep(num) {
    var qsArray = new Array();
    var qsString = "";

    for (i = 0; i < document.forms["catForm"].elements.length; i++) {
        var element = document.forms["catForm"].elements[i];

        if ((element.type != "submit") && (element.type != "reset")
            && (element.type != "button") && (element.name != "")
            && (element.value != "") && (element.value != null)) {

            if (element.type == 'checkbox') {
                if (element.checked) {
                    qsArray.push(element.name + "=" + element.value);
                }
            }
            else if (element.name == 'numNewFields') {
                qsArray.push(element.name + "=" + num);
            }
            else {
                qsArray.push(element.name + "=" + element.value);
            }
        }
    }

    if (qsArray.length > 0) {
        qsString = qsArray.join("&");
        qsString.replace(" ", "%20");
    }

    location.href = "http://{host}{self}?" + qsString;
}

