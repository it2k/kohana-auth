<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Auth extends Kohana_Auth {
	
	protected $DATAPATH = NULL;
	
	abstract function _registration($username, $email, $password);
	abstract function _activation($email);
	abstract protected function _change_password($password, $email = NULL);
	abstract function _unique_username($username);
	abstract function _unique_email($email);
	
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->DATAPATH = (isset($config['data_path'])) ? trim($config['data_path']) : APPPATH.'data'.DIRECTORY_SEPARATOR.'.auth'.DIRECTORY_SEPARATOR;
		
		if (!$this->init_datapath($this->DATAPATH))
			throw new HTTP_Exception_500('Directory :dir not writable!', array(':dir' => $this->DATAPATH));
		
		$this->init_datapath($this->DATAPATH.'email_confirm'.DIRECTORY_SEPARATOR);
		$this->init_datapath($this->DATAPATH.'reset_password'.DIRECTORY_SEPARATOR);
	}
	
	public function registration($username, $email, $password, $password_confirm)
	{
		// Если разрешена регистрация и есть класс Model_User
		if (!isset($this->_config['allow_registration']) OR !$this->_config['allow_registration'] OR !class_exists('Model_User'))
			throw new HTTP_Exception_500('Registration not supported');
		
		$user = new Model_User;
		
		// Нужны два метода для регистрации create_user и unique_key_exists
		if (!method_exists($user, 'create_user') OR !method_exists($user, 'unique_key_exists'))
			throw new HTTP_Exception_500('Registration not supported');
	
		$email_confirm = (!isset($this->_config['email_confirm']) OR !$this->_config['email_confirm']) ? TRUE : FALSE;

		$valid = Validation::factory(array(
			'username'         => $username,
			'email'            => $email,
			'password'         => $password,
			'password_confirm' => $password_confirm,
		));
		
		foreach ($user->rules() as $field => $rules)
			$valid->rules($field, $rules);
			
		$valid->rule('password_confirm',  'matches', array(':validation', 'password_confirm', 'password'));
		
		if (!$valid->check())
		{
			return $valid->errors('validation');
		}
				
		if ($email_confirm)
		{
			$data = array(
				'username' => $username,
				'email' => $email,
				'password' => $password,
				'code' => $this->hash(time().$username),
			);
			
			file_put_contents($this->DATAPATH.'email_confirm'.DIRECTORY_SEPARATOR.$email, json_encode($data));
		}
		else
		{
			$user->create_user(array(
				'username'         => $username,
				'email'            => $email,
				'password'         => $password,
			), NULL);
		}
		
		// No errors
		return FALSE;
	}

	public function registration_confirm($email, $code)
	{
		// Если разрешена регистрация и есть класс Model_User
		if (!isset($this->_config['allow_registration']) OR !$this->_config['allow_registration'] OR !class_exists('Model_User'))
			throw new HTTP_Exception_500('Registration not supported');
		
		$user = new Model_User;

		if (file_exists($this->DATAPATH.'email_confirm'.DIRECTORY_SEPARATOR.$email))
		{
			$data = json_decode(file_get_contents($this->DATAPATH.'email_confirm'.DIRECTORY_SEPARATOR.$email));
			
			if ($data->code == $code)
				$user->create_user(array(
					'username'         => $data->username,
					'email'            => $data->email,
					'password'         => $data->password,
				), NULL);
				//$this->_registration($data->username, $data->email, $data->password);

			unlink($this->DATAPATH.'email_confirm'.DIRECTORY_SEPARATOR.$email);			
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function lost_password($email, $code = '')
	{

		if (Auth::instance()->_unique_email($email))
		{
			// Not found
			return FALSE;
		}

		if ($code)
		{
			if (file_exists($this->DATAPATH.'ResetPassword/'.$email) AND file_get_contents($this->DATAPATH.'ResetPassword/'.$email) == $code)
				return TRUE;
			else
				return FALSE;
		}
		else
		{
			$hash = $this->hash(time().$email);
			file_put_contents($this->DATAPATH.'ResetPassword/'.$email, $hash);
			return TRUE;
		}
	}
	
	public function change_password($password, $password_confirm, $email = NULL)
	{
		$valid = Validation::factory(array(
			'password'         => $password,
			'password_confirm' => $password_confirm,
		));
		
		$valid->rule(TRUE, 'not_empty')
			  ->rule('password', 'min_length', array(':value', '6'))
			  ->rule('password_confirm',  'matches', array(':validation', 'password_confirm', 'password'));
			  
		if (!$valid->check())
		{
			return $valid->errors('validation');
		}
		
		if ($this->_change_password($password, $email))
		{
			if ($email AND file_exists($this->DATAPATH.'ResetPassword/'.$email))
				unlink($this->DATAPATH.'ResetPassword/'.$email);
			
			return TRUE;
		}
		
		return FALSE;
				
	}
	
	protected function init_datapath($path)
	{
		if (!file_exists($path) AND !is_writable($path) AND !@mkdir($path, 0750, TRUE) AND !chmod($path, 0750))
			return FALSE;
		
		return TRUE;
	}

	public static function unique_username($username)
	{
		return Auth::instance()->_unique_username($username);
	}
	
	public static function unique_email($email)
	{
		$config = Kohana::$config->load('auth');
		
		$path = (isset($config['data_path'])) ? trim($config['data_path']) : APPPATH.'data'.DIRECTORY_SEPARATOR.'Auth'.DIRECTORY_SEPARATOR;
		
		if (file_exists($path.'EmailConfirm/'.$email))
		{
			// Return error
			return TRUE;
		}
		
		return Auth::instance()->_unique_email($email);
	}

}