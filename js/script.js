// Small utility to open/close modals by id. Exposed on window for reuse.
window.openModal = function (id) {
  var modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add('active');
};

window.closeModal = function (id) {
  var modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('active');
};

document.addEventListener('DOMContentLoaded', function () {
  console.log('EstateHub script loaded');

  // Responsive/mobile nav toggles: any .menu-toggle will toggle the nearest .nav-links
  document.querySelectorAll('.menu-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      // try to find nav-links by id first, else find sibling
      var navLinks = document.getElementById('navLinks') || toggle.closest('nav')?.querySelector('.nav-links');
      if (!navLinks) return;
      navLinks.classList.toggle('active');
    });
  });

  // Close mobile menu when any nav link is clicked
  document.querySelectorAll('.nav-links').forEach(function (nav) {
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        nav.classList.remove('active');
      });
    });
  });

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

  // Property modal (if present on the page) wiring
  var addPropertyBtn = document.getElementById('addPropertyBtn');
  var propertyPopup = document.getElementById('propertyPopup');
  var cancelPropertyBtn = document.getElementById('cancelPropertyBtn');

  if (addPropertyBtn && propertyPopup) {
    addPropertyBtn.addEventListener('click', function () {
      // Reset form if exists
      var form = document.getElementById('propertyForm');
      if (form) form.reset();
      var idInput = document.getElementById('propertyId');
      if (idInput) idInput.value = '';
      propertyPopup.classList.add('active');
    });
  }

  if (cancelPropertyBtn && propertyPopup) {
    cancelPropertyBtn.addEventListener('click', function () {
      propertyPopup.classList.remove('active');
    });
  }

  // Close any open modal when clicking on the backdrop
  window.addEventListener('click', function (e) {
    // If the clicked element is a modal with class 'modal' and active, close it
    var target = e.target;
    if (target.classList && target.classList.contains('modal') && target.classList.contains('active')) {
      target.classList.remove('active');
    }
  });
});
