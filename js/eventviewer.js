///////////////////////////////////////
//Events Functions
///////////////////////////////////////
function build_structure(data){

$.each(data, function(key, value){
	$('#events_container').append('<h3 id="' + key + '"><a href="#">'+ key + ' - ' + value + '</a></h3><div id="options_container" class="' + key + '"><div class="event_col1"></div><div class="event_col2"></div><div class="event_col3"></div><div class="event_col4"></div></div>');
})

$("#events_container").accordion({
	active: false,
	collapsible: true,
	change: function(event, ui){
		var clicked = $(this).find('.ui-state-active').attr('id');

		if($("div." + clicked).children('.event_col1').html() == "") {
			var dataString = "method=meta&relation="+ clicked;
			var value_id = clicked + "_id";

			$.ajax({
				type: "GET",
				url: "get.php",
				data: dataString,
				success: function(data) {
					var size = data.length;
					var count = Math.floor(size/4);
					var col = 1;
					console.log(data);
					$.each(data, function(key, value){
						var div = $("div." + clicked).children('.event_col' + col).append('<li class="' + clicked +'" id="' + value.value_id + '">' + value.name + "</li>");
						col++;
						if(col > 4){
							col = 1;
						}
					})
				}
			})
		}
		else{
		console.log(clicked);
		}

	}
});
}





///////////////////////////////////////
//Locations Functions
///////////////////////////////////////

function build_location(data){

	$.each(data, function(key,value){
		$('ul.container').append('<li><a href="ajax/locations.php?category=' + value.category_id + '">' + value.name + '</a></li>');
		//console.log(value.category_id);
	})

	$("#locations_container").tabs({
		cache: true,
		ajaxOptions: {
				error: function( xhr, status, index, anchor ) {
					$( anchor.hash ).html(
						"Couldn't load this tab. We'll try to fix this as soon as possible. " +
						"If this wouldn't be a demo." );
				}
			}
	});
}


function query(category_id) {
	var dataString = "method=getSubCategories&category_id=" + category_id;
	var height = 0;
	var newheight = 0;
	if($("div#location-" + category_id).html() == "") {
		$.ajax({
			type: "GET",
			url: "get.php",
			data: dataString,
			success: function(data) {
				console.log(data);
			
				id = category_id;
			
				$.each(data, function(key, value){
					if(value.child_id == id){
						$('div#location-' + id).append('<div>' + value.name + '</div>');
					}
					else{
						$('div#location-' + id).append('<h3 id="' + value.child_id + '"><a href="#">' + value.name + '</a></h3><div id="location-' + value.child_id + '" class="location_container" style="height: 300px;"></div>');
					
						height = $('div#location-' + id).css("height");
						newheight = height + 15;
						$('div#location-' + id).css("height", newheight + "px");
					}
				})
			
				$('div#location-' + id).accordion({
					active: false,
					autoHeight: false,
					collapsible: true,
					header: 'h3',
					change: function(event, ui){
						var clicked = $(this).find('.ui-state-active').attr('id');
						query(clicked);
					}
				});
			}
		});
	}
	else {
	
	}
}


function gulf_location(data, id){


}
