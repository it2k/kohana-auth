<div class="col-md-4 col-md-offset-4">
	<form class="form-signin" role="form" method="post">
		<h2 class="form-signin-heading">Смена пароля</h2>
		<?php if (Auth::instance()->logged_in()): ?>
		<input name="current_password" type="password" class="form-control form-control" placeholder="Текущий пароль" required autofocus><br>
		<?php endif; ?>
		<input name="password" type="password" class="form-control form-control-top" placeholder="Новый пароль" required autofocus>
		<input name="password_confirm" type="password" class="form-control form-control-bottom" placeholder="Подтверждение пароля" required>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Изменить</button>
	</form>
</div>