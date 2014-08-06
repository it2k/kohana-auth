<?php defined('SYSPATH') OR die('No direct script access.');

class Controller_Auth {

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

		$action = 'action_'.$this->request->action();

		if ( ! method_exists($this, $action))
			throw new HTTP_Exception_404();

		$this->template = View::factory($this->template);
		
		$this->template->title = 'Авторизация';
		$this->template->content = '';
			
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
		$this->template->title = 'Регистрация';
		$this->view = 'Auth/registration';
	}
	
	protected function action_password_reset()
	{
		
	}
	
	protected function action_lost_password()
	{
		$this->template->title = 'Восстановить пароль';
		$this->view = 'Auth/lost_password';	
	}
	

}
