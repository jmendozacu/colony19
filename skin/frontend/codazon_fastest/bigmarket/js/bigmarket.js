( function($) {
	$.fn.scrollBar = function(options){
		var defaultConfig = {
			colMain: '.col-main',
			header: '.header-container',
			stickyMenu: '.sticky-menu'
		};
		var conf = $.extend({},defaultConfig,options);
		var $win = $(window),
		$colMain = $(conf.colMain).first(), $header = $(conf.header).first(), $stickyMenu = $(conf.stickyMenu).first();
		return this.each(function(){
			var $this = $(this);
			function getLeft(){
				var winW = $win.width(), colWith = $colMain.width(), left = (winW - colWith)/2 + colWith + 10;
				return left;
			}
			function getTop(){
				if($stickyMenu.hasClass('active')){
					var top = '';	
				}else{
					var top = $header.outerHeight() + 10;
				}
				return top;
			}
			function assignPosition(){
				$this.css({left:getLeft(), top: getTop()});
			}
			assignPosition()
			$win.scroll(assignPosition);
			var timeout = false;
			$win.resize(function(){
				if(timeout) clearTimeout(timeout);
				timeout = setTimeout(assignPosition,300);
			});
		});
	}
	$(document).ready(function(e) {
		$('.float-bar').scrollBar();
    });
} )(jQuery);