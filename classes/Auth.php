<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Auth extends Kohana_Auth {
	
	protected $DATAPATH = NULL;
	
	abstract function _registration($username, $email, $password, $active = false);
	abstract function _activation($username);
	
	public function __construct($config = array())
	{
		parent::__construct($config);
		
		$this->DATAPATH = (isset($config['data_path'])) ? trim($config['data_path']) : APPPATH.'data'.DIRECTORY_SEPARATOR.'Auth'.DIRECTORY_SEPARATOR;
		
		if (!$this->init_datapath($this->DATAPATH))
			throw new HTTP_Exception_500('Directory :dir not writable!', array(':dir' => $this->DATAPATH));
		
		$this->init_datapath($this->DATAPATH.'EmailConfirm/');
		$this->init_datapath($this->DATAPATH.'ResetPassword/');
	}
	
	public function registration($username, $email, $password)
	{
		$email_confirm = (!isset($this->_config['email_confirm']) OR !$this->_config['email_confirm']) ? FALSE : TRUE;
		
		$valid = Validation::factory(array(
			'username' => $username,
			'password' => $password,
			'email'    => $email,
		));
		
		$valid->rule(TRUE, 'not_empty')
			  ->rule('email', 'email')
			  ->rule('email', 'max_length', array(':value', '20'))
			  
			  ->rule('username', 'alpha_dash')
			  ->rule('username', 'min_length', array(':value', '4'))
			  ->rule('username', 'max_length', array(':value', '12'))
			  
			  ->rule('password', 'min_length', array(':value', '6'));
			  
		if (!$valid->check())
		{
			return $valid->errors('validation');
		}
		
		if ($email_confirm AND file_exists($this->DATAPATH.'EmailConfirm/'.$email))
		{
			return 'Емайл адрес уже используется!';
		}
		
		return $this->_registration($username, $email, $password, ($email_confirm) ? FALSE : TRUE);
	}

	public function registration_confirm()
	{
		
	}
	
	public function reset_password()
	{
		
	}
	
	protected function init_datapath($path)
	{
		if (!file_exists($path) AND !is_writable($path) AND !@mkdir($path, 0750, TRUE) AND !chmod($path, 0750))
			return FALSE;
		
		return TRUE;
	}

}