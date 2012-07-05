
(function($) {


Backbone.sync = function(method, model, success, error){ 
	success();
}

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

			this.collection = new QueryList();
			this.collection.bind('add', this.appendQuery);

			this.render();
		},

		render: function() {
			var self = this;

			$(this.el).append("<ul class=\"unstyled\"></ul>");
			
		},

		addConstraint: function(id, type, name, kind) {
			var constraint = new Constraint();

				constraint.set({
					kind: kind,
					name: name,
					id_type: type,
					id_value: id,
				});

			this.collection.add(constraint);

			//LOGGING
			console.log("Added new constraint of kind \"" + kind + "\" with name: " + name + ", type: " + type + ", id: " + id)
		},

		appendQuery: function(constraint) {
			var list = $(this.el).children("ul");

			var constraintView = new ConstraintView({
				model: constraint,
			});

			$(list).last().append(constraintView.render().el);
		}

	});

	queryView = new QueryView();


})(jQuery);