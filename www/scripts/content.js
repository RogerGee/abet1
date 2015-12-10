/* functions for editing general_content */
function getContent(id) {
	$.ajax({url:"content.php?id="+id,dataType:"json"}).done(function(general_content) {
		obj = {};
		obj.id = id;
		obj.content = general_content;
		obj.load_func = "loadContent";
		loadContent(obj);
	});
}

function processContent(content, num) {
	if (typeof(content.content) !== 'undefined') {	//comment
		return {tag:"table", "class":"box", children:[
			{tag:"tr", children:[
				{tag:"td", children:"Comment"},
				{tag:"td", children:[
					{tag:"input", id:num, style:"float:right;", 
						tp:"comment", type:"button", value:"delete"
					},
					{tag:"input", id:num, style:"float:right;",
						tp:"comment", type:"button", value:"save"
					}
				]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"Author:"},
				{tag:"td", children:content.author}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"Created On:"},
				{tag:"td", children:content.created}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"User Comment:"},
				{tag:"td", children:
					{tag:"textarea", id:"content:"+num+":content", "class":"property", 
						rows:6, cols:30, children:content.content}
				}
			]}
		]};
	} else {										//file_upload
		return {tag:"table", "class":"box", children:[
			{tag:"tr", children:[
				{tag:"td", children:"File Upload"},
				{tag:"td", children:[
					{tag:"input", id:num, style:"float:right;", 
						tp:"file", type:"button", value:"delete"
					},
					{tag:"input", id:num, style:"float:right;",
						tp:"file", type:"button", value:"save"
					}
				]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"File Name:"},
				{tag:"td", children:{tag:"a", href:"file-download.php?id="+content.id,
					children:content.file_name
				}}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"File Author:"},
				{tag:"td", children:content.author}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"Created On:"},
				{tag:"td", children:content.file_created}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"User Comment:"},
				{tag:"td", children:
					{tag:"textarea", id:"content:"+num+":file_comment", "class":"property",
						rows:4, cols:30, children:content.file_comment}
				}
			]}
		]};
	}
}

function loadContent(general_content) {
	object = general_content.content;
	//wipe and replace content div
	var content = $("#content");
	content.html("");
	content.append("<h2>Content</h2><br/>");
	for (var i = 0; i < object.length; i++) {
		content.append(gen(processContent(object[i], i)));
	}
	if (!read_only) {
		content.append(gen({tag:"div", "class":"box", style:"padding:3px;", children:[
			{tag:"input", type:"button", id:"add", value:"Add"},
			{tag:"select", id:"type", children:[
				{tag:"option", value:"file", children:"New File"},
				{tag:"option", value:"comment", children:"New Comment"},
			]}
		]}));
	}
	$("#add").on("click", function() {
		if ($("#type").val() == 'comment') {
			addComment();
		} else {
			$(this).before(gen({tag:"input", id:"file", type:"file"}));
			$("#file").on("change", addFile);
			$("#add").remove();
			$("#type").remove();
		}
	});
	$("input[type=button][value=delete]").on("click", function() {
		var id = $(this).attr("id");
		var type = $(this).attr("tp");
		$.confirm("Are you sure?", "The following item will be deleted forever!",
			"Delete", "Cancel").accept(function() {
			deleteItem(id, type);
		});
	});
	$("input[type=button][value=save]").on("click", function() {
		submitItem($(this).attr("id"), $(this).attr("tp"));
	});
	initInputs();
}

function submitItem(id, type) {
	$(".submit_success").remove();
	$.ajax({method:"post", url:"content.php", dataType:"json", data:obj.content[id],
		statusCode:{
			200: function() {
				//verify it worked to user
				$("input[value=save][id="+id+"]").after(gen(
					{tag:"div","class":"submit_success",children:[
						{tag:"img", src:"resources/check.png"},
						"Changes Submitted"
					]}
				));
				//cut the modification
				var last = ":content";
				if (type == "file")
					last = ":file_comment";
				cutModif("content:"+id+last);
			},
			400: function() {
				
			}
		}
	});
}

function addComment() {
	$.ajax({method:"post", url:"content.php", dataType:"json",
		data:{type:"comment", id:obj.id}, statusCode:{
			200: function(data) {
				obj.content.push(data);
				loadContent(obj);
				if (hasState())
					saveState();
			},
			400: function() {
				//this shouldn't happen
			}
		}
	});
}

function addFile() {
	var data = new FormData();
	$("#file").before(gen({tag:"img", id:"spin", src:"resources/spin-wait.gif"}));
	$("#file").attr("disabled", true);
	data.append("file", $("#file").prop("files")[0]);
	data.append("type", "file");
	data.append("id", obj.id)
	$.ajax({method:"post", url:"content.php", dataType:"json", data:data,
		processData:false, contentType:false, statusCode:{
			200: function(data) {
				obj.content.push(data);
				loadContent(obj);
				if (hasState())
					saveState();
			},
			400: function() {
				//this shouldn't happen (but it does)
				$("#file").attr("disabled", false);
			}
		}
	}).done(function() {$("#spin").remove();});
}

function deleteItem(id, type) {
	$.ajax({method:"post", url:"content.php", dataType:"json",
		data:{"delete":obj.content[id].id, type:type},
		statusCode:{
			200: function() {
				obj.content.splice(id, 1);
				loadContent(obj);
				if (hasState())
					saveState();
			},
			400: function() {
				//probably shouldn't happen
			}
		}
	});
}

