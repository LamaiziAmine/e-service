const authBox = document.getElementById('auth-box');
const authRegisterBtn = document.getElementById('auth-register');
const authLoginBtn = document.getElementById('auth-login');

authRegisterBtn.addEventListener('click', () => {
    authBox.classList.add("active");
});

authLoginBtn.addEventListener('click', () => {
    authBox.classList.remove("active");
});
