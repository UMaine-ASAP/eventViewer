<script>
$(document).ready(function() {

	var dataString = "method=relation";

	$.ajax({
		type: "GET",
		url: "get.php",
		data: dataString,
		success: function(data) {
			console.log(data);
			build_structure(data);
		}
	})
});

function get_options(){

}

</script>

<div id="events_container"></div>
