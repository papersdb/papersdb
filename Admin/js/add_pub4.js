<script language="JavaScript" type="text/JavaScript">

var paperAtt =
     "Attach a postscript, PDF, or other version of the publication.";

var otherAtt =
    "In addition to the primary paper attachment, attach additional "
    + "files to this publication.";

var extraInfoHelp=
    "Specify auxiliary information, to help classify this "
    + "publication. Eg, &quot;with student&quot; or &quot;best "
    + "paper&quot;, etc. Note that, by default, this information will "
    + "NOT be shown when this document is presented. Separate using "
    + "semiolons(;).";

var extLinks=
    "Used to link this publication to an outside source such as a "
    + "website or a publication that is not in the current database.";

var pubLinks =
    "Used to link other publications in the database to this "
    + "publication.";

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

</script>
