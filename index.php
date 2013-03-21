<?php
/*
Promenade
A lightweight PHP framework that promotes a RESTful API and OOP
Global variables and constants include $base_url, BASEPATH, and $config,
and $csrf_token

*/

define('START_MEM', memory_get_usage(true));

error_reporting(E_ALL);
date_default_timezone_set(@date_default_timezone_get());
if (!defined('BASEPATH'))
	define('BASEPATH', ((function_exists('realpath') && @realpath(dirname(__FILE__)) !== FALSE)?realpath(dirname(__FILE__)):basename(dirname(__FILE__))).'/');

// Reverse the effect of register_globals
unregister_globals();

// Ignore any user abort requests
ignore_user_abort(true);

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch') {
	header('HTTP/1.1 403 Prefetching Forbidden');
	// Send no-cache headers
	header('Expires: Fri, 29 May 1987 09:50:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache');		// For HTTP/1.0 compability
	exit();
}

// Don't load all of app when handling a favicon.ico request. Instead, send the headers for a zero-length favicon and exit.
if ('/favicon.ico' == $_SERVER['REQUEST_URI']) {
	header('Content-Type: image/vnd.microsoft.icon');
	header('Content-Length: 0');
	exit();
}

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

if (get_magic_quotes_runtime())
	@ini_set('magic_quotes_runtime', false);
if (get_magic_quotes_gpc()) {
	function stripslashes_array($array) {
		return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
	}
	$_GET = stripslashes_array($_GET);
	$_POST = stripslashes_array($_POST);
	$_COOKIE = stripslashes_array($_COOKIE);
}

// Strip out "bad" UTF-8 characters
remove_bad_characters();

// If the request_uri is invalid try to fix it
fix_request_uri();

// Generate the CSRF token
if (!isset($_SESSION['ready'])) {
	session_start();
	$_SESSION['ready'] = true;

	if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] != '')
		$csrf_token = $_SESSION['csrf_token'];
	else
		$csrf_token = set_csrf_token();
}

// Messages are stored in $error or $success
$error = array(); $success = array();
// Create session variables
foreach (array('error','success') as $m) {
	$_SESSION[$m] = (isset($_SESSION[$m]) && is_array($_SESSION[$m]) && !empty($_SESSION[$m]))?$_SESSION[$m]:array();
}

if (file_exists(BASEPATH.'includes/config.php'))
	include(BASEPATH.'includes/config.php');

// Creates a friendly URL slug from a string
function slugify($str, $seperator = '_')
{
	$str = str_replace(array(' ',',','.'), array($seperator,'',''), stripslashes(trim($str)));
	$str = preg_replace('/[^A-Za-z 0-9~%:_\-\/]/', '', trim($str));
	$str = preg_replace('/-+/', $seperator, trim($str));
	return trim(strip_all_tags($str));
}

function strip_all_tags($string, $remove_breaks = false)
{
	$string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
	$string = strip_tags($string);
	if ($remove_breaks)
		$string = preg_replace('/[\r\n\t ]+/', ' ', $string);
	return trim($string);
}

/**
 * Unset any variables instantiated as a result of register_globals being enabled
 */
function unregister_globals()
{
	$register_globals = @ini_get('register_globals');
	if ($register_globals === "" || $register_globals === "0" || strtolower($register_globals) === "off")
		return;
	// Prevent script.php?GLOBALS[foo]=bar
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
		exit;
	// Variables that shouldn't be unset
	$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
	// Remove elements in $GLOBALS that are present in any of the superglobals
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
	foreach ($input as $k => $v) {
		if (!in_array($k, $no_unset) && isset($GLOBALS[$k])) {
			unset($GLOBALS[$k]);
			unset($GLOBALS[$k]);	// Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
		}
	}
}

/**
 * Fix the REQUEST_URI if we can, since both IIS6 and IIS7 break it
 * 
 * @param boolean $sef sets search engine friendly urls
 */
