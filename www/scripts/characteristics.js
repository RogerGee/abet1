/* characteristics.js - functionality for editing and creating ABET1
    characteristics

    Target backend scripts:
        - characteristic.php
            [GET] - retrieve all characteristics plus some metadata
            [POST] - retrieve newly created single characteristic (with no
                     specified id)
            [POST] - send edit for single characteristic (given specified id)

    User Object Format:
        chars: array of 'characteristic' (must be initially sorted)
        prog_spec: array of string
        level short_name description program_specifier (currently edited characteristic)

    'characteristic':
        id level short_name description program_specifier

    'new-characteristic':
        level short_name description program_specifier
*/

// ask the server for a list of characteristics; use them to populate the
// initial content view; the server also gives us a list of potential program
// specifier strings
function editCharacteristics() {
    $.ajax({method:"GET",url:"characteristics.php",dataType:"json"}).done(function(chars){
        obj = chars;
        obj.level = null;
        obj.short_name = null;
        obj.description = null;
        obj.program_specifier = null;
        obj.load_func = loadCharacteristics;
        loadCharacteristics(obj);
    });
}

// increments strings to generate organizational levels
function nextLevel(level) {
    if (level.length == 0)
        return 'a';

    var i = level.length - 1;
    var s = level.substr(0,i);
    if (level[i] < 'z')
        return s + String.fromCharCode(String.charCodeAt(level[i]) + 1);
    return nextLevel(s) + 'a';
}

function characteristicsRadioClick() {
    // TODO: detect when object is not saved and prevent user from switching
    var cobj = obj.chars[$(this).parents('tr').attr('cobjid')];

    // setup temp object
    obj.level = cobj.level;
    obj.short_name = cobj.short_name;
    obj.description = cobj.description;
    obj.program_specifier = cobj.program_specifier;

    // populate control interface with characteristic data
    $('#level').val(cobj.level);
    $('#short_name').val(cobj.short_name);
    $('#description').val(cobj.description);
    $('#program_specifier').val(cobj.program_specifier);
    $('#submit').val('Edit');

    return true;
}

