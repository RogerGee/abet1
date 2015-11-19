<?php

require_once 'abet1-query.php';

// initialize the PHP session
session_start();

// abet_is_authenticated() - returns true if the session is authenticated and
// the credentials exist in the database
function abet_is_authenticated() {
    if (array_key_exists('user',$_SESSION)
        && array_key_exists('passwd',$_SESSION))
    {
        // verify the credentials again
        if (!abet_verify($_SESSION['user'],$_SESSION['passwd'])) {
            // the session is bad so unset everything
            session_unset();
            return false;
        }

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
    // create a one-way hash of the password
    $encrypted = password_hash($passwd,PASSWORD_DEFAULT);
    unset($passwd);

    // place the username in the session so we can remember the login attempt
    $_SESSION['user'] = $user;

    // attempt authentication verification
    if (abet_verify($user,$encrypted,$id,$role)) {
        // authentication was successful: place encrypted password, id and
        // user role into session
        $_SESSION['passwd'] = $encrypted;
        $_SESSION['id'] = $id;
        $_SESSION['role'] = $role;
        return true;
    }

    return false;
}

// abet_verify() - verifies username and password pair; the password should be
// a one-way hash of the plain-text password; the database stores this encrypted
// password as a string
function abet_verify($user,$passwd,&$id,&$role) {
    $info = array(
        'tables' => array(
            'userauth' => array('passwd','role'),
            'userprofile' => array('id')
        ),
        'joins' => array(
            'INNER JOIN userprofile ON userauth.id = userprofile.fk_userauth'
        ),
        'where' => "userprofile.username = '$user'"
    );

    $result = (new Query(new QueryBuilder(SELECT_QUERY,$info)))->get_row_assoc(1);
    if (!is_null($result) && $result['passwd'] === $passwd) {
        $id = $result['id'];
        $role = $result['role'];
        return true;
    }

    return false;
}
