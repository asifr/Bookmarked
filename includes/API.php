<?php
$permalink = request_path();
$url = explode('/',request_path());

// Register user
if ($REST->get_request_method() == 'POST' && token_validated('register_user')) {
	// 1. Check if the email address already exists
	// 		Return an error if the email address already exists
	// 2. Create a new user account if this is a new user
	// 3. Login the new user and set cookies
	$post = receive(array('email','password','realname'));
	if (required(array('email','password','realname'),$post)) {
		if ($User->email_exists($post['email']) === false) {
			$User->register($post['realname'],$post['email'],$post['password']);
			$success[] = 'Your account was successfully created please check your email for confirmation.';
		} else {
			$error[] = 'A user with this email already exists';
		}
	} else {
		$error[] = 'A required field is missing';
	}
}

// Login user
if ($REST->get_request_method() == 'POST' && token_validated('login_user')) {
	$post = receive(array('email','password'));
	if (required(array('email','password'),$post)) {
		if ($User->attempt_login($post['email'], $post['password']) === false) {
			$error[] = 'The email/password combo was incorrect.';
		}
	} else {
		$error[] = 'A required field is missing';
	}
}

?>