<?php
/**
* Scholared User API
*/
class User
{
	public $is_loggedin = false;
	public $info = array();
	public $queries = array(
		"cookie_login"	=> "SELECT * FROM bm_users WHERE user_email=%s AND user_session_hash=%s",
		"email_exists"	=> "SELECT 1 FROM bm_users WHERE user_email=%s",
		"user_with_email"	=> "SELECT * FROM bm_users WHERE user_email=%s"
	);

	function __construct()
	{
		$this->cookie_login();
	}

	public function cookie_login()
	{
		global $config, $db;
		if (isset($_COOKIE[$config['cookie_name']]) && is_string($_COOKIE[$config['cookie_name']])) {
			$cookie = json_decode(base64_decode($_COOKIE[$config['cookie_name']]),true);
			$email = $cookie[0]; $session_hash = $cookie[1];
			$res = $db->conn->query(sprintf($this->queries['cookie_login'], $db->quote($email), $db->quote($session_hash)))->fetchAll(PDO::FETCH_ASSOC);
			if (!empty($res)) {
				$this->info = $res[0];
				$this->is_loggedin = true;
				return true;
			}
		}
		return false;
	}

	// Store the session hash in the database and in the cookie
	// Later we use the session hash and id to log in the user
	public function set_cookie($user)
	{
		global $base_url, $config, $db;
		$session_hash = generate_password();
		$q = $db->conn->prepare('UPDATE bm_users SET user_session_hash=? WHERE ID='.$db->quote($user['ID']));
		$q->execute(array($session_hash));
		$value = base64_encode(json_encode(array($user['user_email'],$session_hash)));
		header('P3P: CP="CUR ADM"');
		setcookie($config['cookie_name'], $value, time()+60*60*24*30, '/');
	}

	public function logout()
	{
		global $base_url, $config;
		setcookie($config['cookie_name'], '', time() - 3600, '/');
	}

	public function email_exists($email)
	{
		global $db;
		$res = $db->conn->query(sprintf($this->queries['email_exists'], $db->quote($email)))->fetchAll(PDO::FETCH_ASSOC);
		return empty($res)?false:true;
	}

	public function register($name, $email, $pass)
	{
		global $db;
		include(BASEPATH.'includes/class-phpass.php');
		$PH = new PasswordHash(8, false);
		$hashedpass = $PH->HashPassword($pass);
		$post = array(
			'user_login'	=> '',
			'user_email'	=> $email,
			'user_pass'		=> $hashedpass,
			'user_nicename'	=> $name,
			'user_registered'	=> time(),
			'user_activation_key'	=> generate_password(12, false),
			'user_session_hash'		=> generate_password(12),
			'user_status'			=> 2	// status = 2, users who haven't confirmed their registration via email
		);

		$values = array();
		foreach ($post as $key => $value) {
			$values[':'.$key] = $value;
		}
		$q = $db->conn->prepare('INSERT INTO bm_users ('.implode(',',array_keys($post)).') VALUES ('.implode(',',array_keys($values)).')');
		$q->execute($values);
		$user_id = $db->conn->lastInsertId();
		return true;
	}

	public function attempt_login($email, $pass)
	{
		global $db;
		$res = $db->conn->query(sprintf($this->queries['user_with_email'], $db->quote($email)))->fetchAll(PDO::FETCH_ASSOC);
		if (!empty($res)) {
			$user = $res[0];
			include(BASEPATH.'includes/class-phpass.php');
			$PH = new PasswordHash(8, false);
			if ($PH->CheckPassword($pass, $user['user_pass'])) {
				$this->set_cookie($user);
				$this->info = $user;
				$this->is_loggedin = true;
				return true;
			}
		}
		return false;
	}
}

?>