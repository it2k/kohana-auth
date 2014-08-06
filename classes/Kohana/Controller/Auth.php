<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Controller_Auth {

	protected $template = 'Auth/template';

	protected $view     = FALSE;

	protected $content  = '';

	protected $data     = array();

	public $request;

	public $response;

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	public function execute()
	{

		$config = Kohana::$config->load('auth');

		$action = 'action_'.$this->request->action();

		if ( ! method_exists($this, $action))
			throw new HTTP_Exception_404();

		$this->template = View::factory($this->template);

		$this->template->title = 'Авторизация';
		$this->template->content = '';
		$this->template->message = '';
		$this->template->message_type = 'success';

		$this->template->allow_registration = ((isset($config['allow_registration']) AND ($config['allow_registration']) AND (in_array($config['driver'], array('ORM', 'Json')))) ? TRUE : FALSE);
		$this->template->allow_reset_password = (in_array($config['driver'], array('ORM', 'Json'))) ? TRUE : FALSE;
		$this->template->allow_remember = (in_array($config['driver'], array('ORM', 'Json'))) ? TRUE : FALSE;
		
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
		$this->view = 'Auth/logon';	
	}
	
	protected function action_registration()
	{
		if (!$this->template->allow_registration)
			throw new HTTP_Exception_404();
			
		$this->template->title = 'Регистрация';
		$this->view = 'Auth/registration';
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
