/* functions for editing profile */
function getProfile() {
	$.ajax({url:"profile.php",dataType:"json"}).done(function(profile) {
		obj = profile;
		obj.load_func = "loadProfile";
		loadProfile(profile);
	});
}

function loadProfile(profile) {
	//wipe and replace content div
	var content = $("#content");
	content.html("");
	content.append("<h2>Edit Profile</h2>");
	content.append(gen({tag:"table", children:[
		{tag:"tr", children:[
			{tag:"td", children:["Username"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"username", "class":"property", value:profile.username}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["First Name"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"first_name", "class":"property", value:profile.first_name}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Middle Initial"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"middle_initial", "class":"property", value:profile.middle_initial}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Last Name"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"last_name", "class":"property", value:profile.last_name}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Suffix"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"suffix", "class":"property", value:profile.suffix}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Gender"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"gender", "class":"property", value:profile.gender}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Bio"]},
			{tag:"td", children:[{tag:"textarea", rows:5, cols:21, id:"bio", "class":"property", children:[profile.bio]}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Email"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"email_addr", "class":"property", value:profile.email_addr}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Office Phone"]},
			{tag:"td", children:[{tag:"input", type:"phone", id:"office_phone", "class":"property", value:profile.office_phone}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Mobile Phone"]},
			{tag:"td", children:[{tag:"input", type:"phone", id:"mobile_phone", "class":"property", value:profile.mobile_phone}]}
		]}
	]}));
	content.append(gen({tag:"input", id:"submit", type:"button", value:"Submit"}));
	content.append("<h2>Change Password</h2>");
	content.append(gen({tag:"table", children:[
		{tag:"tr", children:[
			{tag:"td", children:["old password"]},
			{tag:"td", children:[{tag:"input", type:"password", id:"old_pass"}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["new password"]},
			{tag:"td", children:[{tag:"input", type:"password", id:"new_pass1", disabled:"disabled"}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["retype new password"]},
			{tag:"td", children:[{tag:"input", type:"password", id:"new_pass2", disabled:"disabled"}]}
		]}
	]}));
	content.append(gen({tag:"input", id:"changepass", type:"button", value:"Change"}));
	$("#submit").on("click", submitProfile);
	//validate that the password matches the actual password
	$("#old_pass").on("change", checkPass);
	$("#old_pass").on("keydown", function(event) {if (event.which == 13) this.blur();});
	$("#changepass").on("click", changePass);
	initPhone();
	initInputs();
}

function submitProfile() {
	//submit back the profile using ajax, and print status message
	$(".submit_success").remove();
	$(".submit_error").remove();
	$.ajax({method:"post",url:"profile.php",dataType:"json",data:obj,
		statusCode:{
			200: function() {
				$("#submit").after(gen(
					{tag:"p","class":"submit_success",children:["Changes Submitted"]}
				));
				//scrub the cache, but only on successful submit
				clearState();
			},
			400: function(data) {
				data = data.responseJSON;
				$("#"+data.errField).parent().after(gen(
					{tag:"td","class":"submit_error",children:[data.error]}
				));
			},
		}
	});
}

function checkPass() {
	$(".submit_success").remove();
	$(".submit_error").remove();
	$.ajax({method:"post", url:"checkpass.php", dataType:"json",
		data:{pass:$(this).val()},
		statusCode:{
			200: function() {
				//password is correct, unlock the new password fields
				$("#new_pass1, #new_pass2").prop("disabled", false);
			},
			400: function() {
				//password doesn't match, disable fields and add message
				$("#new_pass1, #new_pass2").prop("disabled", true);
				$("#old_pass").parent().after(gen(
					{tag:"td","class":"submit_error",children:["incorrect password!"]}
				));
			}
		}
	});
}

function changePass() {
	$(".submit_success").remove();
	$(".submit_error").remove();
	if ($("#new_pass1").val() != $("#new_pass2").val()) {
		$("#new_pass2").parent().after(gen(
			{tag:"td","class":"submit_error",children:["passwords don't match!"]}
		));
	} else {
		$.ajax({method:"post", url:"changepass.php", dataType:"json",
			data:{old_pass:$("#old_pass").val(), new_pass:$("#new_pass").val()},
			statusCode:{
				200: function() {
					$("#changepass").after(gen(
						{tag:"p","class":"submit_success",children:["Password Changed"]}
					));
				},
				400: function(data) {
					data = data.responseJSON;
					$("#"+data.errField).parent().after(gen(
						{tag:"td","class":"submit_error",children:[data.error]}
					));
				}
			}
		});
	}
}

