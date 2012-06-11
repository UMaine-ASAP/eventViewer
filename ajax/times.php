<?PHP
?>
<script>
$(function() {
	$("#timespans").buttonset();
});

</script>
<form>
	<div id="timespans" style="text-align: center">

		<input type="radio" name="timespans" id="radio1"><label for="radio1">Year</label></input>
		<input type="radio" name="timespans" id="radio2"><label for="radio2">Month</label></input>
		<input type="radio" name="timespans" id="radio3"><label for="radio3">Day</label></input>
		<input type="radio" name="timespans" id="radio4"><label for="radio4">Custom</label></input>
	</div>
<form>