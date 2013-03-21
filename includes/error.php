<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<title>Error</title>
</head>
<body>
	<h1>Sorry! The page could not be loaded</h1>
<?php
	if (isset($message))
		echo '<p>'.$message.'</p>'."\n";
	else
		echo '<p>This is probably a temporary error. Just refresh the page and retry. If problem continues, please check back in 5-10 minutes.</p>'."\n";

	if ($num_args > 1) {
		if (defined('DEBUG')) {
			if (isset($file) && isset($line))
				echo '<p class="error_line">'.sprintf('The error occurred on line %1$s in %2$s', $line, $file).'</p>'."\n";
		}
	}
?>
</body>
</html>
