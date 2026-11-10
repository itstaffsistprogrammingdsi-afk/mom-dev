'use strict';

const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const emailWrap = document.getElementById('emailWrap');
const passwordWrap = document.getElementById('passwordWrap');
const emailError = document.getElementById('emailError');
const passwordError = document.getElementById('passwordError');
const submitButton = document.getElementById('submitButton');
const togglePassword = document.getElementById('togglePassword');
const toast = document.getElementById('toast');
const helpDialog = document.getElementById('helpDialog');
let toastTimer;

// Update year automatically.
document.getElementById('currentYear').textContent = new Date().getFullYear();

function showToast(message) {
  toast.textContent = message;
  toast.classList.add('show');
  window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => toast.classList.remove('show'), 3400);
}

function setFieldError(input, wrap, errorElement, message) {
  wrap.classList.toggle('has-error', Boolean(message));
  errorElement.textContent = message || '';
  input.setAttribute('aria-invalid', message ? 'true' : 'false');
}

function validateEmail() {
  const value = emailInput.value.trim();
  if (!value) {
    setFieldError(emailInput, emailWrap, emailError, 'Email wajib diisi.');
    return false;
  }
  const valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);
  setFieldError(emailInput, emailWrap, emailError, valid ? '' : 'Masukkan alamat email yang valid.');
  return valid;
}

function validatePassword() {
  const value = passwordInput.value;
  if (!value) {
    setFieldError(passwordInput, passwordWrap, passwordError, 'Password wajib diisi.');
    return false;
  }
  const valid = value.length >= 6;
  setFieldError(passwordInput, passwordWrap, passwordError, valid ? '' : 'Password minimal 6 karakter.');
  return valid;
}

emailInput.addEventListener('blur', validateEmail);
passwordInput.addEventListener('blur', validatePassword);
emailInput.addEventListener('input', () => {
  if (emailError.textContent) validateEmail();
});
passwordInput.addEventListener('input', () => {
  if (passwordError.textContent) validatePassword();
});

togglePassword.addEventListener('click', () => {
  const show = passwordInput.type === 'password';
  passwordInput.type = show ? 'text' : 'password';
  togglePassword.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  togglePassword.setAttribute('title', show ? 'Sembunyikan password' : 'Tampilkan password');
  document.getElementById('eyeIcon').innerHTML = show
    ? '<path d="M3 3 21 21M10.6 10.6a2 2 0 0 0 2.8 2.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M9.9 5.2A10.9 10.9 0 0 1 12 5c6.1 0 9.5 7 9.5 7a16 16 0 0 1-3 3.7M6.2 6.3C3.8 8 2.5 12 2.5 12s3.4 7 9.5 7c1.3 0 2.4-.3 3.4-.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>'
    : '<path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.8"/>';
});

loginForm.addEventListener('submit', (event) => {
  event.preventDefault();
  const validEmail = validateEmail();
  const validPassword = validatePassword();
  if (!validEmail) {
    emailInput.focus();
    return;
  }
  if (!validPassword) {
    passwordInput.focus();
    return;
  }

  // Demo only: replace this section with your real authentication API.
  submitButton.disabled = true;
  submitButton.classList.add('is-loading');
  submitButton.querySelector('.button-label').textContent = 'Memeriksa...';

  window.setTimeout(() => {
    submitButton.disabled = false;
    submitButton.classList.remove('is-loading');
    submitButton.querySelector('.button-label').textContent = 'Masuk';
    showToast('Form berhasil divalidasi. Hubungkan endpoint login backend untuk autentikasi.');
  }, 900);
});

document.getElementById('forgotPassword').addEventListener('click', () => {
  document.getElementById('dialogTitle').textContent = 'Reset password';
  document.getElementById('dialogMessage').textContent = 'Untuk mengatur ulang password, silakan hubungi administrator sistem Notula atau gunakan prosedur pemulihan akun internal perusahaan.';
  helpDialog.showModal();
});

document.getElementById('contactAdmin').addEventListener('click', () => {
  document.getElementById('dialogTitle').textContent = 'Hubungi administrator';
  document.getElementById('dialogMessage').textContent = 'Akses akun Notula dikelola oleh administrator perusahaan. Silakan hubungi tim IT untuk meminta akun atau bantuan akses.';
  helpDialog.showModal();
});

document.getElementById('googleButton').addEventListener('click', () => {
  showToast('Google Sign-In belum dikonfigurasi. Tambahkan OAuth Client ID untuk mengaktifkannya.');
});

document.getElementById('closeDialog').addEventListener('click', () => helpDialog.close());
helpDialog.addEventListener('click', (event) => {
  if (event.target === helpDialog) helpDialog.close();
});
