/* functions for editing rubrics */
function getRubric(id) {
	$.ajax({url:"rubric.php?id="+id, dataType:"json"}).done(function(rubric) {
		obj = rubric;
		obj.id = id;
		obj.load_func = "loadRubric";
		loadRubric(rubric);
	});
}

function loadRubric(rubric) {
	/*	draft of rubric JSON
		{
			//from rubric
			name:"",
			threshold:<number>,
			threshold_desc:"",
			//from rubric_description
			outstanding_desc:"",
			expected_desc:"",
			marginal_desc:"",
			unacceptable_desc:"",
			//from rubric_results
			total_students:<number>,
			//from competencyXcompetency_results
			competency:[
				{
					description:"",
					outstanding_tally:<number>,
					expected_tally:<number>,
					marginal_tally:<number>,
					unacceptable_tally:<number>,
					pass_fail_type:<bool>,
					comment:""
				}
			],
		}
	*/
	//wipe and replace content div
	var content = $("#content");
	content.html("");
	content.append("<h2>" + rubric.name + "</h2>");
	content.append(gen({tag:"table", children:[
		{tag:"tr", children:[
			{tag:"td", children:"Unacceptable Description:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"input", 
				id:"unacceptable_desc", value:rubric.unacceptable_desc
			}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Marginal Description:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"input", 
				id:"marginal_desc", value:rubric.marginal_desc
			}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Expected Description:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"input", 
				id:"expected_desc", value:rubric.expected_desc
			}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Outstanding Description:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"input", 
				id:"outstanding_desc", value:rubric.outstanding_desc
			}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Threshold:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"number", 
				min:0, max:1, step:.01, id:"threshold", value:rubric.threshold
			}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Threshold Description:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"input", 
				id:"threshold_desc", value:rubric.threshold_desc
			}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Students:"},
			{tag:"td", children:{tag:"input", "class":"property", type:"number", 
				min:0, max:999, step:1, id:"total_students", value:rubric.total_students
			}}
		]}
	]}));
	var table = {tag:"table", "class":"rubric", children:[
		{tag:"tr", children:[
			{tag:"th", children:"Component"},
			{tag:"th", children:"Unacceptable"},
			{tag:"th", children:"Marginal"},
			{tag:"th", children:"Expected"},
			{tag:"th", children:"Outstanding"},
			{tag:"th", children:"Delete"}
		]}
	]};
	for (i = 0; i < rubric.competency.length; i++) {
		var a = rubric.competency[i].unacceptable_tally / rubric.total_students;
		var b = rubric.competency[i].marginal_tally / rubric.total_students;
		var c = rubric.competency[i].expected_tally / rubric.total_students;
		var d = rubric.competency[i].outstanding_tally / rubric.total_students;
		table.children.push({tag:"tr", children:[
			{tag:"td", children:{tag:"textarea", "class":"property", rows:4, cols:30,
				id:"competency:"+i+":description",
				children:rubric.competency[i].description
			}},
			{tag:"td", style:"width:6em;", children:[{tag:"h2", children:a+"%"},
				{tag:"input", type:"number", "class":"property", style:"width:4em;",
					id:"competency:"+i+":unacceptable_tally", row:i,
					value:rubric.competency[i].unacceptable_tally
				}
			]},
			{tag:"td", style:"width:6em;", children:[{tag:"h2", children:b+"%"},
				{tag:"input", type:"number", "class":"property", style:"width:4em;",
					id:"competency:"+i+":marginal_tally", row:i,
					value:rubric.competency[i].marginal_tally
				}
			]},
			{tag:"td", style:"width:6em;", children:[{tag:"h2", children:c+"%"},
				{tag:"input", type:"number", "class":"property", style:"width:4em;",
					id:"competency:"+i+":expected_tally", row:i,
					value:rubric.competency[i].expected_tally
				}
			]},
			{tag:"td", style:"width:6em;", children:[{tag:"h2", children:a+"%"},
				{tag:"input", type:"number", "class":"property", style:"width:4em;",
					id:"competency:"+i+":outstanding_tally", row:i,
					value:rubric.competency[i].outstanding_tally
				}
			]},
			{tag:"td", children:{tag:"input", id:i, type:"button", value:"delete"}}
		]});
	}
	table.children.push({tag:"tr", children:
		{tag:"th", colspan:6, children:"<input type='button' value='Add Row'/>"}
	});
	content.append(gen(table));
	content.append("<input type='button' id='submit' value='Submit'/>");
	$("#total_students").on("keyup mouseup", function() {
		$(".rubric input[type=number]").trigger("input");
	});
	$(".rubric input[type=number]").on("input", function() {
		if ($(this).val() < 0)
			$(this).val(0);
		var total = 0;
		$("input[row="+$(this).attr("row")+"]").each(function() {
			total += parseInt($(this).val());
		});
		if (total > obj.total_students) {
			total -= $(this).val();
			$(this).val(obj.total_students - total);
		}
		var x = $(this).val() / obj.total_students * 100;
		x = +x.toFixed(2);
		$(this).parent().children().html(x + "%");
	});
	$("input[type=button][value=delete]").on("click", function() {
		$.confirm("Are you sure?", "The following item will be deleted forever!",
			"Delete", "Cancel").accept(function() {
			deleteRow(this.id);
		});
	});
	$("input[type=button][value='Add Row']").on("click", addRow)
	$("#submit").on("click", submitRubric);
	initInputs();
}

function submitRubric() {
	$.ajax({method:"post", url:"rubric.php", dataType:"json", data:obj,
		statusCode:{
			200: function() {
				//verify it worked to user
				
				//scrub cache
				clearState();
			},
			400: function() {
				
			}
		}
	});
}

function addRow() {
	$.ajax({method:"post", url:"rubric.php", dataType:"json",
		data:{add:"row", id:obj.id}, statusCode: {
			200: function(data) {
				obj.competency.push(data);
				loadRubric(obj);
				if (hasState())
					saveState();
			},
			400: function() {
				//this shouldn't happen
			}
		}
	});
}

function deleteRow(id) {
	$.ajax({method:"post", url:"rubric.php", dataType:"json",
		data:{"delete":obj.competency[id].id},
		statusCode:{
			200: function() {
				obj.content.splice(id, 1);
				loadRubric(obj);
				if (hasState())
					saveState();
			},
			400: function() {
				//probably shouldn't happen
			}
		}
	});
}

