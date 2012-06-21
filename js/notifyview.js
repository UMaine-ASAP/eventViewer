(function($) {

	var Query = Backbone.Model.extend({ 
		defaults: {
			queryString: 'invalid query string',
			hasQueried: false,
			queryTime: '6/21/2012'
		}
	});

	var QueryList = Backbone.Collection.extend({
		model: Query
	});



	var NotifyView = Backbone.View.extend({ 
		el: $('#notify-box'),

		initialize: function() {
			_.bindAll(this, 'render', 'appendQuery');
			this.collection = new QueryList();
			this.collection.bind('add', this.appendQuery);

			var sampleQuery = new Query();
			this.collection.add(sampleQuery);
			this.render();
		},

		render: function() {
			var self = this;
			$(this.el).append("<ul class=\"unstyled\"></ul>");
			_(this.collection.models).each(function(item) {
				self.appendQuery(item);
			});
		},

		appendQuery: function(item) {
			$('ul', this.el).append("<li><i class=\"icon-info-sign\"></i> " + item.get('queryString') + "</li>");
		}
	});
	var notifyView = new NotifyView();
})(jQuery);