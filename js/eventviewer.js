var EventViewer = {
	query: function(dataString, success) {
		$.ajax({
			type: "GET",
			url: "get.php",
			data: dataString,
			success: success
		});
	}
};

Array.prototype.filterOutValue = function(v) {
    var x, _i, _len, _results;
    _results = [];
    for (_i = 0, _len = this.length; _i < _len; _i++) {
        x = this[_i];
        if (x !== v) {
            _results.push(x);
        }
    }
    return _results;
};