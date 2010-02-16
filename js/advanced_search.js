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
    
    user_info = document.getElementsByName("user_info");
    if (user_info.length == 1)
    	user_info[0].value = "{user_info}";
    
    show_internal_info = document.getElementsByName("show_internal_info");
    if (show_internal_info.length == 2)
        show_internal_info[1].checked = "{show_internal_info}";
        
    // since we are using PEAR HTML_QuickForm the radio checkboxes have 2
    // elements with the same name, we need to access the checkbox element
    author_myself = document.getElementsByName("author_myself");
    if (author_myself.length == 2)
        author_myself[1].checked = "{author_myself}"
    
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
    if (paper_rank.length == 2) 
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
    if (paper_col.length == 2) 
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
