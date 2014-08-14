<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Controller_Auth {

	public $request;

	public $response;

	protected $template = 'Auth/template';

	protected $view     = FALSE;

	protected $data     = array();
	
	protected $config 	= array();
	
	protected $_db_path;
	
	protected $allow_registration = FALSE;
	
	protected $allow_reset_password = FALSE;
	
	protected $allow_remember = FALSE;
	
	protected $email_confirm = TRUE;

	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	public function execute()
	{

		$this->_db_path = APPPATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'.auth'.DIRECTORY_SEPARATOR;
		if (!$this->init_db($this->_db_path) OR !$this->init_db($this->_db_path.'email_confirm') OR !$this->init_db($this->_db_path.'reset_password'))
			throw new HTTP_Exception_500('Cannot initialize auth database');

		$this->config = Kohana::$config->load('auth');			

		$action = 'action_'.$this->request->action();

		if ( ! method_exists($this, $action))
			throw new HTTP_Exception_404();

		if (class_exists('Model_User'))
		{
			$user = new Model_User();
			
			if (method_exists($user, 'unique_key_exists'))
			{
			
				$this->allow_reset_password = TRUE;
				
				$this->allow_remember = TRUE;
	
				if ((method_exists($user, 'create_user')) AND (isset($this->config['allow_registration'])) AND ($this->config['allow_registration']))
						$this->allow_registration = TRUE;
			}
		}
		
		if (isset($this->config['email_confirm']) AND !$this->config['email_confirm'])
			$this->email_confirm = FALSE;
		
		$this->template = View::factory($this->template);

		$this->template->title = 'Авторизация';
		$this->template->content = '';
		$this->template->message = '';
		$this->template->message_type = 'success';

		$this->template->allow_registration = $this->allow_registration;
		$this->template->allow_reset_password = $this->allow_reset_password;
		$this->template->allow_remember = $this->allow_remember;
		
		$this->template->set_global('allow_registration', $this->allow_registration);
		$this->template->set_global('allow_reset_password', $this->allow_reset_password);
		$this->template->set_global('allow_remember', $this->allow_remember);
		
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
		if (!$this->allow_registration)
			throw new HTTP_Exception_404();
		
		$username = trim(strtolower($this->request->post('username')));
		$email    = trim(strtolower($this->request->post('email')));
		$password = $this->request->post('password');
		$password_confirm = $this->request->post('password_confirm');
		
		if ($username AND $email AND $password AND $password_confirm)
		{

			$user = new Model_User;

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
				$this->template->message = "<ul><li>".implode('</li><li>', $valid->errors('validation'))."</li></ul>";
				$this->template->message_type = 'danger';
			}
			else
			{
				if (!$this->email_confirm)
				{

					$user->create_user(array(
						'username'         => $username,
						'email'            => $email,
						'password'         => $password,
						'password_confirm' => $password_confirm,
						'enable'           => 1,
					), array('username', 'email', 'password', 'enable'));

					Auth::instance()->force_login(strtolower($username));
					HTTP::redirect(URL::base());
				}
				else
				{
					$data = array(
						'username' => $username,
						'email' => $email,
						'password' => $password,
						'password_confirm' => $password,
						'enable' => 1,
						'code' => Auth::instance()->hash(time().$username),
					);
				
					if (!file_put_contents($this->_db_path.'email_confirm'.DIRECTORY_SEPARATOR.$email, json_encode($data)))
						throw new HTTP_Exception_500('Cannot save email_confirm file');
				
					HTTP::redirect(URL::base().'auth/registration_confirm?email='.$email);
				}
			}
		}
			
		$this->template->title = 'Регистрация';
		$this->view = 'Auth/registration';
	}
	
	protected function action_registration_confirm()
	{
		if (!$this->allow_registration)
			throw new HTTP_Exception_404();
			
		$email = trim(strtolower($this->request->query('email')));
		$code  = trim($this->request->query('code'));
		
		// TODO: Нужна проверка что это email
		if (!$email OR !Valid::email($email))
			throw new HTTP_Exception_500('Не верный email адрес');

		$this->template->message = '<strong>Регистрация прошла успешно</strong><p>На указанный Вами почтовый ящик('.$email.') выслан код подтверждения, для активации аккаунта введите код подтверждения в форму ниже, либо пройдите по ссылке из письма</p>';
		$this->template->message_type = 'info';
				
		if ($code AND file_exists($this->_db_path.'email_confirm'.DIRECTORY_SEPARATOR.$email))
		{
			$data = json_decode(file_get_contents($this->_db_path.'email_confirm'.DIRECTORY_SEPARATOR.$email), TRUE);

			if ($data['code'] == $code)
			{
				$user = new Model_User();
				$user->create_user($data, array('username', 'email', 'password', 'enable'));
				unlink($this->_db_path.'email_confirm'.DIRECTORY_SEPARATOR.$email);
				HTTP::redirect(URL::base().'auth/');
			}
				
			$this->template->message = 'Не верный код подтверждения!';
			$this->template->message_type = 'danger';

		}
						
		$this->template->title = 'Подтверждение регистрации';
		$this->view = 'Auth/registration_confirm';
		
		return array('email' => $email, 'code' => $code);
	}
		
	protected function action_lost_password()
	{
		if (!$this->template->allow_reset_password)
			throw new HTTP_Exception_404();
		
		$email = $this->request->query('email');
		$code  = $this->request->query('code');
		
		if ($email)
		{
			if ($code)
			{
				if (!Auth::instance()->lost_password($email, $code))
				{
					$code = "";
					$this->template->message = 'Не верный код подтверждения.';
					$this->template->message_type = 'danger';
				}
				else
				{
					HTTP::redirect(URL::base().'auth/change_password?email='.$email.'&code='.$code);
				}
			}
			else
			{
				if (!Auth::instance()->lost_password($email))
				{
					$this->template->message = 'Пользователь с адресом '.$email.' не зарегистрирован.';
					$this->template->message_type = 'danger';

					$email = "";
				}					
			}
		}
		
		$this->template->title = 'Восстановить пароль';
		$this->view = 'Auth/lost_password';
		
		return array('email' => $email, 'code' => $code);
	}
	
	protected function action_change_password()
	{
		$auth = Auth::instance();
		
		$email = $this->request->query('email');
		$code  = $this->request->query('code');
		
		if (!$auth->logged_in() AND ((!$email OR !$code) OR !$auth->lost_password($email, $code)))
			throw new HTTP_Exception_403();
		
		$current_password = $this->request->post('current_password');
		$password = $this->request->post('password');
		$password_confirm = $this->request->post('password_confirm');
		
		if ($auth->logged_in() AND $current_password AND $password AND $password_confirm)
		{
			if ($auth->password($auth->get_user()) == $auth->hash($current_password))
			{
				$result = $auth->change_password($password, $password_confirm);
				if (!is_array($result) && $result)
				{
					HTTP::redirect(URL::base().'auth/logout');	
				}
				else
				{
					$this->template->message = (is_array($result)) ? "<ul><li>".implode('</li><li>', $result)."</li></ul>" : "Ошиба при изменении пароля.";
					$this->template->message_type = 'danger';
				}
			}
			else
			{
				$this->template->message = "Не верно введен текущий пароль";
				$this->template->message_type = 'danger';
			}
		}
		elseif (!$auth->logged_in() AND $password AND $password_confirm)
		{
			$result = $auth->change_password($password, $password_confirm, $email);
			
			if (!is_array($result) && $result)
			{
				HTTP::redirect(URL::base().'auth/');	
			}
			else
			{
				$this->template->message = (is_array($result)) ? "<ul><li>".implode('</li><li>', $result)."</li></ul>" : "Ошиба при изменении пароля.";
				$this->template->message_type = 'danger';
			}
		}
		
		$this->template->title = 'Изменение пароля';
		$this->view = 'Auth/change_password';		
				
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

	protected function init_db($path)
	{
		if (!file_exists($path) AND !is_writable($path) AND !@mkdir($path, 0750, TRUE) AND !chmod($path, 0750))
			return FALSE;
		
		return TRUE;
	}


}
