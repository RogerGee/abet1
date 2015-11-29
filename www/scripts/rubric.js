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
	content.append("<p>Unacceptable: " + rubric.unacceptable_desc + "</p>");
	content.append("<p>Marginal: " + rubric.marginal_desc + "</p>");
	content.append("<p>Expected: " + rubric.expected_desc + "</p>");
	content.append("<p>Outstanding: " + rubric.outstanding_desc + "</p>");
	content.append("<p><u>" + rubric.threshold_desc + "</u></p>");
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
		table.children.push({tag:"tr", children:[
			{tag:"td", children:{tag:"textarea", "class":"property", rows:4, cols:30,
				id:"competency:"+i+":description",
				children:rubric.competency[i].description
			}},
			{tag:"td", children:{tag:"input", type:"number", "class":"property",
				id:"competency:"+i+":unacceptable_tally", row:i,
				value:rubric.competency[i].unacceptable_tally
			}},
			{tag:"td", children:{tag:"input", type:"number", "class":"property",
				id:"competency:"+i+":marginal_tally", row:i,
				value:rubric.competency[i].marginal_tally
			}},
			{tag:"td", children:{tag:"input", type:"number", "class":"property",
				id:"competency:"+i+":expected_tally", row:i,
				value:rubric.competency[i].expected_tally
			}},
			{tag:"td", children:{tag:"input", type:"number", "class":"property",
				id:"competency:"+i+":outstanding_tally", row:i,
				value:rubric.competency[i].outstanding_tally
			}},
			{tag:"td", children:{tag:"input", id:i, type:"button", value:"delete"}}
		]});
	}
	table.children.push({tag:"tr", children:
		{tag:"th", colspan:6, children:"<input type='button' value='Add Row'/>"}
	});
	content.append(gen(table));
	content.append("<input type='button' id='submit' value='Submit'/>");
	$("input[type=number]").on("input", function() {
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
	});
	$("input[type=button][value=delete]").on("click", function() {
		deleteRow(this.id);
	});
	$("input[type=button][value='Add Row']").on("click", addRow)
	$("#submit").on("click", submitContent);
	initInputs();
}

function submitRubric() {
	$.ajax({method:"post", url:"rubric.php", dataType:"json", data:obj,
		statusCode:{
			200: function() {
				//verify it worked to user
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

