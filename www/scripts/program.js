// program.js
// the program user object looks like this:
// {"id":1,"name":"Computer Science","abbrv":"CS","semester":"Fall","year":2012,
//      "description":"blah blah"}
// Notes: "semester" must be one of "Fall" or "Spring"

function programIncoming(program) {
    // the program object is already formatted by server
    obj = program;
    obj.load_func = "loadProgram";
    loadProgram(obj);
}

// retrieve a single program for edit specified by id (user clicked
// "Edit Program" in nav bar)
function editProgram(id) {
    // use AJAX to call GET on program.php with the id parameter; this will cause
    // the server to give us the program object specified by the id
    $.ajax({method:"GET",url:"program.php?id="+id,dataType:"json"}).done(programIncoming);
}

// create a new program for submission (user clicked "Create Program" in nav bar)
function createProgram(id) {
    // use AJAX to call GET on program.php with no parameters; this will cause
    // the server to get us a new program object
    $.ajax({method:"GET",url:"program.php"}).done(programIncoming);
}

// submits changes to create new program
function submitProgram() {
    $(".submit_success").remove();
    $(".submit_error").remove();
    $.ajax({method:"POST",url:"program.php",dataType:"json",data:obj,
        statusCode:{
            200: function() {
                $("#submit").after(gen(
                    {tag:"p","class":"submit_success",children:"Changes Submitted"}
                ));
                //scrub the cache, but only on successful submit
                clearState();
            },
            400: function(data) {
                // these shouldn't happen because of the drop downs (will just
                // happen if fields were left empty)
                if (typeof data.errField !== "undefined") {
                    data = data.responseJSON;
                    $("#"+data.errField).parent().after(gen(
                        {tag:"td","class":"submit_error",children:data.error}
                    ));
                }
            }
        }
    });
}

// loads the UI for editing a program
function loadProgram(program) {
    // grab content div and wipe it
    var content = $("#content");
    content.html("");
    content.append("<h2>Edit Program</h2>");

    // create drop down for semester
    var semesterDropDown = [
        {tag:"option", value:"Fall", children:"Fall Semester"},
        {tag:"option", value:"Spring", children:"Spring Semester"}
    ];
    if (program.semester !== null) {
        if (program.semester == "Fall")
            semesterDropDown[0].selected = "selected";
        else
            semesterDropDown[1].selected = "selected";
    }

    // create drop down for year
    var START_YEAR = 2010;
    var MAX_YEAR = 2100;
    var yearDropDown = [];
    for (var i = START_YEAR;i < MAX_YEAR;i++) {
        var item = {tag:"option", value:i, children:i.toString()};
        if (i == program.year)
            item.selected = "selected";
        yearDropDown.push(item);
    }

    // add user controls for editing a program
    content.append(gen(
        {
            tag: "table",
            children: [
                {tag: "tr", children:[
                    {tag: "td", children: "Program Name"},
                    {tag: "td", children:{tag:"input", type:"text", id:"name", "class":"property", value:program.name}}
                ]},
                {tag: "tr", children:[
                    {tag: "td", children: "Abbreviation"},
                    {tag: "td", children:{tag:"input", type:"text", id:"abbrv", "class":"property", value:program.abbrv}}
                ]},
                {tag: "tr", children:[
                    {tag: "td", children: "Semester"},
                    {tag: "td", children:{tag:"select", id:"semester", "class":"property", children:semesterDropDown}}
                ]},
                {tag: "tr", children:[
                    {tag: "td", children: "Year"},
                    {tag: "td", children: {tag:"select", id:"year", "class":"property", children:yearDropDown}}
                ]},
                {tag: "tr", children:[
                    {tag: "td", children: "Description"},
                    {tag: "td", children: {
                        tag:"textarea", rows:5, cols:21, id:"description", "class":"property", children:program.description
                    }}
                ]}
            ]
        }
    ));

    // add and setup submit button
    content.append(gen({tag:"input", id:"submit", type:"button", value:"Submit"}));
    $("#submit").on("click", submitProgram);
}
