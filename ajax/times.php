<?PHP
?>
<script>

var badData = [[1960, 1985], [1990, 1995]];

var generateBad = function(paper, badData) {
	//sample input: badData = [[1960, 1985], [1990, 1995]]
	for (var i = 0; i < badData.length; i++) {
		//determine the starting and ending dates of the bad data
		var start = badData[i][0];
		var end = badData[i][1];

		//...and the start and end of the year ranges
		var yearStart = $("#daterange").slider("option", "min");
		var yearEnd = $("#daterange").slider("option", "max");

		//remember the total span of years between the start and the end
		var total = (yearEnd - yearStart);

		//now that we have absolute spans of time, convert those into px for the rectangles
		start = start - yearStart;
		end = end - yearStart;

		var width = $("#daterange_canvas").width();
		var startPx = (start / total) * width;

		//draw the rectangle!
		var endPx = (end / total) * width;
		var rect = paper.rect(startPx, 0, endPx - startPx, 40);

		rect.attr({fill: 'red'}); //and fill it with red because why on earth not
	}
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

	generateBad(paper, badData);
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