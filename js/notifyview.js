(function($) {


/**
 *		Query - Backbone Model
 *
 *		@ kind			string 		The kind of value we are given (ie. event, location, time)
 *		@ id_type		string 		The type of id we are given (location_id, measurand_id)
 *		@ id_value		int 		The id value associated with type of id we are given
 *		@ name			string 		Name of constraint.
 * 		@ description	string 		Not required.  EV Description if one exists.
 *
 */


 	var Constraint = Backbone.Model.extend({
 		defaults: {
 			kind: '',
 			id_type: '',
 			id_value: '',
 			name: 'test',
 			description: ''
 		}
 	});

	var QueryList = Backbone.Collection.extend({
		model: Constraint
	});


	var ConstraintView = Backbone.View.extend({

		tagName: 'li',

		//TODO Add delete


		initialize: function(){
			_.bindAll(this, 'render');

			this.model.bind('change', this.render);
		},

		render: function(){
			$(this.el).html(this.model.get('name'));
			return this;
		}
	});


	var QueryView = Backbone.View.extend({

		el: $('#notify-box'),

		events: {
			'click .constraint': 'addConstraint',
		},

		initialize: function() {
			_.bindAll(this, 'render', 'addConstraint', 'appendQuery');
		},

		render: function() {
			var self = this;

			$(this.el).append("<ul class=\"unstyled\"></ul>");
			_(this.collection.models).each(function(item) {
				this.appendQuery(item);
			});
		},

		addConstraint: function() {
			var constraint = new Constraint();
			this.collection.add(constraint);
		},

		appendQuery: function() {
			var list = $(this.el).children("ul");

			var constraintView = new ConstraintView({
				model: Constraint,
			});

			$(list).last().append(constraintView.render().el);
		}

	});

	var queryView = new QueryView();
})(jQuery);