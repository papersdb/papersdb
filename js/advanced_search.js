// $Id: advanced_search.js,v 1.4 2007/10/31 17:49:36 loyola Exp $

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
            } 
            else if (element.type == "select-multiple"){
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
    form.authors.value     = "{authors}";
    form.paper.value       = "{paper}";
    form.abstract.value    = "{abstract}";
    form.venue.value       = "{venue}";
    form.keywords.value    = "{keywords}";
        
    // since we are using PEAR HTML_QuickForm the radio checkboxes have 2
    // elements with the same name, we need to access the checkbox element
    form.author_myself[1].checked = "{author_myself}"
    form.show_internal_info[1].checked = "{show_internal_info}";
    
    var paper_rank = document.getElementsByName("paper_rank[1]");
    if (paper_rank.length == 2) 
    	paper_rank[1].checked = "{paper_rank1}";
    	
  	paper_rank = document.getElementsByName("paper_rank[2]");
    if (paper_rank.length == 2) 
    	paper_rank[1].checked = "{paper_rank2}";
    	
  	paper_rank = document.getElementsByName("paper_rank[3]");
    if (paper_rank.length == 2) 
    	paper_rank[1].checked = "{paper_rank3}";

  	paper_rank = document.getElementsByName("paper_rank[4]");
    if (paper_rank.length == 4) 
    	paper_rank[1].checked = "{paper_rank4}";
    	
    var paper_rank_other = document.getElementsByName("paper_rank_other");  
    if (paper_rank_other.length > 0)
    	form.paper_rank_other.value = "{paper_rank_other}";
   	
    var paper_col = document.getElementsByName("paper_col[1]");
    if (paper_col.length == 2) 
    	paper_col[1].checked = "{paper_col1}";
    	
  	paper_col = document.getElementsByName("paper_col[2]");
    if (paper_col.length == 2) 
    	paper_col[1].checked = "{paper_col2}";
    	
  	paper_col = document.getElementsByName("paper_col[3]");
    if (paper_col.length == 2) 
    	paper_col[1].checked = "{paper_col3}";

  	paper_col = document.getElementsByName("paper_col[4]");
    if (paper_col.length == 4) 
    	paper_col[1].checked = "{paper_col4}";
    	    	
    var date = document.getElementsByName("startdate[Y]");
    if (date.length > 0)
    	date[0].value = "{startdateY}";
    
    date = document.getElementsByName("startdate[M]");
    if (date.length > 0)
    	date[0].value = "{startdateM}";
    	
    date = document.getElementsByName("enddate[Y]");
    if (date.length > 0)
    	date[0].value = "{enddateY}";
    
    date = document.getElementsByName("enddate[M]");
    if (date.length > 0)
    	date[0].value = "{enddateM}";
}
