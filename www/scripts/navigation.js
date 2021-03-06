/* functions for the navbar */
function decodeNavInner(data) {
	var li = {tag:"li", label:data.label, identity:data.identity, children:[{tag:"div"}]};
	if (data.children) {
		li.children[0].children = [data.label];
		li.children.push({tag:"ul", children:[]})
		for (var i in data.children)
			li.children[1].children.push(decodeNavInner(data.children[i]));
	} else if (data.type) {
		li.children[0].children = [{tag:"a", "class":"internal", href:data.type, id:data.id}];
		li.children[0].children[0].children = [data.label];
	}
	return li;
}

function decodeNav(data, id) {
	var ul = {tag:"ul", id:id, children:[]};
	for (var i in data)
		ul.children.push(decodeNavInner(data[i]));
	return ul;
}

function compNavInner(tree, data) {
	$(tree).children().each(function() {
		var found = false;
		var i = 0
		for (i = 0; i < data.children.length; i++) {
			if (data.children[i].found)
				continue;
			if (data.children[i].identity == $(this).attr("identity")) {
				found = true;
				break;
			}
		}
		if (!found) {
			$(this).remove();
			return;
		}
		if (data.children[i].label != $(this).attr("label")) {
			data.children[i].move = true;
			$(this).attr("label", data.children[i].label);
			if (data.children[i].children) {
				$(this).children("div").html(data.children[i].label);
			} else {
				$(this).children("div").children("a").html(data.children[i].label);
			}
		}
		data.children[i].found = true;
		$(this).children("ul").each(function() {
			compNavInner(this, data.children[i]);
		});
	});
	for (var i = 0; i < data.children.length; i++) {
		var content;
		if (data.children[i].found) {
			if (!data.children[i].move)
				continue;
			content = $(tree).children("[identity="+data.children[i].identity+"]").detach();
		} else {
			content = gen(decodeNavInner(data.children[i]));
		}
		if (i == 0) {
			$($(tree).children()[0]).before(content);
		} else {
			$($(tree).children()[i-1]).after(content);
		}
	}
}

function compNav(data) {
	compNavInner($("#navtree"), data);
	$("#navtree").tree();
	hijackAnchors();
}

//global flag to prevent calling loadNavigation twice simultaneously
var __loading__ = false;

function loadNavigation() {
	if (__loading__)
		return;
	__loading__ = true;
	$.ajax({url:"/nav.php",dataType:"json"}).done(function(data) {
		if ($("#left_bar").html())
			return compNav(data[0]);
		$("#left_bar").append(data[0].label);
		$("#left_bar").append(gen(decodeNav(data[0].children, "navtree")));
		if (data.length > 1) {
			$("#left_bar").append(data[1].label);
			$("#left_bar").append(gen(decodeNav(data[1].children, "admintree")));
		}
		$("#navtree, #admintree").tree();
		hijackAnchors();
	}).done(function() {__loading__ = false;});
}

