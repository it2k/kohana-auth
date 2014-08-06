<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Auth_Json extends Auth {

	// User list
	protected $_users;

	/**
	 * Constructor loads the user list into the class.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->init_datapath($this->DATAPATH.'Users/');
		$this->init_datapath($this->DATAPATH.'Users/Emails/');
	}

	/**
	 * Logs a user in.
	 *
	 * @param   string   $username  Username
	 * @param   string   $password  Password
	 * @param   boolean  $remember  Enable autologin (not supported)
	 * @return  boolean
	 */
	protected function _login($username, $password, $remember)
	{
		if (is_string($password))
		{
			// Create a hashed password
			$password = $this->hash($password);
		}

		if (isset($this->_users[$username]) AND $this->_users[$username] === $password)
		{
			// Complete the login
			return $this->complete_login($username);
		}

		// Login failed
		return FALSE;
	}

	/**
	 * Forces a user to be logged in, without specifying a password.
	 *
	 * @param   mixed    $username  Username
	 * @return  boolean
	 */
	public function force_login($username)
	{
		// Complete the login
		return $this->complete_login($username);
	}

	/**
	 * Get the stored password for a username.
	 *
	 * @param   mixed   $username  Username
	 * @return  string
	 */
	public function password($username)
	{
		return Arr::get($this->_users, $username, FALSE);
	}

	/**
	 * Compare password with original (plain text). Works for current (logged in) user
	 *
	 * @param   string   $password  Password
	 * @return  boolean
	 */
	public function check_password($password)
	{
		$username = $this->get_user();

		if ($username === FALSE)
		{
			return FALSE;
		}

		return ($password === $this->password($username));
	}
	
	public function _registration($username, $email, $password, $active = false)
	{	
		if (file_exists($this->DATAPATH.'Users/'.strtolower($username)))
			return 'Имя пользователя уже занято!';
		elseif (file_exists($this->DATAPATH.'Users/Emails/'.$email))
			return 'Емайл адрес уже используется!';
		
		$data = array(
			'username' => $username,
			'email'    => $email,
			'password' => $this->hash($password),
			'active'   => $active,
		);
		
		if (file_put_contents($this->DATAPATH.'Users/'.strtolower($username), json_encode($data)) AND file_put_contents($this->DATAPATH.'Users/Emails/'.$email, strtolower($username)))
			return false;
			
		return true;
	}
	
	public function _activation($username)
	{
		return true;
	}

}