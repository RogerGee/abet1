(function($) {
	$.extend({
		confirm: function(title, message, accept, decline) {
			if (typeof(message) === 'undefined') {
				message = typeof(title) !== 'undefined' ? title : "Are you sure?";
				title = "Confirm";
			}
			title = typeof(title) !== 'undefined' ? title : "Confirm";
			accept = typeof(accept) !== 'undefined' ? accept : "yes";
			decline = typeof(decline) !== 'undefined' ? decline : "no";
			$("body").append(gen({tag:"div", "class":"confirm_wrapper", children:[
				{tag:"div", "class":"overlay"},
				{tag:"div", "class":"box", children:[
					{tag:"div", "class":"title", children:[title]},
					{tag:"div", "class":"message", children:[message]},
					{tag:"div", "class":"buttons", children:[
						{tag:"input", type:"button", id:"_cy", value:accept},
						{tag:"input", type:"button", id:"_cn", value:decline}
					]}
				]}
			]}));
			var wrapper = $(".confirm_wrapper");
			wrapper.children(".overlay").on("click", function(event) {
				event.stopPropagation();
			});
			$(".confirm_wrapper input").on("click", function() {
				wrapper.remove();
			});
			return wrapper;
		}
	});
	$.fn.extend({
		accept: function(func) {
			this.find("#_cy").on("click", func);
			return this;
		},
		decline: function(func) {
			this.find("#_cn").on("click", func);
			return this;
		}
	});
})(jQuery);