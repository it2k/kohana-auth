<div class="col-md-4 col-md-offset-4">
	<form class="form-signin" role="form" method="<?php echo ($email AND $code) ? 'post' : 'get' ?>" <?php echo ($email AND $code) ? 'action="'.URL::base().'auth/reset_password/?email='.$email.'&code='.$code.'"' : '' ?>>
		<h2 class="form-signin-heading">Восстановление</h2>
		<?php if($email AND $code): ?>
		<input name="password" type="password" class="form-control form-control-top" placeholder="Пароль" required>
		<input name="password_confirm" type="password" class="form-control form-control-bottom" placeholder="Подтверждение пароля" required>
		<?php elseif($email): ?>
		<input type="hidden" name="email" value="<?php echo $email ?>">
		<input name="code" type="email" class="form-control" placeholder="Код подтверждения" required autofocus><br>
		<?php else: ?>
		<input name="email" type="email" class="form-control" placeholder="Имя пользователя / Email" required autofocus><br>
		<?php endif; ?>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Восстановить пароль</button>
		<p>
			<ul>
				<li><a href="<?php echo URL::base()?>auth/">Авторизация</a></li>
				<li><a href="<?php echo URL::base()?>auth/registration">Регистрация</a></li>
			</ul>
		</p>
	</form>
</div>