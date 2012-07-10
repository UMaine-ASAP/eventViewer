
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
			_.bindAll(this, 'render', 'unrender');

			this.model.bind('change', this.render);
			this.model.bind('remove', this.unrender);
		},

		render: function(){
			$(this.el).html(this.model.get('name'));
			return this;
		},

		unrender: function(){
			$(this.el).remove();
		}
	});


	var QueryView = Backbone.View.extend({

		el: $('#notify-box'),

		events: {
			'click .constraint': 'addConstraint',
		},

		initialize: function() {
			_.bindAll(this, 'render', 'addConstraint', 'removeConstraint', 'appendQuery');

			this.collection = new QueryList();
			this.collection.bind('add', this.appendQuery);

			this.render();
		},

		render: function() {
			var self = this;

			$(this.el).append("<ul class=\"unstyled\"><li>Events</li><li><ul id=\"events_view\"></ul></li><li>Locations</li><li><ul id=\"locations_view\"></ul></li><li>Times</li><li><ul id=\"times_view\"></ul></li></ul>");
			
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

		removeConstraint: function(id, name) {
			var remove = this.collection.where({
				id_value : id,
				name : name,
			});

			this.collection.remove(remove[0]);
			console.log(this.collection.toJSON());
		},

		appendQuery: function(constraint) {
			var list = $(this.el).children("ul");

			var kind = constraint.get('kind');

			var constraintView = new ConstraintView({
				model: constraint,
			});

			if(kind == "event"){
				$(list).find("#events_view").last().append(constraintView.render().el);
			}
			else if(kind == "location"){
				$(list).find("#locations_view").last().append(constraintView.render().el);
			}
			else if(kind == "time"){
				$(list).find("#times_view").last().append(constraintView.render().el);
			}

		},

	});

	queryView = new QueryView();


})(jQuery);