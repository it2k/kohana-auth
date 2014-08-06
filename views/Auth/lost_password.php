<div class="col-md-4 col-md-offset-4">
	<form class="form-signin" role="form" method="post">
		<h2 class="form-signin-heading">Восстановление</h2>
		<input type="email" class="form-control" placeholder="Имя пользователя / Email" required autofocus><br>
		<button class="btn btn-lg btn-primary btn-block" type="submit">Восстановить пароль</button>
		<p>
			<ul>
				<li><a href="<?php echo URL::base()?>auth/">Авторизация</a></li>
				<li><a href="<?php echo URL::base()?>auth/registration">Регистрация</a></li>
			</ul>
		</p>
	</form>
</div>