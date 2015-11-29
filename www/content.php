<?php

// include needed files; update the include path to find the libraries
$paths = array(
    get_include_path(),
    '/usr/lib/abet1',
    '/usr/local/lib/abet1'
);
set_include_path(implode(PATH_SEPARATOR,$paths));
require_once 'abet1-login.php';
require_once 'abet1-query.php';
require_once 'abet1-misc.php';

/* content.php - JSON transfer specification
    Supports: GET/POST

    Fields: GET
    *----*
    | id |
    *----*

    Fields: POST
     - if new content creation: (type = comment|file)
     *---------*
     | id type |
     *---------*
     - if delete content:
     *-------------*
     | delete type |
     *-------------*
     - if update content:
     *---------*           *-----------------*      *------------*
     | content |  array of | id file_comment |  OR  | id content |
     *---------*           *-----------------*      *------------*

    This script creates, edits and retrieves general content objects. The GET
    request is used to retrieve an initial set of general content items (file upload
    references and user comments) given an id for a general_content entity:

        - the script will return an array of general content items:
            - if file then will have following fields:
                *-----------------------------------------------*
                | id file_name author file_comment file_created |
                *-----------------------------------------------*
            - if comment then will have following fields:
                *---------------------------*
                | id author content created |
                *---------------------------*

    The POST operation handles either A) creation of files, B) creation of
    comments, C) updating content or D) deletion of content:

        A) create new file upload
            - client sends id/type pair (type=file); we get file data and name from
            $_FILES/filesystem; the id is the general_content entity id
            - server creates file upload
            - server sends back object with the following fields:
                id, file_name, file_comment (empty), file_created, author (string)

        B) create new comment
            - client sends id/type pair (type=comment)
            - server creates user comment
            - server sends back object with the following fields:
                id, author, content (empty), created

        C) update content
            - client sends array of objects with the following fields:
                - if file upload:
                    id, file_comment
                - if user comment:
                    id, content
            - server just updates comments for file_upload/user_comment
            - server returns {"success":true} if action
                - {"success":false} otherwise with status code 500

        D) delete content
            - client sends a single id/type pair
            - server deletes single entity
            - server returns {"success":true} if action
                - {"success":false} otherwise with status code 500
*/

$DATETIME_FORMAT = "%M %D, %Y - %l:%i %p";
header('Content-Type: application/json');

// get content set given general_content entity id
function get_content($gcId) {
    global $DATETIME_FORMAT;
    $content = array();

    // select comments and file uploads separately
    $comments = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'user_comment' => array('id','content'),
            1 => array(
                "DATE_FORMAT(user_comment.created,'$DATETIME_FORMAT') created",
                "UNIX_TIMESTAMP(user_comment.created) unix_time"
            ),
            'userprofile' => array('first_name','last_name')
        ),
        'joins' => array(
            'INNER JOIN userprofile ON userprofile.id = user_comment.fk_author'
        ),
        'where' => "fk_content_set = ?",
        'where-params' => array("i:$gcId")
    )));
    $uploads = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'file_upload' => array('id','file_name','file_comment'),
            1 => array(
                "DATE_FORMAT(file_created,'$DATETIME_FORMAT') file_created",
                "UNIX_TIMESTAMP(file_created) unix_time"
            ),
            'userprofile' => array('first_name','last_name')
        ),
        'joins' => array(
            'INNER JOIN userprofile ON userprofile.id = file_upload.fk_author'
        ),
        'where' => "fk_content_set = ?",
        'where-params' => array("i:$gcId")
    )));

    // create closure for adding row to content set
    $addRow = function($row) use(&$content) {
        $row['author'] = "$row[first_name] $row[last_name]";
        unset($row['first_name']);
        unset($row['last_name']);
        $content[] = $row;
    };

    // grab content items and sort them by create time
    $comments->for_each_assoc($addRow);
    $uploads->for_each_assoc($addRow);
    usort($content,function($a,$b){return $a['unix_time'] - $b['unix_time'];});

    return json_encode($content);
}

