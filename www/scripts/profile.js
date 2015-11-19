//assuming profile object obtained is just the table columns from schema

function getProfile() {
	$.ajax({url:"fake_profile.php",dataType:"json"}).done(function(profile) {
		//wipe and replace content div
		var content = $("#content");
		content.html("");
		content.append(gen({tag:"table", children:[
			{tag:"tr", children:[
				{tag:"td", children:["Username"]},
				{tag:"td", children:[{tag:"input", type:"text", id:"uname", value:profile.username}]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:["First Name"]},
				{tag:"td", children:[{tag:"input", type:"text", id:"fname", value:profile.first_name}]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:["Middle Initial"]},
				{tag:"td", children:[{tag:"input", type:"text", id:"mi", value:profile.middle_initial}]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:["Last Name"]},
				{tag:"td", children:[{tag:"input", type:"text", id:"lname", value:profile.last_name}]}
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
				{tag:"td", children:[{tag:"input", type:"text", id:"bio", value:profile.bio}]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:["Email"]},
				{tag:"td", children:[{tag:"input", type:"text", id:"email", value:profile.email_addr}]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:["Office Phone"]},
				{tag:"td", children:[{tag:"input", type:"tel", id:"ophone", value:profile.office_phone}]}
			]},
			{tag:"tr", children:[
				{tag:"td", children:["Mobile Phone"]},
				{tag:"td", children:[{tag:"input", type:"tel", id:"mphone", value:profile.mobile_phone}]}
			]}
		]}));
		content.append(gen({tag:"input", type:"button", value:"Submit"}));
		//return it so it can be cached
		return profile;
	});
}