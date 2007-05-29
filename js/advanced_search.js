// $Id: advanced_search.js,v 1.1 2007/05/29 19:56:11 aicmltec Exp $

function dataKeep(num) {
    var form = document.forms["advSearchForm"];
    var qsArray = new Array();
    var qsString = "";

    for (i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];
        if ((element.value != "") && (element.value != null)
            && (element.type != "button")
            && (element.type != "reset")
            && (element.type != "submit")) {

            if (element.type == "checkbox") {
                if (element.checked) {
                    qsArray.push(element.name + "=" + element.value);
                }
            } else if (element.type == "select-multiple"){
                var select_name = element.name;
                if (select_name.indexOf("[]") > 0) {
                    select_name = select_name.substr(0, select_name.length - 2);
                }

                var count = 0;
                for (i=0; i < element.length; i++) {
                    if (element.options[i].selected) {
                        qsArray.push(select_name + "[" + count + "]=" + element.options[i].value);
                        count++;
                    }
                }
            } else {
                qsArray.push(element.name + "=" + element.value);
            }
        }
    }
    if (qsArray.length > 0) {
        qsString = qsArray.join("&");
        qsString.replace(" ", "%20");
    }
    location.href
        = "http://{host}{self}?"
        + qsString;
}

function lastSearchUse() {
    var form = document.forms["advSearchForm"];
    var authorselect = form.elements["authorselect[]"];
    var selected_authors = "{selected_authors}";

    form.cat_id.value      = "{cat_id}";
    form.title.value       = "{title}";
    form.authortyped.value = "{authortyped}";
    form.paper.value       = "{paper}";
    form.abstract.value    = "{abstract}";
    form.venue.value       = "{venue}";
    form.keywords.value    = "{keywords}";
    form.paper_rank_other.value = "{paper_rank_other}";

    for (var i = 0; i < form.elements.length; i++) {
        if (form.elements[i].name == "startdate[Y]")
            form.elements[i].value = "{startdateY}";
        if (form.elements[i].name == "startdate[M]")
            form.elements[i].value = "{startdateM}";
        if (form.elements[i].name == "enddate[Y]")
            form.elements[i].value = "{enddateY}";
        if (form.elements[i].name == "enddate[M]")
            form.elements[i].value = "{enddateM}";

        if (form.elements[i].name == "paper_rank[1]")
            form.elements[i].value = "{paper_rank1}";
        if (form.elements[i].name == "paper_rank[2]")
            form.elements[i].value = "{paper_rank2}";
        if (form.elements[i].name == "paper_rank[3]")
            form.elements[i].value = "{paper_rank3}";
        if (form.elements[i].name == "paper_rank[4]")
            form.elements[i].value = "{paper_rank4}";

        if (form.elements[i].name == "paper_col[1]")
            form.elements[i].value = "{paper_col1}";
        if (form.elements[i].name == "paper_col[2]")
            form.elements[i].value = "{paper_col2}";
        if (form.elements[i].name == "paper_col[3]")
            form.elements[i].value = "{paper_col3}";
        if (form.elements[i].name == "paper_col[4]")
            form.elements[i].value = "{paper_col4}";
    }

    var author_myself = "{author_myself}";
    if (author_myself.length > 0) {
        form.author_myself[1].checked = true;
    }

    for (var i =0; i < authorselect.length; i++) {
        authorselect.options[i].selected = false;
        if (selected_authors.indexOf(":" + authorselect.options[i].value + ":") >= 0) {
            authorselect.options[i].selected = true;
        }
    }
    //dataKeep(0);
}
