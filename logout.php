<?php
// Logout and redirect to homepage
if (isset($_GET['token']) && $_GET['token'] == $csrf_token) {
	$csrf_token = set_csrf_token();
	$User->logout();
	redirect($base_url);
}
?>