//assuming profile object obtained is just the table columns from schema

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
	content.append(gen({tag:"table", children:[
		{tag:"tr", children:[
			{tag:"td", children:["Username"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"username", value:profile.username}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["First Name"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"first_name", value:profile.first_name}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Middle Initial"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"middle_initial", value:profile.middle_initial}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Last Name"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"last_name", value:profile.last_name}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Suffix"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"suffix", value:profile.suffix}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Gender"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"gender", value:profile.gender}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Bio"]},
			{tag:"td", children:[{tag:"textarea", id:"bio", children:[profile.bio]}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Email"]},
			{tag:"td", children:[{tag:"input", type:"text", id:"email_addr", value:profile.email_addr}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Office Phone"]},
			{tag:"td", children:[{tag:"input", type:"phone", id:"office_phone", value:profile.office_phone}]}
		]},
		{tag:"tr", children:[
			{tag:"td", children:["Mobile Phone"]},
			{tag:"td", children:[{tag:"input", type:"phone", id:"mobile_phone", value:profile.mobile_phone}]}
		]}
	]}));
	content.append(gen({tag:"input", id:"submit", type:"button", value:"Submit"}));
	$("#submit").on("click", submitProfile);
	initPhone();
}

function submitProfile() {
	//submit back the profile using ajax, and print status message
	$(".submit_success").remove();
	$(".submit_error").remove();
	$.ajax({method:"post",url:"profile.php",dataType:"json",data:obj,
		statusCode:{
			200: function() {
				$("#content").append(gen(
					{tag:"p","class":"submit_success",children:["success!"]}
				));
				//scrub the cache, but only on successful submit
				clearState();
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
