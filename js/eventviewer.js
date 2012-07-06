var EventViewer = {
	query: function(dataString, success) {
		$.ajax({
			type: "GET",
			url: "get.php",
			data: dataString,
			success: success,
		});
	}
};