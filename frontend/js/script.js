// ===========================
// Global Modal Functions
// ===========================
window.openModal = function (id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add('active');
  document.body.style.overflow = 'hidden'; // Prevent background scrolling
};

window.closeModal = function (id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('active');
  document.body.style.overflow = ''; // Restore scrolling
};

// ===========================
// API Request Helper
// ===========================
window.apiRequest = async function(endpoint, method = 'GET', body = null, token = null) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  try {
    const response = await fetch(`http://localhost:8000/api/v1${endpoint}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : null
    });

    const data = await response.json();
    return { ok: response.ok, data };
  } catch (err) {
    console.error('API Request Error:', err);
    return { ok: false, data: { status: 'error', error: { message: 'Network error' } } };
  }
};

// ===========================
// DOM Content Loaded Events
// ===========================
document.addEventListener('DOMContentLoaded', function () {
  console.log('EstateHub initialized successfully');

  // ===========================
  // Mobile Navigation Toggle
  // ===========================
  const menuToggles = document.querySelectorAll('.menu-toggle');
  menuToggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      const navLinks = document.getElementById('navLinks') || toggle.closest('nav')?.querySelector('.nav-links');
      if (navLinks) navLinks.classList.toggle('active');
    });
  });

  const navLinks = document.querySelectorAll('.nav-links');
  navLinks.forEach(function (nav) {
    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        nav.classList.remove('active');
      });
    });
  });

  // ===========================
  // Password Visibility Toggle
  // ===========================
  const passwordToggles = document.querySelectorAll('.toggle-password');
  passwordToggles.forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      const container = toggle.closest('.password-container');
      const input = container?.querySelector('input');
      if (!input) return;

      if (input.type === 'password') {
        input.type = 'text';
        toggle.textContent = '🙈';
      } else {
        input.type = 'password';
        toggle.textContent = '👁️';
      }
    });
  });

  // ===========================
  // Property & Tenant Modals
  // ===========================
  ['property', 'tenant'].forEach(type => {
    const addBtn = document.getElementById(`add${type.charAt(0).toUpperCase() + type.slice(1)}Btn`);
    const modal = document.getElementById(`${type}Modal`) || document.getElementById(`${type}Popup`);
    const cancelBtn = document.getElementById(`close${type.charAt(0).toUpperCase() + type.slice(1)}Modal`) || document.getElementById(`cancel${type.charAt(0).toUpperCase() + type.slice(1)}Btn`);

    if (addBtn && modal) {
      addBtn.addEventListener('click', function () {
        const form = document.getElementById(`${type}Form`);
        if (form) form.reset();
        const idInput = document.getElementById(`${type}Id`);
        if (idInput) idInput.value = '';
        const title = document.getElementById(type === 'property' ? 'popupTitle' : 'modalTitle');
        if (title) title.textContent = `Add ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        openModal(modal.id);
      });
    }

    if (cancelBtn && modal) {
      cancelBtn.addEventListener('click', function () {
        closeModal(modal.id);
      });
    }
  });

  // ===========================
  // Close Modal on Backdrop Click
  // ===========================
  window.addEventListener('click', function (e) {
    const target = e.target;
    if (target.classList && target.classList.contains('modal') && target.classList.contains('active')) {
      target.classList.remove('active');
      document.body.style.overflow = '';
    }
  });

  // ===========================
  // Validation Helpers
  // ===========================
  window.validateEmail = email => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).toLowerCase());
  window.validatePhone = phone => /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/.test(String(phone));

  // ===========================
  // Logout Function
  // ===========================
  window.logout = function() {
    if (confirm('Are you sure you want to logout?')) {
      sessionStorage.clear();
      localStorage.clear();
      window.location.href = 'index.html';
    }
  };

  // ===========================
  // Date & Currency Formatting
  // ===========================
  window.formatDate = date => new Date(date).toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
  window.formatCurrency = amount => 'KSh ' + amount.toLocaleString();

  // ===========================
  // Active Navigation Highlight
  // ===========================
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a, .sidebar a').forEach(link => {
    if (link.getAttribute('href') === currentPage) link.classList.add('active');
  });

  // ===========================
  // Smooth Scroll (Fixed)
  // ===========================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const href = anchor.getAttribute('href');
      // Only process if href is more than just '#'
      if (href && href.length > 1) {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ===========================
  // Toast Notification
  // ===========================
  window.showToast = (message, type='success') => {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed; top: 80px; right: 20px;
      background: ${type==='success'?'#27ae60':type==='error'?'#dc3545':'#ffc107'};
      color: white; padding: 15px 25px; border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 10000;
      animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'slideOut 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  };

  // ===========================
  // Loading Spinner
  // ===========================
  window.showLoader = () => {
    if (document.getElementById('loader')) return;
    const loader = document.createElement('div');
    loader.id = 'loader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = 'position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:9999;';
    document.body.appendChild(loader);
  };
  window.hideLoader = () => { const loader = document.getElementById('loader'); if (loader) loader.remove(); };

  // ===========================
  // Local Storage Helpers
  // ===========================
  window.saveToStorage = (key, data) => { try { localStorage.setItem(key, JSON.stringify(data)); return true; } catch(e) { console.error(e); return false; } };
  window.getFromStorage = key => { try { const data = localStorage.getItem(key); return data ? JSON.parse(data) : null; } catch(e){ console.error(e); return null; } };

  // ===========================
  // Confirm & Print Helpers
  // ===========================
  window.confirmAction = (message, callback) => { if(confirm(message)){ if(typeof callback==='function') callback(); return true; } return false; };
  window.printPage = () => window.print();

  // ===========================
  // Tooltips
  // ===========================
  document.querySelectorAll('[data-tooltip]').forEach(el => {
    el.addEventListener('mouseenter', function(){
      const tooltip = document.createElement('div');
      tooltip.className = 'tooltip';
      tooltip.textContent = el.getAttribute('data-tooltip');
      tooltip.style.cssText = 'position:absolute; background:#333; color:white; padding:5px 10px; border-radius:4px; font-size:0.85rem; z-index:10000; pointer-events:none;';
      document.body.appendChild(tooltip);
      const rect = el.getBoundingClientRect();
      tooltip.style.top = (rect.top-tooltip.offsetHeight-5)+'px';
      tooltip.style.left = (rect.left + rect.width/2 - tooltip.offsetWidth/2)+'px';
      el._tooltip = tooltip;
    });
    el.addEventListener('mouseleave', function(){ if(this._tooltip){ this._tooltip.remove(); delete this._tooltip; } });
  });

  // ===========================
  // Login Form Handler (for pages that use script.js)
  // ===========================
  const loginForm = document.getElementById('loginForm');
  if (loginForm && !loginForm.dataset.handlerAttached) {
    loginForm.dataset.handlerAttached = 'true';
    loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      if (!email || !password) return showToast('Fill in all fields','error');

      showLoader();
      const res = await apiRequest('/auth/login','POST',{email,password});
      hideLoader();

      if (res.ok && res.data.status==='success') {
        localStorage.setItem('authToken', res.data.data.token);
        localStorage.setItem('user', JSON.stringify(res.data.data.user));
        showToast(res.data.data.message || 'Login successful','success');
        
        // FIXED: Correct role-based redirect
        const role = res.data.data.user?.role;
        if (role === 'admin') {
          window.location.href = 'admin-dashboard.html';
        } else if (role === 'tenant') {
          window.location.href = 'tenants_dashboard.html';
        } else {
          window.location.href = 'index.html';
        }
      } else {
        showToast(res.data.error?.message || 'Login failed','error');
      }
    });
  }

  // ===========================
  // Register Form Handler
  // ===========================
  const registerForm = document.getElementById('registerForm');
  if (registerForm && !registerForm.dataset.handlerAttached) {
    registerForm.dataset.handlerAttached = 'true';
    registerForm.addEventListener('submit', async e => {
      e.preventDefault();
      const name = document.getElementById('name').value.trim();
      const email = document.getElementById('email').value.trim();
      const phone = document.getElementById('phone').value.trim();
      const role = document.getElementById('role').value;
      const password = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirmPassword').value;

      if (!name || !email || !phone || !role || !password || !confirmPassword) return showToast('Fill in all fields','error');
      if (password!==confirmPassword) return showToast('Passwords do not match','error');

      showLoader();
      const res = await apiRequest('/auth/register','POST',{full_name:name,email,phone,password,role});
      hideLoader();

      if (res.ok && res.data.status==='success') {
        showToast(res.data.data.message || 'Registration successful','success');
        setTimeout(() => window.location.href = 'login.html', 1500);
      } else {
        showToast(res.data.error?.message || 'Registration failed','error');
      }
    });
  }

  console.log('All event listeners initialized');
});

// ===========================
// Add CSS Animations Dynamically
// ===========================
const style = document.createElement('style');
style.textContent = `
@keyframes slideIn { from { transform:translateX(100%); opacity:0; } to { transform:translateX(0); opacity:1; } }
@keyframes slideOut { from { transform:translateX(0); opacity:1; } to { transform:translateX(100%); opacity:0; } }
.spinner { width:50px; height:50px; border:5px solid rgba(255,255,255,0.3); border-top-color:white; border-radius:50%; animation:spin 1s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
`;
document.head.appendChild(style);