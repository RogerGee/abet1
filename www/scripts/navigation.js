/* functions for the navbar */
function decodeNavInner(data) {
	var li = {tag:"li", children:[{tag:"div"}]};
	if (data.children) {
		li.children[0].children = [data.label];
		li.children.push({tag:"ul", children:[]})
		for (i in data.children)
			li.children[1].children.push(decodeNavInner(data.children[i]));
	} else if (data.type) {
		li.children[0].children = [
			{tag:"a", "class":"internal", href:data.type, id:data.id}];
		li.children[0].children[0].children = [data.label];
	}
	return li;
}

function decodeNav(data, id) {
	var ul = {tag:"ul", id:id, children:[]};
	for (i in data)
		ul.children.push(decodeNavInner(data[i]));
	return ul;
}

function loadNavigation() {
	$.ajax({url:"/nav.php",dataType:"json"}).done(function(data) {
		$("#left_bar").html("");
		$("#left_bar").append(data[0].label);
		$("#left_bar").append(gen(decodeNav(data[0].children, "navtree")));
		if (data.length > 1) {
			$("#left_bar").append(data[1].label);
			$("#left_bar").append(gen(decodeNav(data[1].children, "admintree")));
		}
		$("#navtree, #admintree").tree();
		hijackAnchors();
	});
}

