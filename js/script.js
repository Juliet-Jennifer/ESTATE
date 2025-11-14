document.addEventListener('DOMContentLoaded', function () {
  console.log('EstateHub script loaded');

  var menuToggle = document.getElementById('menuToggle');
  var navLinks = document.getElementById('navLinks');

  if (menuToggle && navLinks) {
    menuToggle.addEventListener('click', function () {
      navLinks.classList.toggle('active');
    });

    // Close menu when a link is clicked (mobile)
    var links = navLinks.querySelectorAll('a');
    links.forEach(function (link) {
      link.addEventListener('click', function () {
        navLinks.classList.remove('active');
      });
    });
  }

  // Toggle password visibility for any .toggle-password elements
  document.querySelectorAll('.toggle-password').forEach(function (t) {
    t.addEventListener('click', function () {
      var input = t.closest('.password-container')?.querySelector('input');
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        t.textContent = '🙈';
      } else {
        input.type = 'password';
        t.textContent = '👁️';
      }
    });
  });
});
