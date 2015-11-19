//here goes all general use javascript

function gen(obj) {
	var ret = "<" + obj.tag;
	for (var prop in obj)
		if (prop != "children" && prop != "tag")
			ret += " " + prop + "='" + obj[prop] + "'";
	var html = "";
	if (obj.children) {
		for (var child in obj.children) {
			if (typeof(obj.children[child]) === 'string')
				html += obj.children[child];
			else
				html += gen(obj.children[child]);
		}
	}
	return ret + ">" + html + "</" + obj.tag + ">";
}