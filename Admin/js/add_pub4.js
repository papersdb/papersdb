// $Id: add_pub4.js,v 1.4 2008/02/19 16:24:22 loyola Exp $

window.addEvent('domready', function() {
                    var Tips1 = new Tips($$('.Tips1'));
                });

function dataKeep(num) {
    var form =  document.forms["add_pub4"];
    var qsArray = new Array();
    var qsString = "";

    for (i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];

        if ((element.type != "submit") && (element.type != "reset")
            && (element.type != "button")
            && (element.value != "") && (element.value != null)) {

            if (element.type == "checkbox") {
                if (element.checked) {
                    qsArray.push(element.name + "=" + element.value);
                }
            } else if (element.name == "num_att") {
                qsArray.push(form.elements[i].name + "=" + num);
            } else if (element.type != "hidden") {
                qsArray.push(form.elements[i].name + "="
                             + form.elements[i].value);
            }
        }
    }

    if (qsArray.length > 0) {
        qsString = qsArray.join("&");
        qsString.replace(" ", "%20");
        qsString.replace("\"", "?");
    }

    // {host} and {self} are replaced by the PHP script that loads this file
    location.href = "http://{host}{self}?" + qsString;
}

