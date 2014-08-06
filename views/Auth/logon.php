<div class="col-md-4 col-md-offset-4">
	<form class="form-signin" role="form" method="post">
		<h2 class="form-signin-heading">Авторизация</h2>
		<input name="username" type="username" class="form-control form-control-top" placeholder="Имя пользователя / Email" required autofocus>
		<input name="password" type="password" class="form-control form-control-bottom" placeholder="Пароль" required>
		<?php if ($allow_remember): ?>
		<div class="checkbox">
			<label>
				<input name="remember" type="checkbox" value="1"> Запомнить меня
			</label>
		</div>
		<?php endif; ?>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Вход</button>
		<p>
			<ul>
				<?php if ($allow_registration): ?>
				<li><a href="<?php echo URL::base()?>auth/registration">Регистрация</a></li>
				<?php endif; if ($allow_reset_password): ?>
				<li><a href="<?php echo URL::base()?>auth/lost_password">Восстановление пароля</a></li>
				<?php endif; ?>
			</ul>
		</p>
	</form>
</div>