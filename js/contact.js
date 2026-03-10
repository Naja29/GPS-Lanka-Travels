document.addEventListener('DOMContentLoaded', () => {

  const form    = document.getElementById('contactForm');
  const success = document.getElementById('formSuccess');
  const btnText = document.getElementById('btnText');
  const btnIcon = document.getElementById('btnIcon');

  if (!form) return;

  form.addEventListener('submit', e => {
    e.preventDefault();

    // Simple validation
    const required = form.querySelectorAll('[required]');
    let valid = true;

    required.forEach(field => {
      field.style.borderColor = '';
      if (!field.value.trim()) {
        field.style.borderColor = '#e74c3c';
        valid = false;
      }
    });

    // Email check
    const email = form.querySelector('#email');
    if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
      email.style.borderColor = '#e74c3c';
      valid = false;
    }

    if (!valid) return;

    // Simulate send
    btnText.textContent = 'Sending…';
    btnIcon.className   = 'fas fa-spinner fa-spin';

    setTimeout(() => {
      form.style.display = 'none';
      success.classList.add('show');
      success.style.display = 'flex';
    }, 1200);
  });

  // Clear error border on input
  form.querySelectorAll('input, textarea, select').forEach(f => {
    f.addEventListener('input', () => { f.style.borderColor = ''; });
  });

});
