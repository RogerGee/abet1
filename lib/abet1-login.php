<?php

require_once 'abet1-query.php';

// initialize the PHP session
session_start();

// abet_is_authenticated() - returns true if the session is authenticated and
// the credentials exist in the database
function abet_is_authenticated() {
    if (array_key_exists('user',$_SESSION)
        && array_key_exists('role',$_SESSION)
        && array_key_exists('id',$_SESSION))
    {
        return true;
    }

    return false;
}

// abet_is_admin_authenticated() - determine if user is authenticated admin
function abet_is_admin_authenticated() {
    if (abet_is_authenticated()) {
        return $_SESSION['role'] == 'admin';
    }

    return false;
}

// abet_login() - perform login authentication with the specified
// user:passwd pair and save the session
function abet_login($user,$passwd) {
    // place the username in the session so we can remember the login attempt
    $_SESSION['user'] = $user;

    // attempt authentication verification
    if (abet_verify($user,$passwd,$id,$role)) {
        // authentication was successful: place id and user role into session
        $_SESSION['id'] = $id;
        $_SESSION['role'] = $role;
        return true;
    }

    return false;
}

// abet_verify() - verifies username and password pair; the password should be
// the plain-text password; the database stores the one-way hash (plus Salt) for
// the password as a string
function abet_verify($user,$passwd,&$id,&$role) {
    $info = array(
        'tables' => array(
            'userauth' => array('passwd','role'),
            'userprofile' => array('id')
        ),
        'joins' => array(
            'INNER JOIN userprofile ON userauth.id = userprofile.fk_userauth'
        ),
        'where' => 'userprofile.username = ?',
        'where-params' => array("s:$user")
    );

    // run query and verify password
    $result = (new Query(new QueryBuilder(SELECT_QUERY,$info)))->get_row_assoc();
    if (!is_null($result) && password_verify($passwd,$result['passwd'])) {
        $id = $result['id'];
        $role = $result['role'];
        return true;
    }

    return false;
}

// abet_change_password() - change the specified user's password to '$passwd'; the password
// must be the plain-text password; if 'alwaysUpdate' is false, then the new password
// is checked against the old and second oldest password in the database
function abet_change_password($user,$passwd,$alwaysUpdate = false) {
    return Query::perform_transaction(function(&$rollback) use($user,$passwd,$alwaysUpdate) {
        // grab old password and the userauth entity id
        $result = (new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'userauth' => array('id','passwd','old_passwd')
            ),
            'joins' => array(
                'INNER JOIN userprofile ON userauth.id = userprofile.fk_userauth'
            ),
            'where' => 'userprofile.username = ?',
            'where-params' => array("s:$user")
        ))))->get_row_assoc();

        if (is_null($result)) { // this shouldn't happen unless the account was deleted
            $rollback = true;
            return false;
        }

        // make sure new password is not the same as the old passwords
        if (!$alwaysUpdate && (password_verify($passwd,$result['passwd'])
            || password_verify($passwd,$result['old_passwd'])))
        {
            $rollback = true;
            return false;
        }

        // update password and old password
        $hash = password_hash($passwd,PASSWORD_DEFAULT);
        $query = new Query(new QueryBuilder(UPDATE_QUERY,array(
            'table' => 'userauth',
            'updates' => array(
                'passwd' => "s:$hash",
                'old_passwd' => "s:$result[passwd]"
            ),
            'where' => "id = $result[id]",
            'limit' => 1
        )));
        if (!$query->validate_update()) { // this shouldn't really happen
            $rollback = true;
            return false;
        }

        return true;
    });
}

// abet_update_username() - updates the session's username variable from the
// database; this should be called after the database has been updated
function abet_update_username() {
    if (abet_is_authenticated()) {
        // query username for user id
        $userName = (new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'userprofile' => 'username'
            ),
            'where' => "userprofile.id = $_SESSION[id]"
        ))))->get_row_ordered()[0];

        $_SESSION['username'] = $userName;
    }
}
