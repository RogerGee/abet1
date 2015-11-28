/* functions for editing assessments */
function getContent(id) {
	$.ajax({url:"content.php?id="+id,dataType:"json"}).done(function(general_content) {
		obj = {}
		obj.id = id;
		obj.content = general_content;
		obj.load_func = "loadContent";
		loadAssessment(obj);
	});
}

function processContent(content, num) {
	if (typeof(content.content) !== 'undefined') {	//comment
		return {tag:"table", style:"border: 1px solid black;", children:[
			{tag:"tr", children:[
				{tag:"td", children:"Comment"},
				{tag:"td", children:
					{tag:"input", id:num, style:"float:right;", 
						tp:"comment", type:"button", value:"delete"}
				}
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
		return {tag:"table", style:"border: 1px solid black;", children:[
			{tag:"tr", children:[
				{tag:"td", children:"File Upload"},
				{tag:"td", children:
					{tag:"input", id:num, style:"float:right;", 
						tp:"file", type:"button", value:"delete"}
				}
			]},
			{tag:"tr", children:[
				{tag:"td", children:"File Name:"},
				{tag:"td", children:content.file_name}
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
	content.append("<h2>Content</h2>");
	for (i = 0; i < object.length; i++) {
		content.append(gen(processContent(object[i], i)));
	}
	content.append(gen({tag:"input", type:"button", id:"add", value:"Add"}));
	content.append(gen({tag:"select", id:"type", children:[
			{tag:"option", value:"file", children:"New File"},
			{tag:"option", value:"comment", children:"New Comment"},
	]}));
	content.append("<br /><input type='button' id='submit' value='Submit'>");
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
		deleteItem(this.id, $(this).prop("id"));
	});
	initInputs();
}

function submitContent() {
	$.ajax({method:"post", url:"content.php", dataType:"json", data:obj,
		statusCode:{
			200: function() {
				//verify it worked to user
			},
			400: function() {
				
			}
		}
	});
}

function addComment() {
	$.ajax({method:"post", url:"content.php", dataType:"json",
		data:{type:"comment"},
		statusCode:{
			200: function(data) {
				obj.content.append(data);
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
	data.append("file", $("#file").prop("files")[0]);
	data.append("type", "file");
	$.ajax({method:"post", url:"content.php", dataType:"json", data:data,
		processData:false, contentType:false, statusCode:{
			200: function(data) {
				obj.content.append(data);
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
