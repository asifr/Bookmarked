<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<title>Bookmarked.in</title>
	<link rel="stylesheet" href="<?php echo $base_url; ?>normalize.css" type="text/css" media="screen" charset="utf-8">
	<link rel="stylesheet" href="<?php echo $base_url; ?>style.css" type="text/css" media="screen" charset="utf-8">
</head>
<body>

<?php if ($User->is_loggedin): ?>
<div class="container-fluid fill">
	<div class="row-fluid">
		<div class="span2">
			<nav>
				<ul>
					<li><a href="<?php echo $base_url.'prune'; ?>">Prune duplicates</a></li>
					<li><a href="<?php echo $base_url.'import'; ?>">Import</a></li>
					<li><a href="<?php echo $base_url.'export/?token='.$csrf_token; ?>">Export</a></li>
					<li><a href="<?php echo $base_url.'logout/?token='.$csrf_token; ?>">Logout</a></li>
				</ul>
			</nav>
			<h3>Bookmarklet</h3>
			<p><a class="bookmarklet" href="javascript:q=location.href;p=document.title;void(t=open('<?php echo $base_url; ?>add/?url='+encodeURIComponent(q)+'&title='+encodeURIComponent(p),'Bookmarked.in','toolbar=no,width=400,height=200'));t.blur();">save to bookmarked.in</a></p>
			<p>Drag the bookmarklet link to your bookmarks toolbar.</p>
		</div>
		<div class="span10 filler">
			<h3>Bookmarks</h3>
			<?php
			$bookmarks = $db->conn->query('SELECT * FROM bm_bookmarks WHERE bookmark_author='.$db->quote($User->info['ID']).' ORDER BY bookmark_date DESC')->fetchAll(PDO::FETCH_ASSOC);
			?>
			<ul>
			<?php foreach ($bookmarks as $bookmark): ?>
				<li><a href="<?php echo $bookmark['bookmark_url']; ?>" target="_blank"><?php echo $bookmark['bookmark_title']; ?></a></li>
			<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
<?php else: ?>
<div class="container-fluid fill">
	<div class="row-fluid">
		<div class="span12">
			<h1>Bookmarked.in</h1>
			<?php echo get_messages(); ?>
			<div id="login"<?php echo (isset($_POST['action']) && $_POST['action'] == 'login_user')?'':' style="display:none;"'; ?>>
				<form action="" method="post" accept-charset="utf-8">
					<h4>Log in</h4>
					<input type="hidden" name="action" value="login_user">
					<input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
					<p><input type="text" name="email" value="" placeholder="Email" class="text"></p>
					<p><input type="password" name="password" value="" placeholder="Password" class="text"></p>
					<p><input type="submit" value="Login" class="btn"> <a href="javascript:void(0);" onclick="javascript:document.getElementById('login').style.display = 'none';document.getElementById('register').style.display = 'block';" class="small">Create a free account</a></p>
				</form>
			</div>
			<div id="register"<?php echo (isset($_POST['action']) && $_POST['action'] == 'login_user')?' style="display:none;"':''; ?>>
				<form action="" method="post" accept-charset="utf-8">
					<h4>Create a free account</h4>
					<input type="hidden" name="action" value="register_user">
					<input type="hidden" name="token" value="<?php echo $csrf_token; ?>">
					<p><input type="text" name="realname" value="" placeholder="Name" class="text"></p>
					<p><input type="text" name="email" value="" placeholder="Email" class="text"></p>
					<p><input type="password" name="password" value="" placeholder="Password" class="text"></p>
					<p><input type="submit" value="Register" class="btn"> <a href="javascript:void(0);" onclick="javascript:document.getElementById('login').style.display = 'block';document.getElementById('register').style.display = 'none';" class="small">Login</a></p>
				</form>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
	
</body>
</html>