// this function creates HTML content for editing/creating ABET characteristics,
// loading the specified content into the page
function loadCharacteristics(chars) {
    var content = $("#content");

    // wipe content div and set heading
    content.html("");
    content.append(
        "<h2>ABET Characteristics</h2><br/>" +
        "<p>Select a characteristic to edit from the list " +
        "or create a new characteristic. Characteristics " +
        "are seen by faculty when working on assessments " +
        "but only edited by you.</p>" );

    // create drop down content for characteristic level selection
    var levelSelect = [];
    for (var s = "a";s != "zz";s = nextLevel(s)) {
        var item = {tag:"option", value:s, children:s};
        levelSelect.push(item);
    }

    // create drop down content for program specifiers
    var progSpec = [{tag:"option", value:null, children:"&lt;empty&gt;"}];
    for (var s of chars.prog_spec) {
        var item = {tag:"option", value:s, children:s};
        progSpec.push(item);
    }

    // generate tabled interface for characteristic properties
    content.append(gen(
        {
            tag:"table",
            children: [
                {tag:"tr", children:[
                    {tag:"td", children:"Organizational Level"},
                    {tag:"td", children:{tag:"select", id:"level", "class":"property", children:levelSelect}}
                ]},
                {tag:"tr", children:[
                    {tag:"td", children:"Short Name"},
                    {tag:"td", children:{tag:"input", type:"text", id:"short_name", "class":"property"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td", children:"Description"},
                    {tag:"td", children:{tag:"textarea", rows:5, cols:50, "class":"property", id:"description"}}
                ]},
                {tag:"tr", children:[
                    {tag:"td", children:"Program Specifier"},
                    {tag:"td", children:{tag:"select", id:"program_specifier", "class":"property", children:progSpec}},
                    {tag:"td", children:{tag:"input", id:"submit", type:"button", value:"Create"}},
                    {tag:"td", id:"response_message_insert", children:{tag:"input", id:"reset", type:"button", value:"Reset"}}
                ]}
            ]
        }
    ));

    $('#level').val('');
    $('#short_name').val('');
    $('#description').val('');
    $('#program_specifier').val('');

    // setup button event handlers
    $('#submit').on('click', function(){
        $(".submit_success").remove();
        $(".submit_error").remove();
        if ($(this).val() == 'Create') {
            // submit created characteristic; don't include an id; this makes
            // the server create a new characteristic
            var cobj = {
                level:obj.level,
                short_name:obj.short_name,
                description:obj.description,
                program_specifier:obj.program_specifier
            };

            $.ajax({method:"POST",url:"characteristics.php",dataType:"json",data:cobj,
                statusCode:{
                    200:function(data) {
                        // insert the new characteristic into the array of characteristics
                        // and into the control interface at sorted position
                        var index = 0, newcobjid;
                        while (index < obj.chars.length && obj.chars[index].level <= data.level)
                            ++index;
                        newcobjid = obj.chars.length;
                        obj.chars.push(cobj); // (we don't change the order of this array)
                        var newRow = {
                            tag:"tr",
                            key:data.level,
                            cobjid:newcobjid,
                            children: [
                                {tag:"td", children:{tag:"input", type:"radio", name:"chsel"}},
                                {tag:"td", children:{tag:"b", "class":"ch_entry_level", children:data.level}},
                                {tag:"td", children:{tag:"i", "class":"ch_entry_short_name", children:data.short_name}},
                                {tag:"td", "class":"ch_entry_description", children:data.description}
                            ]
                        };
                        if (data.program_specifier != null) {
                            newRow.children.push(
                                {tag:"td", "class":"ch_entry_program_specifier",
                                    children:"["+data.program_specifier+"]"}
                            );
                        }
                        else
                            newRow.children.push({tag:"td","class":"ch_entry_program_specifier"});
                        $('#options_table tr:nth-child(' + (index+1) + ')').before(gen(newRow));

                        // select the new radio button entry and set its click handler
                        var radbut = $('tr[cobjid='+newcobjid+'] input[type=radio]');
                        radbut.prop('checked',true);
                        radbut.on('click',characteristicsRadioClick);

                        $("#submit").val('Edit');
                        $("#response_message_insert").after(gen(
                            {tag:"p","class":"submit_success",children:"Characteristic Created"}
                        ));
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
            });
        }
        else { // submit edit to existing characteristic
            var cobjid = $('input[type=radio]:checked').parents('tr').attr('cobjid');
            if (typeof cobjid != 'undefined') {
                // copy properties from temp object to actual object
                var cobj = obj.chars[cobjid];
                cobj.level = obj.level;
                cobj.short_name = obj.short_name;
                cobj.description = obj.description;
                cobj.program_specifier = obj.program_specifier;

                // post changes to server
                $.ajax({method:"POST",url:"characteristics.php",dataType:"json",data:cobj,
                    statusCode:{
                        200:function() {
                            $("#response_message_insert").after(gen(
                                {tag:"p","class":"submit_success",children:"Changes Submitted"}
                            ));

                            // update entry in DOM
                            var row = $('tr[cobjid='+cobjid+']');
                            row.attr('key',cobj.level);
                            row.find('.ch_entry_level').html(cobj.level);
                            row.find('.ch_entry_short_name').html(cobj.short_name);
                            row.find('.ch_entry_description').html(cobj.description);
                            if (cobj.program_specifier != null)
                                row.find('.ch_entry_program_specifier').html("["+cobj.program_specifier+"]");
                            else
                                row.find('.ch_entry_program_specifier').html("");

                            // move the element into the correct position based on
                            // its (possibly) new level
                            var index = 0;
                            var rows = $('tr[cobjid]');
                            while (index < rows.length && $(rows[index]).attr('key') <= cobj.level)
                                ++index;
                            if (index >= obj.chars.length)
                                $(rows[index-1]).after($('tr[cobjid='+cobjid+']'));
                            else
                                $(rows[index]).before($('tr[cobjid='+cobjid+']'));
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
                });
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
                'No' ).accept(function(){
                    $('#submit').val('Create');
                    $('#level').val('');
                    $('#short_name').val('');
                    $('#description').val('');
                    $('#program_specifier').val('');
                    $('input[type=radio]').attr('checked',false);
                });
        }
        else {
            $('#submit').val('Create');
            $('#level').val('');
            $('#short_name').val('');
            $('#description').val('');
            $('#program_specifier').val('');
            $('input[type=radio]').attr('checked',false);
        }
    });

    content.append("<hr/>");

    // show each characteristic next to a radio button
    var chrows = {tag:"table",id:"options_table",cellspacing:'10',children:[]};
    var index = 0;
    for (var c of chars.chars) {
        var item = {
            tag:"tr",
            key:c.level,
            cobjid:index,
            children: [
                {tag:"td", children:{tag:"input", type:"radio", name:"chsel"}},
                {tag:"td", children:{tag:"b", "class":"ch_entry_level", children:c.level}},
                {tag:"td", children:{tag:"i", "class":"ch_entry_short_name", children:c.short_name}},
                {tag:"td", "class":"ch_entry_description", children:c.description}
            ]
        };
        if (c.program_specifier !== null) {
            item.children.push(
                {tag:"td", "class":"ch_entry_program_specifier",
                    children:"["+c.program_specifier+"]"}
            );
        }
        else
            item.children.push({tag:"td","class":"ch_entry_program_specifier"});

        chrows.children.push(item);
        ++index;
    }

    // we require the server to sort initially; from this point on we just insert
    // new/edited elements into their latest possible position (for stability)
    // chrows.children.sort(function(a,b){
    //     return (a.key > b.key) - (a.key < b.key);
    // });

    content.append(gen(chrows));
    $('input[type=radio]').on('click',characteristicsRadioClick);

    // setup automated update for global object
    initInputs();
}
