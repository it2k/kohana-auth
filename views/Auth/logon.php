<div class="col-md-4 col-md-offset-4">
	<form class="form-signin" role="form" method="post">
		<h2 class="form-signin-heading">Авторизация</h2>
		<input type="email" class="form-control form-control-top" placeholder="Имя пользователя / Email" required autofocus>
		<input type="password" class="form-control form-control-bottom" placeholder="Пароль" required>
		<div class="checkbox">
			<label>
				<input type="checkbox" value="remember-me"> Запомнить меня
			</label>
		</div>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Вход</button>
		<p>
			<ul>
				<li><a href="<?php echo URL::base()?>auth/registration">Регистрация</a></li>
				<li><a href="<?php echo URL::base()?>auth/lost_password">Восстановление пароля</a></li>
			</ul>
		</p>
	</form>
</div>