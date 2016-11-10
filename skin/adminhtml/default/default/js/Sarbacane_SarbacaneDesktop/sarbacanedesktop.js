/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Sarbacane
 * @package     Sarbacane_Sarbacanedesktop
 * @author      Sarbacane Software <contact@sarbacane.com>
 * @copyright   2015 Sarbacane Software
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

function changeOptionOrdersData(id_shop_c, value) {
	if(value == true) {
		document.getElementById('id_shop_' + id_shop_c).value=id_shop_c + '1';
	}
	else {
		document.getElementById('id_shop_' + id_shop_c).value=id_shop_c + '0';
	}
}

function changeOptionOrdersDataDisplay(id_shop_c, value) {
	if(value == true) {
		document.getElementById('customer_data_' + id_shop_c).disabled=false;
		document.getElementById('id_shop_' + id_shop_c).value=id_shop_c + '0';

	}
	else {
		document.getElementById('id_shop_' + id_shop_c).value='';
		document.getElementById('customer_data_' + id_shop_c).disabled=true;
		document.getElementById('customer_data_' + id_shop_c).checked=false;
	}
}

function sdUserYesNoDisplayButton(user_selection) {
	Element.removeClassName("sd_step1 .sd_button","sd_step1_button");
}

function sdDisplayStep(step) {
	var otherSteps = $$(".sd_step");
	if(otherSteps != null && otherSteps.length > 0 ){
		otherSteps.each(function(item,i){
			Element.removeClassName(item,"sd_show_step");
		});
	}
	Element.addClassName("sd_step"+step,"sd_show_step");
	window.location.href = '#sd_step';
}

function sdInfoDataOrdersHover(id_shop_c) {
	Element.addClassName('sd_tooltip_' + id_shop_c,"sd_tooltip_show");
}

function sdInfoDataOrdersOut(id_shop_c) {
	Element.removeClassName('sd_tooltip_' + id_shop_c,"sd_tooltip_show");
//	$('#sd_tooltip_' + id_shop_c).removeClass('sd_tooltip_show');
}

function sdInfoDataOrdersClick(id_shop_c) {
	Element.toggleClassName('sd_tooltip_' + id_shop_c,"sd_tooltip_show");
//	$('#sd_tooltip_' + id_shop_c).toggleClass('sd_tooltip_show');
}
var isAlreadyUser = false;
function showStep2(){
	if($(sd_user_click_yes).checked){
		$(sd_step1).removeClassName("sd_show_step");
		$(sd_step2).addClassName("sd_show_step");
		$("3_step_block").hide();
		isAlreadyUser = true;
	}else{
		if($(sd_user_click_no).checked){
			$(sd_step1).removeClassName("sd_show_step");
			$(sd_step2).addClassName("sd_show_step");
			$("1_step_block").hide();
		}else{
		}
	}
}
function showStep3(){
	$(sd_step2).removeClassName("sd_show_step");
	$(sd_step3).addClassName("sd_show_step");
	if(isAlreadyUser){
		$("3_step_block").hide();
	}else{
		$("1_step_block").hide();
	}
}