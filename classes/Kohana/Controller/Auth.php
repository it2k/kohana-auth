<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Controller_Auth {

	protected $template = 'Auth/template';

	protected $view     = FALSE;

	protected $data     = array();
	
	protected $config 	= array(); 

	public $request;

	public $response;

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	public function execute()
	{

		$this->config = Kohana::$config->load('auth');

		$action = 'action_'.$this->request->action();

		if ( ! method_exists($this, $action))
			throw new HTTP_Exception_404();

		$this->template = View::factory($this->template);

		$this->template->title = 'Авторизация';
		$this->template->content = '';
		$this->template->message = '';
		$this->template->message_type = 'success';

		$this->template->allow_registration = ((isset($this->config['allow_registration']) AND ($this->config['allow_registration']) AND (in_array($this->config['driver'], array('ORM', 'Json')))) ? TRUE : FALSE);
		$this->template->allow_reset_password = (in_array($this->config['driver'], array('ORM', 'Json'))) ? TRUE : FALSE;
		$this->template->allow_remember = (in_array($this->config['driver'], array('ORM', 'Json'))) ? TRUE : FALSE;
		
		$this->template->set_global('allow_registration', $this->template->allow_registration);
		$this->template->set_global('allow_reset_password', $this->template->allow_reset_password);
		$this->template->set_global('allow_remember', $this->template->allow_remember);
		
		$this->data = $this->{$action}();

		if (!$this->template->content)
			$this->template->content = ($this->view) ? View::factory($this->view, $this->data) : '';
		
		$this->response->body($this->template);
		
		return $this->response;
	}
	
	protected function action_index()
	{
		$username = Arr::get($_POST, 'username');
		$password = Arr::get($_POST, 'password');
		
		$remember = (intval(Arr::get($_POST, 'remember')) == 1) ? TRUE : FALSE;
		
		if ($username AND $password)
		{
			if (Auth::instance()->login($username, $password, $remember))
			{
				HTTP::redirect(URL::base());	
			}
			else
			{
				$this->template->message = 'Не верное имя пользователя или пароль!';
				$this->template->message_type = 'danger';
			}
		}
		
		$this->view = 'Auth/logon';	
	}
	
	protected function action_logout()
	{
		Auth::instance()->logout();
		HTTP::redirect(URL::base());
	}
	
	protected function action_registration()
	{
		if (!$this->template->allow_registration)
			throw new HTTP_Exception_404();
		
		$username = trim(Arr::get($_POST, 'username'));
		$email    = trim(strtolower(Arr::get($_POST, 'email')));
		$password = Arr::get($_POST, 'password');
		$password_confirm = Arr::get($_POST, 'password_confirm');
		
		if ($username AND $email AND $password AND $password_confirm)
		{
			$email_confirm = (!isset($this->config['email_confirm']) OR !$this->config['email_confirm']) ? TRUE : FALSE;

			if ($errors = Auth::instance()->registration($username, $email, $password, $password_confirm))
			{
				if (is_array($errors))
				{
					$this->template->message = "<ul><li>".implode('</li><li>', $errors)."</li></ul>";
				}
				elseif (is_string($errors))
					$this->template->message = $errors;
				else
					$this->template->message = 'При регистрации возникли ошибки';
					
				$this->template->message_type = 'danger';
			}
			else
			{
				if (!$email_confirm)
				{
					Auth::instance()->force_login(strtolower($username));
					HTTP::redirect(URL::base());
				}
				else
				{
					HTTP::redirect(URL::base().'auth/registration_confirm?email='.$email);
				}
			}
		}
			
		$this->template->title = 'Регистрация';
		$this->view = 'Auth/registration';
	}
	
	protected function action_registration_confirm()
	{
		$email = strtolower($this->request->query('email'));
		$code  = $this->request->query('code');
		
		// TODO: Нужна проверка что это email
		if (!$email)
			throw new HTTP_Exception_500('Email not set');

		$this->template->message = '<strong>Регистрация прошла успешно</strong><p>На указанный Вами почтовый ящик('.$email.') выслан код подтверждения, для активации аккаунта введите код подтверждения в форму ниже, либо пройдите по ссылке из письма</p>';
		$this->template->message_type = 'info';
				
		if ($code)
		{
			if (Auth::instance()->registration_confirm($email, $code))
				HTTP::redirect(URL::base().'auth/');
				
			$this->template->message = 'Не верный код подтверждения!';
			$this->template->message_type = 'danger';

		}
						
		$this->template->title = 'Подтверждение регистрации';
		$this->view = 'Auth/registration_confirm';
		
		return array('email' => $email, 'code' => $code);
	}
	
	protected function action_password_reset()
	{
		
	}
	
	protected function action_lost_password()
	{
		if (!$this->template->allow_reset_password)
			throw new HTTP_Exception_404();
			
		$this->template->title = 'Восстановить пароль';
		$this->view = 'Auth/lost_password';	
	}
	
	protected function action_gen_password_hash()
	{
		$password = Arr::get($_POST, 'password');
		$password_confirm = Arr::get($_POST, 'password_confirm');
		
		if ($password AND $password_confirm)
		{
			if ($password <> $password_confirm)
			{
				$this->template->message = 'Пароли не совпадают!';
				$this->template->message_type = 'warning';
			}
			else
			{
				$this->template->message = Auth::instance()->hash($password);
			}
		}
		
		$this->template->title = 'Генерация хеша пароля';
		$this->view = 'Auth/gen_password_hash';
	}

}
