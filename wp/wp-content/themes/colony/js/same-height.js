$(function(){



	var w_w = $(window).width();
	if(w_w > 768){

	
		$('.row').each(function(){

		  //Selectionne à l'intérieur toutes les classes qui commencent par "col"
		  var cols = $(this).find('[class^=col]');
		     
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

		  var window_width = $(window).width();

		  if(window_width > 480){
		  $(cols).each(function(i){
		    //$(this).find('.module').css('height',max_height_value);
		  });
		  }
		  

		});

	}

});



$(function(){
	//Pour que les tailles des produits soient homogènes

	var w_w = $(window).width();
	if(w_w > 768){

	  //Selectionne à l'intérieur toutes les classes qui commencent par "col"
	  var cols = $(this).find('#productsList .product');
	     
	  var heights = new Array();
	  var i =0;

	  //Parcours les colonnes
	  $(cols).each(function(i){
	    var col = $(this);

	    console.log(i);
	    
	    heights[i] = $(this).css('height');
	    heights[i] = parseInt(heights[i]);
	    i++;
	    
	  });

	  //Détermine la valeur maximale
	  var max_height_value = Math.max.apply(Math, heights);

	  //Applique cette valeur à tous

	  var window_width = $(window).width();

	  if(window_width > 480){
	  $(cols).each(function(i){
	    $(this).css('height',max_height_value);
	  });
	  }
	
	}

	//});

});