function fix_request_uri($sef = true)
{
	if (defined('IGNORE_REQUEST_URI'))
		return;

	if (!isset($_SERVER['REQUEST_URI']) || (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['REQUEST_URI'], '?') === false)) {
		if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
			// Workaround for a bug in IIS7
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		} elseif (!$sef) {
			// IIS6 also doesn't set REQUEST_URI, If we are using the default SEF URL scheme then we can work around it
			$requested_page = str_replace(array('%26', '%3D', '%2F', '%3F'), array('&', '=', '/', '?'), rawurlencode($_SERVER['PHP_SELF']));
			$_SERVER['REQUEST_URI'] = $requested_page.(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');
		} else{
			// Otherwise I am not aware of a work around...
			die('The web server you are using is not correctly setting the REQUEST_URI variable. This usually means you are using IIS6, or an unpatched IIS7. Please either disable SEF URLs, upgrade to IIS7 and install any available patches or try a different web server.');
		}
	}
}

/**
 * Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
 */
function remove_bad_characters()
{
	global $bad_utf8_chars;
	$bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");
	function _remove_bad_characters($array) {
		global $bad_utf8_chars;
		return is_array($array) ? array_map('_remove_bad_characters', $array) : str_replace($bad_utf8_chars, '', $array);
	}
	$_GET = _remove_bad_characters($_GET);
	$_POST = _remove_bad_characters($_POST);
	$_COOKIE = _remove_bad_characters($_COOKIE);
	$_REQUEST = _remove_bad_characters($_REQUEST);
}

/**
* Sort an array given in $arr_data along the $str_column column
*/
function array_sort($arr_data, $str_column, $bln_desc=false)
{
	$arr_data = (array) $arr_data;
	if (count($arr_data)) {
		$str_column = (string) trim($str_column);
		$bln_desc = (bool) $bln_desc;
		$str_sort_type = ($bln_desc) ? SORT_DESC : SORT_ASC;
		foreach ($arr_data as $key => $row) {
			${$str_column}[$key] = $row[$str_column];
		}
		array_multisort($$str_column, $str_sort_type, $arr_data);
	}
	return $arr_data;
}

function base_paths()
{
	global $base_url, $base_path, $base_root;
	$is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
	$http_protocol = $is_https ? 'https' : 'http';
	$base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
	$base_url_guess = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);
	$base_url = trim($base_url_guess,'/').'/';
	if (isset($base_url)) {
		$parts = parse_url($base_url);
		$http_protocol = $parts['scheme'];
		if (!isset($parts['path'])) {
		$parts['path'] = '';
		}
		$base_path = $parts['path'] . '/';
		$base_root = substr($base_url, 0, strlen($base_url) - strlen($parts['path']));
	} else {
		$http_protocol = $is_https ? 'https' : 'http';
		$base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
		if ($dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/')) {
			$base_path = $dir;
			$base_path .= '/';
		} else {
			$base_path = '/';
		}
	}
	$base_secure_url = str_replace('http://', 'https://', $base_url);
	$base_insecure_url = str_replace('https://', 'http://', $base_url);
}

