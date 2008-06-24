/*-----------------------------------------------------------------------------
 *
 * The information contained herein is proprietary and confidential to Alberta
 * Ingenuity Centre For Machine Learning (AICML) and describes aspects of AICML
 * products and services that must not be used or implemented by a third party
 * without first obtaining a license to use or implement.
 *
 * Copyright 2008 Alberta Ingenuity Centre For Machine Learning.
 *
 *-----------------------------------------------------------------------------
 */

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
    form.user_info.value   = "{user_info}";
        
    // since we are using PEAR HTML_QuickForm the radio checkboxes have 2
    // elements with the same name, we need to access the checkbox element
    var author_myself = document.getElementsByName("author_myself[1]");
    if (author_myself)
        author_myself.checked = "{author_myself}"
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
