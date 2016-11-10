$(function(){
	
	$('.row').each(function(){

	  //Selectionne à l'intérieur toutes les classes qui commencent par "col"
	  var cols = $(this).find('[class^=span]');
	     
	  var heights = new Array();
	  var i =0;

	  //Parcours les colonnes
	  $(cols).each(function(i){
	    var col = $(this);
	    
	    heights[i] = $(this).css('height');
	    heights[i] = parseInt(heights[i]);
	    i++;
	    
	  });

	  //Détermine la valeur maximale
	  var max_height_value = Math.max.apply(Math, heights);

	  //Applique cette valeur à tous
	  $(cols).each(function(i){
	    $(this).css('height',max_height_value);
	  });


	});
});