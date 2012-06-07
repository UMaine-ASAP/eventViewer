<HTML>

	<head>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.js"></script>
	
		<script type="text/javascript" src="js/eventviewer.js"></script>
		
		<link href="css/ui-lightness/jquery-ui-1.8.20.custom.css" rel="stylesheet" type="text/css">
		<link href="css/eventviewer.css" rel="stylesheet" type="text/css">


<script>

$(document).ready(function() {
	var user = "phog";
	var pass = "phog1";

	var dataString = "method=login&user=" + user + "&pass=" + pass;

	$.ajax({
		type: "GET",
		url: "get.php",
		data: dataString,
		success: function(data) {
			console.log(data);
		}
	})

})

	$(function() {
		$( "#tabs" ).tabs({
			cache: true,
			ajaxOptions: {
				error: function( xhr, status, index, anchor ) {
					$( anchor.hash ).html(
						"Couldn't load this tab. We'll try to fix this as soon as possible. " +
						"If this wasn't a demo." );
				}
			}
		});
	});



</script>


	</head>

	<body>



	<div id="tabs">
		<ul>
			<li><a href="ajax/events.php">Events</a></li>
			<li><a href="ajax/locations.php">Locations</a></li>
			<li><a href="ajax/times.php">Times</a></li>

		</ul>
	</div>
	<div id="builder">
	
	
	</div>
	</body>
</HTML>
