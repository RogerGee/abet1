/* functions for editing assessments */
function editAssessment(id) {
	$.ajax({url:"assessment.php?id="+id, dataType:"json"}).done(function(assessment) {
		obj = assessment;
		obj.id = id;
		obj.load_func = "loadAssessment";
		loadAssessment(assessment);
	});
}

function createAssessment(id) {
	//id in this case is a fusion of program and criterion ids
	var ids = id.split(":");
	$.ajax({url:"assessment.php?pid="+ids[0]+"&cid="+ids[1], dataType:"json"}).done(
		function(assessment) {
		obj = assessment;
		obj.load_func = "loadAssessment";
		loadAssessment(assessment);
	});
}

function setAssigned() {
	var lookup = {};
	$("#acl").html("");
	for (var i = 0; i < obj.profiles.length; i++)
		lookup[obj.profiles[i].id] = i;
	for (var i = 0; i < obj.acl.length; i++) {
		var p = obj.profiles[lookup[obj.acl[i]]];
		$("#acl").append(p.name + "<br/>");
	}
}

function loadAssessment(assessment) {
	/*	draft of JSON
		{
			name:"",
			characteristics:[
				{
					id:"",
					name:""
				} //, ...
			],
			characteristic:4,
			profiles:[
				{
					id:12,
					name:""
				} //, ...
			],
			acl:[5, 6, 7],
			has_content:false,
			worksheets:[
				{
					id:3,
					name:""
				} //, ...
			]
		}
	*/
	//wipe and replace content div
	var content = $("#content");
	content.html("");
	content.append("<h2>Assessment</h2>");
	var table = {tag:"table", children:[
		{tag:"tr", children:[
			{tag:"td", children:"Name:"},
			{tag:"td", children:{tag:"input", type:"text", "class":"property", id:"name", value:assessment.name}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Characteristic:"},
			{tag:"td", children:{tag:"select", "class":"property", id:"characteristic", children:[]}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Assigned to:"},
			{tag:"td", style:"width:12em;", children:{tag:"div", id:"acl"}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:{tag:"select", id:"profiles", children:[]}},
			{tag:"td", children:[
				{tag:"input", type:"button", id:"acl_add", value:"Add"},
				{tag:"input", type:"button", id:"acl_remove", value:"Delete"}
			]}
		]},
	]};
	for (var i = 0; i < assessment.characteristics.length; i++) {
		var c = assessment.characteristics[i];
		var opt = {tag:"option", value:c.id, children:c.name};
		if (c.id == assessment.characteristic)
			opt.selected = true;
		table.children[1].children[1].children.children.push(opt);
	}
	for (var i = 0; i < assessment.profiles.length; i++) {
		var p = assessment.profiles[i];
		var opt = {tag:"option", value:p.id, children:p.name};
		table.children[3].children[0].children.children.push(opt);
	}
	content.append(gen(table));
	content.append("<input type='button' id='submit' value='Submit'/>");
	for (var i = 0; i < assessment.worksheets.length; i++) {
		if (i == 0)
			content.append("<h2>Worksheets</h2>");
		var w = assessment.worksheets[i];
		content.append(gen({tag:"a", href:"getWorksheet", "class":"internal", id:w.id, children:w.name}));
		content.append("<br/>")
		if (i == assessment.worksheets.length - 1) {
			content.append(gen({tag:"input", type:"text", id:"activity"}));
			content.append(gen({tag:"input", type:"button", id:"create", value:"Create"}));
		}
	}
	$("#acl_add").on("click", function() {
		if (obj.acl.indexOf(parseInt($("#profiles").val())) == -1)
			obj.acl.push(parseInt($("#profiles").val()));
		setAssigned();
	});
	$("#acl_remove").on("click", function() {
		var lookup = {};
		for (var i = 0; i < obj.acl.length; i++)
			lookup[obj.acl[i]] = i;
		if (lookup[parseInt($("#profiles").val())] != null)
			obj.acl.splice(lookup[parseInt($("#profiles").val())], 1);
		setAssigned();
	});
	$("#submit").on("click", submitAssessment);
	$("#create").on("click", createWorksheet);
	setAssigned();
	initInputs();
	hijackAnchors();
}

function createWorksheet() {
	var a = $("#activity");
	$.ajax({url:"worksheet.php?id="+obj.id+"&activity="+encodeURI(a)}).done(function() {
		
	});
}

function submitAssessment() {
	$(".submit_success").remove();
	$.ajax({method:"post", url:"assessment.php", dataType:"json", data:obj,
		statusCode:{
			200: function() {
				//verify it worked to user
				$("#submit").after(gen(
					{tag:"p","class":"submit_success",children:[
						{tag:"img", src:"resources/check.png"},
						"Changes Submitted"
					]}
				));
				//scrub cache
				clearState();
			},
			400: function() {
				
			}
		}
	})
}

