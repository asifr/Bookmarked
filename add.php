<?php
$template = ''; // TEMPLATE
// Add bookmark
if ($permalink == 'add' && isset($_GET['url'])) {
	$data = array(
		'bookmark_url'		=> $_GET['url'],
		'bookmark_title'	=> $_GET['title'],
		'bookmark_date'		=> time(),
		'guid'				=> md5($_GET['url']),
		'bookmark_status'	=> 'toread',
		'bookmark_type'		=> 'website',
		'bookmark_tags'		=> ''
	);
	if ($User->is_loggedin) {
		// INSERT
		$data['bookmark_author'] = $User->info['ID'];
		$values = array();
		foreach ($data as $key => $value) {
			$values[':'.$key] = $value;
		}
		$q = $db->conn->prepare('INSERT INTO bm_bookmarks ('.implode(',',array_keys($data)).') VALUES ('.implode(',',array_keys($values)).')');
		$q->execute($values);
		$data['ID'] = $db->conn->lastInsertId();
		$template = 'saved';	// TEMPLATE
	} else {
		// Show login page
		$template = 'login';	// TEMPLATE
	}
}
?>

<?php if ($template == 'saved'): // display the SAVED text and close the window ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<title>Saved</title>
	<style type="text/css" media="screen">
		h1{display:block;text-align:center;color:#FF0080;font-size:48px;margin-top:40px;}
	</style>
	<script type="text/javascript" charset="utf-8">
		window.setTimeout(function(){
			window.close();
		},1000);
	</script>
</head>
<body>
	<h1>SAVED</h1>
</body>
</html>
<?php endif; ?>

<?php if ($template == 'login'): ?>
	<?php echo get_messages(); ?>
	<div id="login">
		<form action="" method="post" accept-charset="utf-8">
			<h4>Log in</h4>
			<input type="hidden" name="action" value="login_user">
			<input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
			<p><input type="text" name="email" value="" placeholder="Email" class="text"></p>
			<p><input type="password" name="password" value="" placeholder="Password" class="text"></p>
			<p><input type="submit" value="Login" class="btn"></p>
		</form>
	</div>
<?php endif; ?>