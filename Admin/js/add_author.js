// $Id: add_author.js,v 1.8 2008/02/16 00:12:44 loyola Exp $

window.addEvent('domready', function() {
                    var Tips1 = new Tips($$('.Tips1'));
                });

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

