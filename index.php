<HTML>

	<head>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js"></script>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.js"></script>
	
		<script type="text/javascript" src="js/eventviewer.js"></script>
		<script type="text/javascript" src="js/raphael-min.js"></script>
		<script type="text/javascript" src="js/g.raphael-min.js"></script>
		<script type="text/javascript" src="js/g.bar-min.js"></script>
		
		<link href="css/ui-lightness/jquery-ui-1.8.20.custom.css" rel="stylesheet" type="text/css">
		<link href="css/eventviewer.css" rel="stylesheet" type="text/css">


<script>

$(document).ready(function() {
	//once the DOM is built, send credentials to the eventviewer server
	var user = "phog";
	var pass = "phog1";

	var dataString = "method=login&user=" + user + "&pass=" + pass;
	//use ajax to post the datastring via get.php, which should log us in
	$.ajax({
		type: "GET",
		url: "get.php",
		data: dataString,
		success: function(data) {
			//on success, log the script's response to the console
			console.log(data);
		}
	})

})
	//jquery UI -- set up our tabs
	$(function() {
		$( "#tabs" ).tabs({
			cache: true,
			ajaxOptions: {
				//in case there's an error, display an extremely helpful message
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
