<div class="col-md-4 col-md-offset-4">
	<form class="form-signin" role="form" method="post">
		<h2 class="form-signin-heading">Регистрация</h2>
		<input name="username" type="username" class="form-control form-control-top" placeholder="Имя пользователя" required autofocus>
		<input name="email" type="email" class="form-control form-control-center" placeholder="Email" required>
		<input name="password" type="password" class="form-control form-control-center" placeholder="Пароль" required>
		<input name="password_confirm" type="password" class="form-control form-control-bottom" placeholder="Подтверждение пароля" required>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Зарегистрироватся</button>
		<p>
			<ul>
				<li><a href="<?php echo URL::base()?>auth/">Авторизация</a></li>
				<li><a href="<?php echo URL::base()?>auth/lost_password">Восстановление пароля</a></li>
			</ul>
		</p>
	</form>
</div>