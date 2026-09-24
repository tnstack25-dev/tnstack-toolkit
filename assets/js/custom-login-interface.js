(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var form = document.querySelector('.tnstack-login-page #login form');
		var password = document.querySelector('.tnstack-login-page input[type="password"]');

		if (password && !password.parentElement.querySelector('.wp-hide-pw')) {
			var toggle = document.createElement('button');
			toggle.type = 'button';
			toggle.className = 'tnstack-password-toggle';
			toggle.setAttribute('aria-label', 'Hiện mật khẩu');
			toggle.textContent = '◉';
			toggle.addEventListener('click', function () {
				var revealing = password.type === 'password';
				password.type = revealing ? 'text' : 'password';
				toggle.setAttribute('aria-label', revealing ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
			});
			password.parentElement.style.position = 'relative';
			password.parentElement.appendChild(toggle);
		}

		if (form) {
			form.addEventListener('submit', function () {
				var submit = form.querySelector('[type="submit"]');
				if (submit) {
					submit.classList.add('is-loading');
					submit.setAttribute('aria-busy', 'true');
				}
			});
		}
	});
}());
