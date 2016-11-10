<?php 
/* The HTML5 Shim is required for older browsers, mainly older versions IE */ ?>
<!--[if lt IE 8]>
<div style=' clear: both; text-align:center; position: relative;'>
	<a href="http://www.microsoft.com/windows/internet-explorer/default.aspx?ocid=ie6_countdown_bannercode"><img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0000_us.jpg" border="0" alt="" /></a>
</div>
<![endif]-->
<!--[if (gt IE 9)|!(IE)]><!-->
<script src="<?php echo PARENT_URL; ?>/js/jquery.mobile.customized.min.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(function(){
		jQuery('.sf-menu').mobileMenu({defaultText: <?php echo '"'.of_get_option('mobile_menu_label').'"'; ?>});
	});
</script>
<!--<![endif]-->