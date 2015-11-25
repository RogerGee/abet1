/* functions for profile creation admin tool */
function loadUserCreate() {
	//wipe and replace content div
	var content = $("#content");
	content.html("");
	content.append("<h2>Create Profile</h2>");
	content.append(gen({tag:"table", children:[
		{tag:"tr", children:[
			{tag:"td", children:"Username"},
			{tag:"td", children:{tag:"input", type:"text", id:"username"}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Password"},
			{tag:"td", children:{tag:"input", type:"password", id:"passwd"}}
		]},
		{tag:"tr", children:[
			{tag:"td", children:"Role"},
			{tag:"td", children:{tag:"input", type:"text", id:"role"}}
		]}
	]}));
	content.append(gen({tag:"input", id:"submit", type:"button", value:"Create"}));
	$("#submit").on("click", createUser);
}

function createUser() {
	//submit the new user using ajax, and print status message
	$(".submit_success").remove();
	$(".submit_error").remove();
	var data = {
		username: $("#username").val(),
		passwd: $("#passwd").val(),
		role: $("#role").val()
	};
	$.ajax({method:"post",url:"usercreate.php",dataType:"json",data:data,
		statusCode:{
			200: function() {
				//reset page to empty and add success message
				loadUserCreate();
				$("#submit").after(gen(
					{tag:"p","class":"submit_success",children:"User Created"}
				));
			},
			400: function(data) {
				data = data.responseJSON;
				$("#"+data.errField).parent().after(gen(
					{tag:"td","class":"submit_error",children:data.error}
				));
			}
		}
	});
}

