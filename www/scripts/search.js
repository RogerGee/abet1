/* search functionality */
var term = "";
var page = 0;
var dirty = false;

function initSearch() {
	$("#search").focus(function(event) {
		if ($("#search_results").css("display") == "none")
			$("#search_results").fadeToggle(300);
	});
	$("#search").on("keypress", function(event) {
		if (event.keyCode == 13) {
			this.blur();
			search();
		}
	});
	$("#search").on("change", function(event) {
		term = $(this).val();
		page = 0;
		dirty = true;
	});
}

function processResults(data) {
	$("#search_results").html("");
	for (var i = 0; i < data.results.length; i++) {
		$("#search_results").append(gen({tag:"a", "class":"internal", 
			href:data.results[i].type, id:data.results[i].id, children:{tag:"div",
				children:[
					{tag:"h3", children:data.results[i].label},
					{tag:"p", children:data.results[i].preview}
				]
			}
		}));
	}
	var div = {tag:"div", children:[]};
	if (page > 0)
		div.children.push({tag:"input", type:"button", value:"prev"});
	if ((page + 1) * 10 < data.count)
		div.children.push({tag:"input", type:"button", value:"next"});
	$("#search_results").append(gen(div));
	$(".results input[value=prev]").click(function() {
		page--;
		search();
	});
	$(".results input[value=next]").click(function() {
		page++;
		search();
	});
	hijackAnchors();
}

function search() {
	if (!dirty) return;
	dirty = false;
	var data = {q:term, no:page};
	$.ajax({url:"search.php", data:data, dataType:"json"}).done(processResults);
}

