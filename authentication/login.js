const form = document.getElementById('loginForm');

form.addEventListener('submit', function (e) {
    const usernameEmail = form.username_email.value.trim();
    const password = form.password.value.trim();
    let errorDiv = document.querySelector('.error-message');

    if (!usernameEmail || !password) {
        e.preventDefault();
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = 'Please fill in all fields.';
            form.parentElement.insertBefore(errorDiv, form);
        } else {
            errorDiv.textContent = 'Please fill in all fields.';
            errorDiv.style.animation = 'shake 0.3s';
            setTimeout(() => {
                errorDiv.style.animation = '';
            }, 300);
        }
    }
});

function togglePassword() {
    const pwd = document.getElementById('password');
    const btn = event.target;
    if (pwd.type === 'password') {
        pwd.type = 'text';
        btn.textContent = 'Hide';
    } else {
        pwd.type = 'password';
        btn.textContent = 'Show';
    }
}
