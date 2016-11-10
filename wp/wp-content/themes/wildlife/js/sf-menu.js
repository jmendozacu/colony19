// Init navigation menu
jQuery(function(){
// main navigation init
	jQuery('ul.sf-menu').superfish({
		delay: <?php echo (of_get_option('sf_delay')!='') ? of_get_option('sf_delay') : 600; ?>, // the delay in milliseconds that the mouse can remain outside a sub-menu without it closing
		animation: {
			opacity: "<?php echo (of_get_option('sf_f_animation')!='') ? of_get_option('sf_f_animation') : 'show'; ?>",
			height: "<?php echo (of_get_option('sf_sl_animation')!='') ? of_get_option('sf_sl_animation') : 'show'; ?>"
		}, // used to animate the sub-menu open
		speed: "<?php echo (of_get_option('sf_speed')!='') ? of_get_option('sf_speed') : 'normal'; ?>", // animation speed 
		autoArrows: <?php echo (of_get_option('sf_arrows')==false) ? 'false' : of_get_option('sf_arrows'); ?>, // generation of arrow mark-up (for submenu)
		disableHI: true // to disable hoverIntent detection
	});

//Zoom fix
//IPad/IPhone
	var viewportmeta = document.querySelector && document.querySelector('meta[name="viewport"]'),
		ua = navigator.userAgent,
		gestureStart = function () {
			viewportmeta.content = "width=device-width, minimum-scale=0.25, maximum-scale=1.6, initial-scale=1.0";
		},
		scaleFix = function () {
			if (viewportmeta && /iPhone|iPad/.test(ua) && !/Opera Mini/.test(ua)) {
				viewportmeta.content = "width=device-width, minimum-scale=1.0, maximum-scale=1.0";
				document.addEventListener("gesturestart", gestureStart, false);
			}
		};
	scaleFix();
});