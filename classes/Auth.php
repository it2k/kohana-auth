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
		
		$this->DATAPATH = (isset($config['data_path'])) ? trim($config['data_path']) : APPPATH.'data'.DIRECTORY_SEPARATOR.'Auth'.DIRECTORY_SEPARATOR;
		
		if (!$this->init_datapath($this->DATAPATH))
			throw new HTTP_Exception_500('Directory :dir not writable!', array(':dir' => $this->DATAPATH));
		
		$this->init_datapath($this->DATAPATH.'EmailConfirm/');
		$this->init_datapath($this->DATAPATH.'ResetPassword/');
	}
	
	public function registration($username, $email, $password, $password_confirm)
	{
		$email_confirm = (!isset($this->_config['email_confirm']) OR !$this->_config['email_confirm']) ? TRUE : FALSE;
		
		$valid = Validation::factory(array(
			'username'         => $username,
			'email'            => $email,
			'password'         => $password,
			'password_confirm' => $password_confirm,
		));
		
		$valid->rule(TRUE, 'not_empty')
			  ->rule('email', 'email')
			  ->rule('email', 'max_length', array(':value', '20'))
			  ->rule('email', 'Auth::unique_email')
			  
			  ->rule('username', 'alpha_dash')
			  ->rule('username', 'min_length', array(':value', '4'))
			  ->rule('username', 'max_length', array(':value', '12'))
			  ->rule('username', 'Auth::unique_username')
			  
			  ->rule('password', 'min_length', array(':value', '6'))
			  ->rule('password_confirm',  'matches', array(':validation', 'password_confirm', 'password'));
			  
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
			
			file_put_contents($this->DATAPATH.'EmailConfirm/'.$email, json_encode($data));
		}
		else
		{
			$this->_registration($username, $email, $password);
		}
		
		// No errors
		return FALSE;
	}

	public function registration_confirm($email, $code)
	{	
		if (file_exists($this->DATAPATH.'EmailConfirm/'.$email))
		{
			$data = json_decode(file_get_contents($this->DATAPATH.'EmailConfirm/'.$email));
			
			if ($data->code == $code)
				$this->_registration($data->username, $data->email, $data->password);

			unlink($this->DATAPATH.'EmailConfirm/'.$email);			
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