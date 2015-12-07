/* functions for editing worksheets */
function getWorksheet(id) {
	$.ajax({url:"worksheet.php?id="+id, dataType:"json"}).done(function(worksheet) {
		obj = worksheet;
		obj.id = id;
		obj.load_func = "loadWorksheet";
		loadWorksheet(obj);
	});
}

function loadWorksheet(worksheet) {
	/*
		{
			faculty:"",
			criterion:"",
			characteristic:"",
			activity:"", || course:"",
			objective:"",
			instrument:"",
			course_of_action:"",
		}
	*/
	var content = $("#content");
	content.html("");
	content.append("<h2>Assessment Worksheet</h2>");
	content.append(gen({tag:"ul", children:[
		{tag:"li", children:["Faculty Member(s) responsible<br/>", worksheet.faculty]},
		{tag:"li", children:["ABET Criterion to be assessing<br/>", worksheet.criterion]},
		{tag:"li", children:["Course or activity where measure is used<br/>",
			(worksheet.activity || worksheet.course)
		]},
		{tag:"li", children:["Objective or standard that interprets the Criterion<br/>",
			{tag:"textarea", id:"objective", "class":"property", cols:80, rows:4,
				children:worksheet.objective
			}
		]},
		{tag:"li", children:["The test of the instrument used to make the evaluation<br/>",
			{tag:"textarea", id:"instrument", "class":"property", cols:80, rows:4,
				children:worksheet.instrument
			}
		]},
		{tag:"li", children:["Course of Action<br/>",
			{tag:"textarea", id:"course_of_action", "class":"property", cols:80, rows:4,
				children:worksheet.course_of_action
			}
		]}
	]}));
	content.append("<input type='button' id='submit' value='Submit'/>");
	$("#submit").on("click", submitWorksheet);
	initInputs();
}

function submitWorksheet() {
	$(".submit_success").remove();
	$.ajax({method:"post", url:"worksheet.php", dataType:"json", data:obj,
		statusCode:{
			200: function() {
				//verify it worked to user
				$("#submit").after(gen(
					{tag:"p","class":"submit_success",children:"Changes Submitted"}
				));
				//scrub cache
				clearState();
			},
			400: function() {
				
			}
		}
	});
}