function request_path()
{
	static $path;
	if (isset($path))
		return $path;
	if (isset($_SERVER['REQUEST_URI'])) {
		$request_path = strtok($_SERVER['REQUEST_URI'], '?');
		$base_path_len = strlen(rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/'));
		$path = substr(urldecode($request_path), $base_path_len + 1);
		if ($path == basename($_SERVER['PHP_SELF']))
			$path = '';
	} else {
		$path = '';
	}
	/*
	if (!empty($_GET)) {
		$path = '';
		foreach ($_GET as $key => $value)
			$path .= $key.(($value != '')?'/'.$value:'');
	} elseif (isset($_SERVER['REQUEST_URI'])) {
		$request_path = strtok($_SERVER['REQUEST_URI'], '?');
		$base_path_len = strlen(rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/'));
		$path = substr(urldecode($request_path), $base_path_len + 1);
		if ($path == basename($_SERVER['PHP_SELF']))
			$path = '';
	} else {
		$path = '';
	}
	*/
	$path = trim($path, '/');
	return $path;
}

/**
 * A unique token for GET and POST requests that should not be repeated
 */
function set_csrf_token()
{
	return $_SESSION['csrf_token'] = $_GLOBALS['csrf_token'] = md5(str_shuffle(chr(mt_rand(32, 126)) . uniqid() . microtime(TRUE)));
}

// Validate the token and the action. The action and token fileds are required for all form submissions.
function token_validated($action)
{
	global $csrf_token;

	if (empty($_POST) || !isset($_POST['action']) || (isset($_POST['action']) && $_POST['action'] != $action))
		return false;

	// check csrf token
	if (isset($_POST['token']) && $_POST['token'] != '' && $_POST['token'] == $csrf_token) {
		// generate new token
		$csrf_token = set_csrf_token();
		return true;
	}
	return false;
}

// Process $_POST data
function receive($postvars)
{
	$data = array();
	foreach ($postvars as $val) {
		if (isset($_POST[$val])) {
			if (!is_array($_POST[$val])){
				$data[$val] = trim($_POST[$val]);
			} else {
				$data[$val] = $_POST[$val];
			}
		} else {
			$_POST[$val] = '';
		}
	}
	return $data;
}

// Returns flase of a required item is missing
function required($keys,$post)
{
	foreach ($keys as $key) {
		if (isset($post[$key]) && $post[$key] != '') {
			continue;
		} else {
			return false;
		}
	}
	return true;
}

// Template
function tpl($tplfile, $tplvars = array())
{
	global $base_url;
	if (file_exists($tplfile)) {
		ob_start();
		extract($tplvars);
		include($tplfile);
		$tplcontents = ob_get_contents();
		ob_end_clean();
	} else {
		die("Template file does not exist: <code>".$tplfile."</code>");
	}
	return $tplcontents;
}

// Return an html unordered list from an array.
function ul_list($data = array(), $class = '')
{
	$class = ($class != null)?" class=\"{$class}\"":'';
	$render = "<ul{$class}>\r";
	if (isset($data) && !is_object($data)) {
		foreach ($data as $key => $value) {
			if (is_object($value))
				$value = (array) $value;
			if (is_array($value) && !empty($value)) {
				$render .= ul_list($value);
			} else {
				$render .= "\t<li>{$value}</li>\r";
			}
		}
	}
	$render .= "</ul>\r";
	return $render;
}

// Retrieve the messages currently in the queue as a list.
function get_messages($class = '')
{
	global $error, $success;

	// Report preserved error messages and new $error
	$success = array_merge($success,$_SESSION['success']);
	$error = array_merge($error,$_SESSION['error']);

	// Empty errors in session after reporting error messages
	$_SESSION['success'] = array();
	$_SESSION['error'] = array();

	$o = (!empty($success))?ul_list($success, 'messages alert alert-success'):'';
	$o .= (!empty($error))?ul_list($error, 'messages alert alert-error'):'';

	return $o;
}

// Display $message and redirect user to $destination_url
function redirect($destination_url)
{
	global $base_url, $error, $success;
	// Preserve $errors and $success in session variable before redirecting
	$_SESSION['error'] = $error;
	$_SESSION['success'] = $success;

	// Prefix with base_url (unless it's there already)
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = $base_url.$destination_url;

	// Do a little spring cleaning
	$destination_url = preg_replace('/([\r\n])|(%0[ad])|(;[\s]*data[\s]*:)/i', '', $destination_url);

	header('Location: '.str_replace('&amp;', '&', $destination_url));
}

/**
 * Return the request url as a string provided the boolean $is_subdomain which corrects for
 * subdomains at the beginning of the url array
 */
function permalink($is_subdomain = false)
{
	static $perma;
	if (isset($perma))
		return $perma;
	$req	= str_replace('&amp;', '&', strip_tags($_SERVER['REQUEST_URI']));
	$req	= (preg_match('/=/', $req))?implode("/", str_replace('&', '/', str_replace('?', '', explode('=', $req)))):str_replace('&', '/', str_replace('?', '', $req));
	$url_array	= explode("/",$req);
	if ($url_array[0] == '') array_shift($url_array);
	array_shift($url_array);
	if (end($url_array) == '') array_pop($url_array);
	if ($is_subdomain)
		$url_array = array_pop($url_array);

	$perma = join('/',(is_array($url_array)?$url_array:array($url_array)));
	return $perma;
}

/**
 * Match the regular expression patterns with a controller function
 */
function url_match($map)
{
	$permalink = permalink();
	foreach ($map as $pattern => $func) {
		if (preg_match($pattern, $permalink))
			return $func;
	}
	return false;
}

/**
 * Map regular expression URI to functions or files
 */
function map($map, $avail_vars = array())
{
	global $config, $base_url, $csrf_token;
	$func = url_match($map);
	if (strpos($func,'.php') === false) {
		if (!is_callable($func))
			die('<!DOCTYPE html><html lang="en"><head><meta http-equiv="Content-type" content="text/html; charset=utf-8"><title>Error</title></head><body><h2>Error 404</h2><p>The page you are looking for could not be found.</p></body></html>');
		$func();
	} else {
		$url_parts = explode('?', $func);
		if (isset($url_parts[1])) {
			$query_string = explode('&', $url_parts[1]);
			foreach ($query_string as $cur_param) {
				$param_data = explode('=', $cur_param);

				$param_data[1] = isset($param_data[1]) ? $param_data[1] : null;

				if (!isset($_POST[$param_data[0]]) && !isset($_COOKIE[$param_data[0]]))
					$_REQUEST[$param_data[0]] = urldecode($param_data[1]);

				$_GET[$param_data[0]] = urldecode($param_data[1]);
			}
		}
		if (!file_exists(BASEPATH.$url_parts[0]))
			die('<h2>Error 404</h2><p>The page you are looking for could not be found.</p>');
		include(BASEPATH.$url_parts[0]);
	}
}

/**
* Plugins
*/
class Plugins
{
	public static $loaded_plugins = array();
	public static $default_sort_order = 100;

	public static function load()
	{
		global $config;
		// Load plugins
		self::$loaded_plugins = array();
		if (!empty($config['plugins'])) {
			$plugins = array();
			$i = self::$default_sort_order; // default sort order
			foreach ($config['plugins'] as $plugin) {
				if (is_array($plugin)) {
					$order = (int) $plugin['order'];
					$plugin_name = $plugin['name'];
				} else {
					$order = $i;
					$i++;
					$plugin_name = $plugin;
				}
				$plugins[] = array('name'=>$plugin_name,'order'=>$order);
			}

			// sort tags using 'sort' attribute
			$plugins = array_sort($plugins, 'order');

			foreach ($plugins as $plugin) {
				if (file_exists(BASEPATH.'plugins/'.$plugin['name'].'.php')) {
					include(BASEPATH.'plugins/'.$plugin['name'].'.php');
					self::$loaded_plugins[] = $plugin['name'];
				} elseif(file_exists(BASEPATH.'plugins/'.$plugin['name'].'/'.$plugin['name'].'.php')) {
					include(BASEPATH.'plugins/'.$plugin['name'].'/'.$plugin['name'].'.php');
					self::$loaded_plugins[] = $plugin['name'];
				}
			}
		}
	}
}

/**
* Actions
*/
class Actions
{
	static public $events = array();
	static public $filters = array();

	/** Register $callable for receiving $event */
	static public function observe($event, $callable)
	{
		global $events;
		if(isset(self::$events[$event]))
			self::$events[$event][] = $callable;
		else
			self::$events[$event] = array($callable);
	}

	/** Dispatch an event, optionally with arguments. */
	static public function event(/* $event [, $arg ..] */ )
	{
		$args = func_get_args();
		$event = array_shift($args);
		if(isset(self::$events[$event])) {
			foreach(self::$events[$event] as $callable) {
				if (call_user_func_array($callable, $args) === true)
					break;
			}
		}
	}

	/** Unregister $callable from receiving $event s */
	static public function stop_observing($callable, $event=null)
	{
		if($event !== null) {
			if(isset(self::$events[$event])) {
				$a =& self::$events[$event];
				if(($i = array_search($callable, $a)) !== false) {
					unset($a[$i]);
					if(!$a)
						unset(self::$events[$event]);
					return true;
				}
			}
		}
		else {
			foreach(self::$events as $n => $a) {
				if(($i = array_search($callable, $a)) !== false) {
					unset(self::$events[$n][$i]);
					if(!self::$events[$n])
						unset(self::$events[$n]);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Add a filter
	 * 
	 * Lower number for $priority means earlier execution of $func.
	 * 
	 * If $func returns boolean FALSE the filter chain is broken, not applying
	 * any more filter after the one returning FALSE. Returning anything else
	 * have no effect.
	 */
	static function add_filter($tag, $func, $priority=100)
	{
		if (!isset(self::$filters[$tag]))
			self::$filters[$tag] = array($priority => array($func));
		elseif (!isset(self::$filters[$tag][$priority]))
			self::$filters[$tag][$priority] = array($func);
		else
			self::$filters[$tag][$priority][] = $func;
	}

	/** Apply filters for $tag on $value */
	static function filter($tag, $value/*, [arg ..] */)
	{
		$vargs = func_get_args();
		$tag = array_shift($vargs);
		if (!isset(self::$filters[$tag]))
			return $value;
		$a = self::$filters[$tag];
		if ($a === null)
			return $value;
		ksort($a, SORT_NUMERIC);
		foreach ($a as $funcs) {
			foreach ($funcs as $func) {
				$value = call_user_func_array($func, $vargs);
				$vargs[0] = $value;
			}
		}
		return $vargs[0];
	}
}

// Display a simple error message
function error()
{
	global $config, $base_url, $db;

	if (!headers_sent()) {
		// if no HTTP responce code is set we send 503
		if (!defined('FORUM_HTTP_RESPONSE_CODE_SET'))
			header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Content-type: text/html; charset=utf-8');
	}

	/*
		Parse input parameters. Possible function signatures:
		error('Error message.');
		error(__FILE__, __LINE__);
		error('Error message.', __FILE__, __LINE__);
	*/
	$num_args = func_num_args();
	if ($num_args == 3) {
		$message = func_get_arg(0);
		$file = func_get_arg(1);
		$line = func_get_arg(2);
	} else if ($num_args == 2) {
		$file = func_get_arg(0);
		$line = func_get_arg(1);
	} else if ($num_args == 1)
		$message = func_get_arg(0);

	// Empty all output buffers and stop buffering
	while (@ob_end_clean());

	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
	if (!empty($config['gzip']) && extension_loaded('zlib') && !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');

	include(BASEPATH.'includes/error.php');
	exit;
}

if (file_exists(BASEPATH.'includes/common.php'))
	include(BASEPATH.'includes/common.php');

// Route URLs here
$map = Actions::filter('map',array(
	'/logout\/(.*)/'		=> 'logout.php',
	'/add\/(.*)/'			=> 'add.php',
	'(.*)'					=> 'homepage.php'
));

// Output
$func = url_match($map);
if (strpos($func,'.php') === false) {
	if (!is_callable($func))
		die('<!DOCTYPE html><html lang="en"><head><meta http-equiv="Content-type" content="text/html; charset=utf-8"><title>Error</title></head><body><h2>Error 404</h2><p>The page you are looking for could not be found.</p></body></html>');
	$func();
} else {
	$url_parts = explode('?', $func);
	if (isset($url_parts[1])) {
		$query_string = explode('&', $url_parts[1]);
		foreach ($query_string as $cur_param) {
			$param_data = explode('=', $cur_param);

			$param_data[1] = isset($param_data[1]) ? $param_data[1] : null;

			if (!isset($_POST[$param_data[0]]) && !isset($_COOKIE[$param_data[0]]))
				$_REQUEST[$param_data[0]] = urldecode($param_data[1]);

			$_GET[$param_data[0]] = urldecode($param_data[1]);
		}
	}
	if (!file_exists(BASEPATH.$url_parts[0]))
		die('<h2>Error 404</h2><p>The page you are looking for could not be found.</p>');
	include(BASEPATH.$url_parts[0]);
}
?>