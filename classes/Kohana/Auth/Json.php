<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Auth_Json extends Auth {

	// User list
	protected $_users;

	protected $users_database_path = NULL;

	/**
	 * Constructor loads the user list into the class.
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->users_database_path = APPPATH.'data'.DIRECTORY_SEPARATOR.'Auth'.DIRECTORY_SEPARATOR.'Users'.DIRECTORY_SEPARATOR;
		
		if (isset($config['users_database_path']))
			$this->users_database_path = $config['users_database_path'];
			
		if (!file_exists($this->users_database_path) AND !is_writable($this->users_database_path) AND !$this->init_database($this->users_database_path))
			throw new HTTP_Exception_500('Directory :dir not writable!', array(':dir' => $this->users_database_path));
		
		$this->_users = Arr::get($config, 'users', array());
	}

	protected function init_database($path)
	{
		if (@mkdir($this->users_database_path, 0750, TRUE) AND chmod($this->users_database_path, 0750))
			return TRUE;
			
		return FALSE;
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
	
	public function registration($data, $email_confirm)
	{	
		$valid = Validation::factory($data);
		$valid->rule(TRUE, 'not_empty')
			  ->rule('email', 'email')
			  ->rule('username', 'alpha_numeric')
			  ->rule('username', 'min_length', array(':value', '5'))
			  ->rule('username', 'max_length', array(':value', '10'))
			  ->rule('password', 'min_length', array(':value', '6'));
			  
		if (!$valid->check())
			return $valid->errors();
		
		if (file_exists($this->users_database_path.strtolower($data['username'])))
			return 'Это имя пользователя уже занято!';
		
		return true;
	}

}