<?PHP
if(!isset($_GET['category'])){
?>
<script>
$(document).ready(function() {

	var dataString = "method=getRootCategories";

	$.ajax({
		type: "GET",
		url: "get.php",
		data: dataString,
		success: function(data) {
			console.log(data);
			build_location(data);
		}
	})

});
</script>
<div id="locations_container"><ul class="container"></ul></div>
<?PHP
}
else {
	if($_GET['category'] == "0"){
	?>
	<script>
		$(document).ready(function() {
			var category_id = <?PHP echo $_GET['category']; ?>;
			
			var dataString = "method=getSubCategories&category_id=8";
			
			$.ajax({
		});
	</script>
<?PHP
	}

	else {
		echo "<div id=location-" . $_GET['category'] . "></div>"; 
		?>
		<script>
			$(document).ready(function() {
				var category_id = <?PHP echo $_GET['category']; ?>;
				//console.log(category_id)
				query(category_id);
			});
		</script>
		<?PHP
	}
}
?>
