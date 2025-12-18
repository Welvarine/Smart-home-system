function register() {
  save('user', {email: email.value, password: password.value});
  alert('Registered');
}
function login() {
  const u = load('user');
  if(u && u.email === email.value && u.password === password.value){
    location.href = 'dashboard.html';
  } else {
    alert('Invalid login');
  }
}