;jQuery(function($) {
	$("#Type input").change(function() {
		$("#Price").toggle($(this).val() == "Price");
	});
	
	if ($("#Type input:checked").length) {
		$("#Type input:checked").trigger("change");
	} else {
		$("#Price").hide();
	}	
});