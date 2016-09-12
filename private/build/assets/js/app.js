// Avoid `console` errors in browsers that lack a console.
(function() {
    var method;
    var noop = function () {};
    var methods = [
        'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
        'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
        'markTimeline', 'profile', 'profileEnd', 'table', 'time', 'timeEnd',
        'timeStamp', 'trace', 'warn'
    ];
    var length = methods.length;
    var console = (window.console = window.console || {});

    while (length--) {
        method = methods[length];

        // Only stub undefined methods.
        if (!console[method]) {
            console[method] = noop;
        }
    }
}());

(function($) {
	w = $(window).width();
	h = $(window).height();
	
	$.extend({
		//http://stackoverflow.com/a/23084051
		wait: function(ms) {
		    var defer = $.Deferred();
		    setTimeout(function() { defer.resolve(); }, ms);
		    return defer;
		},
		
		//http://pressupinc.com/blog/2014/02/setting-dynamic-equal-heights-multiple-elements-jquery/
		equalizeHeights: function(selector) {
			var heights = new Array();

			// Loop to get all element heights
			$(selector).each(function() {

				// Need to let sizes be whatever they want so no overflow on resize
				$(this).attr('style', 'min-height: 1px; height: auto !important; height: 1px;');
				//$(this).css({'min-height': '1px', 'height': 'auto'});
				
				// Then add size (no units) to array
		 		heights.push($(this).outerHeight());
			});

			// Find max height of all elements
			var max = Math.max.apply( Math, heights );

			// Set all heights to max height
			$(selector).each(function() {
				$(this).attr('style', 'min-height: ' + max + 'px; height: auto !important; height: ' + max + 'px;');
				//$(this).css({'min-height': max + 'px', 'height':  max + 'px'});
			});	
		},
		
		fix_theme: function() {
			w = $(window).width();
			
			if(w >= 768) {
				$.equalizeHeights('.equalize');
			}
		},
		
		fix_theme_callback: function() {
			$.fix_theme();
		},
		
		getLabel: function(title) {
			switch(title) {
			    case 'start':
			         return 'label label-info';
			        break;
			    case 'complete':
			         return 'label label-success';
			        break;
			    case 'warning':
					  return 'label label-warning';
					  break;     
			    case 'fail':
			         return 'label label-danger';
			        break;
			    case 'reset':
					  return 'label label-default';
					  break;    
			    default:
			        return '';
			}
		}
	});
	
	//$('[data-toggle="tooltip"]').tooltip();
	
	$(window).resize(function() {
		$.fix_theme_callback();
	});
	
	$(window).bind("load", function() {
		$.fix_theme_callback();
	});
})(jQuery);
