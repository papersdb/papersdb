// $Id: view_author.js,v 1.1 2008/02/20 21:10:27 loyola Exp $

window.addEvent('domready', function() {   
	var state = 0;
	     							
	$('start').addEvent('click', function(e) {                
    	e = new Event(e).stop();

        var url = "ajax/author_pubs.php?author_id={author_id}";

		if (state == 0) {
        	/**
         	* The simple way for an Ajax request, use
         	* onRequest/onComplete/onFailure to do add your own Ajax
         	* depended code.
         	*/
        	new Ajax(url, {                         
       			method: 'get',                                 
       			update: $('publist'),
       			onRequest: function() {
	       			$('publist').setHTML('&nbsp;').addClass('ajax-loading');
       			},
       			onComplete: function() {
	       			$('publist').setStyle('opacity', 1);
       				$('start').setHTML('Hide Publications by this author');
       				$('publist').removeClass('ajax-loading');
       				state = 1; 
       			},
       			onFailure: function() {
       				$('publist').removeClass('ajax-loading');
	       			$('publist').adopt(new Element('span').setHTML('Failed requesting "' + this.url + '"!'));
       			}
       		}).request();  
       	}
       	else
            if ($('publist').getStyle('display') == 'block') {
        		$('publist').setStyle('display', 'none');
       			$('start').setHTML('Show Publications by this author');
        	}
        	else {
        		$('publist').setStyle('display', 'block');
       			$('start').setHTML('Hide Publications by this author');
        	}      
    });		
}); 

