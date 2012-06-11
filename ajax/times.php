<?PHP
?>
<script>

var generateBad = function(x, width, paper) {
	var bad = paper.rect(x, 1, width, 38);
	bad.attr({fill: '#ccc', stroke: 'none'});
}

$(document).ready(function() {
	$("#timespans").buttonset();
	$("#daterange").slider({
		range: true,
		min: 1800,
		max: 2012,
		values: [1978, 1997],
		slide: function(event, ui) {
			$("#year").text(ui.values[0] +  "-" + ui.values[1]);
		}
	});

	//initialize year label to the slider's values
	$("#year").text($("#daterange").slider("values", 0) + "-" + $("#daterange").slider("values", 1));

	var width = $("#daterange").width(); //the width of the slider
	var paper = new Raphael(document.getElementById("daterange_canvas"), width, 40); //create a canvas to draw on -- the width of the slider and 40px in height
	var background = paper.rect(0, 0, width, 40);
	background.attr({fill: 'green'});

	generateBad(250, 20, paper);
	generateBad(350, 15, paper);
	generateBad(100, 8, paper);
});

</script>
<form>
	<div id="timespans" style="text-align: center">
		<input type="radio" name="timespans" id="radio1"><label for="radio1">Year</label></input>
		<input type="radio" name="timespans" id="radio2"><label for="radio2">Month</label></input>
		<input type="radio" name="timespans" id="radio3"><label for="radio3">Day</label></input>
		<input type="radio" name="timespans" id="radio4"><label for="radio4">Custom</label></input>
	</div>
	<label for="year" id="year">If this has no numbers on it, something is wrong!</label>
	<div id="daterange"></div>
	<div id="space"></div>
	<div id="daterange_canvas"></div>
	
<form>