// creates a new comment for the given general_content entity
function create_comment($gcId) {
    // perform the update/select operations within a transaction
    list($code,$message) = Query::perform_transaction(function(&$rollback) use($gcId){
        global $DATETIME_FORMAT;
        // insert new row into user_comment table
        $insert = new Query(new QueryBuilder(INSERT_QUERY,array(
            'table' => 'user_comment',
            'fields' => array(
                'content', 'fk_author', 'fk_content_set', 'created'
            ),
            'values' => array(
                // insert empty content, current user, specified general_content id,
                // and the current timestamp:
                array("s:", "i:$_SESSION[id]", "i:$gcId", "l:NOW()")
            )
        )));
        if (!$insert->validate_update()) {
            $rollback = true;
            return array(SERVER_ERROR,"an insertion operation failed");
        }

        // now select the newly created row from the DB, along with some other info about the user
        $row = (new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'user_comment' => array('id','content'),
                // note: userprofile also has a created field
                1 => "DATE_FORMAT(user_comment.created,'$DATETIME_FORMAT') created",
                'userprofile' => array('first_name','last_name')
            ),
            'joins' => 'INNER JOIN userprofile ON userprofile.id = user_comment.fk_author',
            'where' => 'user_comment.id = LAST_INSERT_ID()'
        ))))->get_row_assoc();
        if (is_null($row)) {
            $rollback = true;
            return array(SERVER_ERROR,"could not retrieve inserted row");
        }

        // format the data for the client
        $entity = new stdClass;
        $entity->id = $row['id'];
        $entity->author = "$row[first_name] $row[last_name]";
        $entity->content = $row['content'];
        $entity->created = $row['created'];

        return array(OKAY,json_encode($entity));
    });

    if ($code != OKAY) {
        page_fail_with_reason($code,$message);
    }

    return $message;
}

// creates a new file upload for the given general_content given file upload
// info in $_FILES
function create_file($gcId) {
    // perform update/select operations within a transaction
    list($code,$message) = Query::perform_transaction(function(&$rollback) use($gcId){
        global $DATETIME_FORMAT;
        // create new file_upload entity
        $insert = new Query(new QueryBuilder(INSERT_QUERY,array(
            'table' => 'file_upload',
            'fields' => array(
                'file_name', 'file_contents', 'file_comment', 'file_created',
                'fk_author', 'fk_content_set'
            ),
            'values' => array(
                array(
                    "s:{$_FILES['file']['name']}",
                    "l:LOAD_FILE('{$_FILES['file']['tmp_name']}')",
                    "s:",
                    "l:NOW()",
                    "i:$_SESSION[id]",
                    "i:$gcId"
                )
            )
        )));
        if (!$insert->validate_update()) {
            $rollback = true;
            return array(SERVER_ERROR,"failed to insert file_upload");
        }

        // select the newly created row from the DB, along with some info about the user
        $row = (new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'file_upload' => array('id','file_name','file_comment'),
                1 => "DATE_FORMAT(file_created,'$DATETIME_FORMAT') file_created",
                'userprofile' => array('first_name','last_name')
            ),
            'joins' => 'INNER JOIN userprofile ON userprofile.id = file_upload.fk_author',
            'where' => 'file_upload.id = LAST_INSERT_ID()'
        ))))->get_row_assoc();
        if (is_null($row)) {
            $rollback = true;
            return array(SERVER_ERROR,"could not retrieve inserted row");
        }

        // format the data for the client
        //   id, file_name, file_comment (empty), file_created, author (string)
        $entity = new stdClass;
        $entity->id = $row['id'];
        $entity->file_name = $row['file_name'];
        $entity->file_comment = $row['file_comment'];
        $entity->file_created = $row['file_created'];
        $entity->author = "$row[first_name] $row[last_name]";

        return array(OKAY,json_encode($entity));
    });

    if ($code != OKAY) {
        page_fail_with_reason($code,$message);
    }

    return $message;
}

