/* course.js

    user object specification:
        courses: array of 'course' (ordered by title)
        profiles: array of 'profile' (ordered by last name)

    'course' specification:
        {
            title, course_number, coordinator, instructor, description,
            textbook, credit_hours
        }

    'profile' specification:
        {
            id, name
        }
*/

// admin tool for editing courses
function editCourses() {
    $.ajax({method:"GET",url:"course.php",dataType:"json"}).done(function(data){
        obj = data;

        // create temporary object and assign load function (as a string!)
        obj.title = null;
        obj.course_number = null;
        obj.coordinator = null;
        obj.instructor = null;
        obj.description = null;
        obj.textbook = null;
        obj.credit_hours = null;
        obj.load_func = "loadCourses";

        loadCourses(obj);
    });
}

// load and setup content div
function loadCourses(info) {
    var content = $("#content");
    content.html("");
    content.append("<h2>Edit Courses</h2>");

    // create drop down selections for credit hours
    var creditHoursOptions = [
        {tag:"option", value:"1", children:"1 hour"},
        {tag:"option", value:"2", children:"2 hours"},
        {tag:"option", value:"3", children:"3 hours"}
    ];

    // create drop down selections for user profiles
    var userProfilesOptions = [];
    info.profileIdMap = {};
    for (var p of info.profiles) {
        var item = {
            tag:"option",
            value:p.id,
            children:p.name
        };
        userProfilesOptions.push(item);
        info.profileIdMap[p.id] = p;
    }

    // create table for main control interface
    content.append(gen(
        {
            tag:"table",
            children:[
                {tag:"tr", children:[
                    {tag:"td",children:"Course Title"},
                    {tag:"td",children:{tag:"input", type:"text", id:"title", "class":"property"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td",children:"Course Number"},
                    {tag:"td",children:{tag:"input", type:"text", id:"course_number", "class":"property"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td", children:"Coordinator"},
                    {tag:"td", children:{tag:"select", id:"coordinator", "class":"property", children:userProfilesOptions}}
                ]},
                {tag:"tr", children:[
                    {tag:"td",children:"Instructor"},
                    {tag:"td",children:{tag:"input", type:"text", id:"instructor", "class":"property"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td", children:"Description"},
                    {tag:"td", children:{tag:"textarea", rows:5, cols:50, id:"description", "class":"property"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td",children:"Textbook"},
                    {tag:"td",children:{tag:"input", type:"text", id:"textbook", "class":"property"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td", children:"Credit Hours"},
                    {tag:"td", children:{tag:"select", id:"credit_hours", "class":"property", children:creditHoursOptions}},
                    {tag:"td", children:{tag:"input", id:"submit", type:"button", value:"Create"}},
                    {tag:"td", id:"response_message_insert", children:{tag:"input", id:"reset", type:"button", value:"Reset"}}
                ]}
            ]
        }
    ));

    // setup button event handlers
    $('#submit').on('click', function(){
        $(".submit_success").remove();
        $(".submit_error").remove();
        if ($(this).val() == 'Create') {
            // copy temporary object
            var cobj = {
                title:obj.title,
                course_number:obj.course_number,
                coordinator:obj.coordinator,
                instructor:obj.instructor,
                description:obj.description,
                textbook:obj.textbook,
                credit_hours:obj.credit_hours
            };

            $.ajax({method:"POST",url:"course.php",dataType:"json",data:cobj,
                statusCode:{
                    200:function(data) {
                        // find insertion point in selection list to keep
                        // the courses sorted by title
                        var index = 0, newcobjid;
                        while (index < obj.courses.length && obj.courses[index].title <= data.title)
                            ++index;
                        newcobjid = obj.courses.length;
                        obj.courses.push(data); // (we don't change the order of this array)

                        // insert new element
                        var newRow = createRow(data,newcobjid);
                        if (index < obj.courses.length-1)
                            $('#options_table tr:nth-child(' + (index+1) + ')').before(gen(newRow));
                        else
                            $('#options_table tr:nth-child(' + (obj.courses.length-1) + ')').after(gen(newRow));

                        // select new radio button object and change to edit mode
                        var radbut = $('tr[cobjid='+newcobjid+'] input[type=radio]');
                        radbut.prop('checked',true);
                        radbut.on('click',radioButtonClick);
                        $("#submit").val('Edit');
                        $("#response_message_insert").after(gen(
                            {tag:"p","class":"submit_success",children:"Course Created"}
                        ));

                        //scrub cache
                        clearState();
                    },
                    400:function(data) {
                        data = data.responseJSON;
                        if (typeof data.errField !== "undefined") {
                            $("#"+data.errField).parents('tr').after(gen(
                                {tag:"td","class":"submit_error",children:data.error}
                            ));
                        }
                        else {
                            $("#response_message_insert").after(gen(
                                {tag:"p","class":"submit_error",children:"Operation Failed"}
                            ));
                        }
                    }
                }
            }); // end $.ajax call
        }
        else { // edit mode
            // grab index of course in global user object list
            var cobjid = $('input[type=radio]:checked').parents('tr').attr('cobjid');
            if (typeof cobjid != 'undefined') {
                // copy properties from temp object to actual object
                var cobj = obj.courses[cobjid];
                cobj.title = obj.title;
                cobj.course_number = obj.course_number;
                cobj.coordinator = obj.coordinator;
                cobj.instructor = obj.instructor;
                cobj.description = obj.description;
                cobj.textbook = obj.textbook;
                cobj.credit_hours = obj.credit_hours;

                $.ajax({method:"POST",url:"course.php",dataType:"json",data:cobj,
                    statusCode:{
                        200:function() {
                            $("#response_message_insert").after(gen(
                                {tag:"p","class":"submit_success",children:"Changes Submitted"}
                            ));

                            // update entry in DOM
                            var row = $('tr[cobjid='+cobjid+']');
                            row.attr('key',cobj.title);
                            row.find('.cr_entry_title').html(cobj.title);
                            row.find('.cr_entry_course_number').html(cobj.course_number);
                            row.find('.cr_entry_coordinator').html(cobj.coordinator);
                            row.find('.cr_entry_instructor').html(cobj.instructor);
                            row.find('.cr_entry_description').html(cobj.description);
                            row.find('.cr_entry_textbook').html(cobj.textbook);
                            row.find('.cr_entry_credit_hours').html(cobj.credit_hours);

                            // move the element into the correct position based on
                            // its (possibly) new level
                            var index = 0;
                            var rows = $('tr[cobjid]');
                            while (index < obj.courses.length && $(rows[index]).attr('key') <= cobj.title)
                                ++index;
                            if (index >= obj.courses.length)
                                $(rows[index-1]).after($('tr[cobjid='+cobjid+']'));
                            else
                                $(rows[index]).before($('tr[cobjid='+cobjid+']'));

                            //scrub cache
                            clearState();
                        },
                        400:function(data) {
                            data = data.responseJSON;
                            if (typeof data.errField !== "undefined") {
                                $("#"+data.errField).parents('tr').after(gen(
                                    {tag:"td","class":"submit_error",children:data.error}
                                ));
                            }
                            else {
                                $("#response_message_insert").after(gen(
                                    {tag:"p","class":"submit_error",children:"Operation Failed"}
                                ));
                            }
                        }
                    }
                }); // end $.ajax call
            }
        }
    });
    $('#reset').on('click', function(){
        $(".submit_success").remove();
        $(".submit_error").remove();
        if (hasState()) {
            $.confirm(
                'Unsubmitted Work',
                'You have left changes in the form. Reset anyway?',
                'Yes',
                'No' ).accept(resetControls);
        }
        else
            resetControls();
    });

    content.append("<hr/>");

    // create list of courses with selection buttons next to each row
    var createRow = function(cobj,index){
        var cname;
        if (typeof cobj.coordinator === 'undefined')
            cname = "&lt;undefined&gt;";
        else
            cname = info.profileIdMap[cobj.coordinator].name;
        var item = {
            tag:"tr",
            key:cobj.title,
            cobjid:index,
            children:[
                {tag:"td", children:{tag:"input", type:"radio", name:"crsel"}},
                {tag:"td", "class":"cr_entry_title", children:cobj.title},
                {tag:"td", "class":"cr_entry_course_number", children:cobj.course_number},
                {tag:"td", "class":"cr_entry_coordinator", children:cname},
                {tag:"td", "class":"cr_entry_instructor", children:cobj.instructor},
                {tag:"td", "class":"cr_entry_description", children:cobj.description},
                {tag:"td", "class":"cr_entry_textbook", children:cobj.textbook},
                {tag:"td", "class":"cr_entry_credit_hours", children:cobj.credit_hours},
            ]
        };

        return item;
    };
    var rows = {tag:"table",id:"options_table",cellspacing:'10',children:[]};
    var index = 0;
    rows.children.push({tag:"tr","class":"course_heading",children:[
        {tag:"td",children:""},
        {tag:"td",children:"Title"},
        {tag:"td",children:"Number"},
        {tag:"td",children:"Coordinator"},
        {tag:"td",children:"Instructor"},
        {tag:"td",children:"Description"},
        {tag:"td",children:"Textbook"},
        {tag:"td",children:"Credits Hours"}
    ]});
    for (var c of info.courses)
        rows.children.push( createRow(c,index++) );
    content.append(gen(rows));

    var resetControls = function(){
        $('#submit').val('Create');
        $('#title').val('');
        $('#course_number').val('');
        $('#coordinator').val('');
        $('#instructor').val('');
        $('#description').val('');
        $('#textbook').val('');
        $('#credit_hours').val('');
        $('input[type=radio]').attr('checked',false);
    };

    var radioButtonClick = function(){
        // TODO: detect when object is not saved and prevent user from switching
        var cobj = obj.courses[$(this).parents('tr').attr('cobjid')];

        // setup temp object
        obj.title = cobj.title;
        obj.course_number = cobj.course_number;
        obj.coordinator = cobj.coordinator;
        obj.instructor = cobj.instructor;
        obj.description = cobj.description;
        obj.textbook = cobj.textbook;
        obj.credit_hours = cobj.credit_hours;

        // populate control interface with characteristic data
        $('#title').val(cobj.title);
        $('#course_number').val(cobj.course_number);
        $('#coordinator').val(cobj.coordinator);
        $('#instructor').val(cobj.instructor);
        $('#description').val(cobj.description);
        $('#textbook').val(cobj.textbook);
        $('#credit_hours').val(cobj.credit_hours);

        $('#submit').val('Edit');
        return true;
    };
    $('input[type=radio]').on('click',radioButtonClick);

    resetControls();
    initInputs(); // setup automated update for global object
}