// deletes an entity; kind must be either file_upload or user_comment
function delete_content($entityId,$entityKind) {
    $query = new Query(new QueryBuilder(DELETE_QUERY,array(
        'tables' => $entityKind,
        'where' => "$entityKind.id = ?",
        'where-params' => array("i:$entityId")
    )));

    if (!$query->validate_update()) {
        page_fail_with_reason(SERVER_ERROR,"content item deletion failed");
    }

    return "{\"success\":true}";
}

// updates a single entity based on kind (either file_upload or user_comment)
function update_content($entityKind,$updates) {
    $entityId = $updates['id'];
    unset($updates['id']); // remove so not in update line
    $query = new Query(new QueryBuilder(UPDATE_QUERY,array(
        'table' => $entityKind,
        'updates' => $updates,
        'where' => "$entityKind.id = ?",
        'where-params' => array("i:$entityId")
    )));
}

// check initial user authentication
if (!abet_is_authenticated()) {
    page_fail(UNAUTHORIZED);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!array_key_exists('id',$_GET))
        page_fail(BAD_REQUEST);

    // double check access to content
    if (!abet_is_admin_authenticated() && !check_assessment_access($_SESSION['id'],$_GET['id'],'general_content'))
        page_fail(UNAUTHORIZED);

    echo get_content($_GET['id']);
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (array_key_exists('id',$_POST) && array_key_exists('type',$_POST)) {
        // make sure user can access general_content entity
        if (!abet_is_admin_authenticated() && !check_assessment_access($_SESSION['id'],$_POST['id'],'general_content'))
            page_fail(UNAUTHORIZED);

        // create new content (single entity)
        if ($_POST['type'] == 'file' && array_key_exists('file',$_FILES)) {
            // make sure file data was uploaded correctly
            if (!is_uploaded_file($_FILES['file']['tmp_name']))
                page_fail_with_reason(SERVER_ERROR,"file upload was unsuccessful");

            // limit file size to 250mb (there probably is a better way of doing
            // this that doesn't require the file to actually finish uploading)
            if ($_FILES['file']['size'] > 262100000)
                page_fail_with_reason(SERVER_ERROR,"uploaded file is too large");

            echo create_file($_POST['id']);
        }
        else if ($_POST['type'] == 'comment') {
            echo create_comment($_POST['id']);
        }
        else
            page_fail(BAD_REQUEST);
    }
    else if (array_key_exists('delete',$_POST) && array_key_exists('type',$_POST)) {
        // delete content (single entity)
        if ($_POST['type'] != 'file' && $_POST['type'] != 'comment')
            page_fail(BAD_REQUEST);

        // verify that the user can access the entity
        $kind = $_POST['type'] == 'file' ? 'file_upload' : 'user_comment';
        if (!abet_is_admin_authenticated()
            && !check_general_content_item_access($_SESSION['id'],$_POST['id'],$kind))
        {
            page_fail(UNAUTHORIZED);
        }

        // delete the specified entity
        echo delete_content($_POST['id'],$kind);
    }
    else if (array_key_exists('content',$_POST)) {
        // update content (array of entities)
        foreach ($_POST['content'] as $content) {
            if (!array_key_exists('id',$content))
                page_fail(BAD_REQUEST);
            if (array_key_exists('file_comment',$content))
                $kind = 'file_upload';
            else if (array_key_exists('content',$content))
                $kind = 'user_comment';
            else
                page_fail(BAD_REQUEST);

            // verify that the user can access the entity
            if (!abet_is_admin_authenticated()
                && !check_general_content_item_access($_SESSION['id'],$content['id'],$kind))
            {
                page_fail(UNAUTHORIZED);
            }

            // for security's sake I create these manually
            $updates = array();
            $updates['id'] = $content['id'];
            if (array_key_exists('file_comment',$content))
                $updates['file_comment'] = "s:$content[file_comment]";
            else
                $updates['content'] = "s:$content[content]";
            update_content($kind,$updates);
        }
        echo "{\"success\":true}";
    }
    else
        page_fail(BAD_REQUEST);
}
else {
    page_fail(BAD_REQUEST);
}
