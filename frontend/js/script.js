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
      const navLinks = document.getElementById('navLinks') || 
                      toggle.closest('nav')?.querySelector('.nav-links');
      if (navLinks) {
        navLinks.classList.toggle('active');
      }
    });
  });

  // Close mobile menu when clicking on nav links
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
  // Property Modal Handlers
  // ===========================
  const addPropertyBtn = document.getElementById('addPropertyBtn');
  const propertyPopup = document.getElementById('propertyPopup');
  const cancelPropertyBtn = document.getElementById('cancelPropertyBtn');

  if (addPropertyBtn && propertyPopup) {
    addPropertyBtn.addEventListener('click', function () {
      const form = document.getElementById('propertyForm');
      if (form) form.reset();
      
      const idInput = document.getElementById('propertyId');
      if (idInput) idInput.value = '';
      
      const title = document.getElementById('popupTitle');
      if (title) title.textContent = 'Add Property';
      
      openModal('propertyPopup');
    });
  }

  if (cancelPropertyBtn && propertyPopup) {
    cancelPropertyBtn.addEventListener('click', function () {
      closeModal('propertyPopup');
    });
  }

  // ===========================
  // Tenant Modal Handlers
  // ===========================
  const addTenantBtn = document.getElementById('addTenantBtn');
  const tenantModal = document.getElementById('tenantModal');
  const closeTenantModalBtn = document.getElementById('closeModal');

  if (addTenantBtn && tenantModal) {
    addTenantBtn.addEventListener('click', function () {
      const form = document.getElementById('tenantForm');
      if (form) form.reset();
      
      const idInput = document.getElementById('tenantId');
      if (idInput) idInput.value = '';
      
      const title = document.getElementById('modalTitle');
      if (title) title.textContent = 'Add Tenant';
      
      openModal('tenantModal');
    });
  }

  if (closeTenantModalBtn && tenantModal) {
    closeTenantModalBtn.addEventListener('click', function () {
      closeModal('tenantModal');
    });
  }

  // ===========================
  // Close Modal on Backdrop Click
  // ===========================
  window.addEventListener('click', function (e) {
    const target = e.target;
    if (target.classList && 
        target.classList.contains('modal') && 
        target.classList.contains('active')) {
      target.classList.remove('active');
      document.body.style.overflow = '';
    }
  });

  // ===========================
  // Form Validation Helpers
  // ===========================
  window.validateEmail = function(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
  };

  window.validatePhone = function(phone) {
    const re = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/;
    return re.test(String(phone));
  };

  // ===========================
  // Logout Function
  // ===========================
  window.logout = function() {
    if (confirm('Are you sure you want to logout?')) {
      // Clear any stored session data
      sessionStorage.clear();
      localStorage.clear();
      
      // Redirect to home page
      window.location.href = 'index.html';
    }
  };

  // ===========================
  // Date Formatting Helper
  // ===========================
  window.formatDate = function(date) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(date).toLocaleDateString('en-US', options);
  };

  // ===========================
  // Currency Formatting Helper
  // ===========================
  window.formatCurrency = function(amount) {
    return 'KSh ' + amount.toLocaleString();
  };

  // ===========================
  // Active Navigation Highlight
  // ===========================
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  const navLinksAll = document.querySelectorAll('.nav-links a, .sidebar a');
  
  navLinksAll.forEach(function(link) {
    const href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    }
  });

  // ===========================
  // Smooth Scroll for Anchor Links
  // ===========================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // ===========================
  // Toast Notification System
  // ===========================
  window.showToast = function(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
      position: fixed;
      top: 80px;
      right: 20px;
      background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#dc3545' : '#ffc107'};
      color: white;
      padding: 15px 25px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 10000;
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
  window.showLoader = function() {
    const loader = document.createElement('div');
    loader.id = 'loader';
    loader.innerHTML = '<div class="spinner"></div>';
    loader.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    `;
    document.body.appendChild(loader);
  };

  window.hideLoader = function() {
    const loader = document.getElementById('loader');
    if (loader) loader.remove();
  };

  // ===========================
  // Local Storage Helpers
  // ===========================
  window.saveToStorage = function(key, data) {
    try {
      localStorage.setItem(key, JSON.stringify(data));
      return true;
    } catch (e) {
      console.error('Error saving to localStorage:', e);
      return false;
    }
  };

  window.getFromStorage = function(key) {
    try {
      const data = localStorage.getItem(key);
      return data ? JSON.parse(data) : null;
    } catch (e) {
      console.error('Error reading from localStorage:', e);
      return null;
    }
  };

  // ===========================
  // Confirm Dialog
  // ===========================
  window.confirmAction = function(message, callback) {
    if (confirm(message)) {
      if (typeof callback === 'function') {
        callback();
      }
      return true;
    }
    return false;
  };

  // ===========================
  // Print Page Function
  // ===========================
  window.printPage = function() {
    window.print();
  };

  // ===========================
  // Initialize Tooltips (if needed)
  // ===========================
  const tooltipElements = document.querySelectorAll('[data-tooltip]');
  tooltipElements.forEach(element => {
    element.addEventListener('mouseenter', function() {
      const tooltip = document.createElement('div');
      tooltip.className = 'tooltip';
      tooltip.textContent = this.getAttribute('data-tooltip');
      tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
        z-index: 10000;
        pointer-events: none;
      `;
      document.body.appendChild(tooltip);
      
      const rect = this.getBoundingClientRect();
      tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
      tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
      
      this._tooltip = tooltip;
    });
    
    element.addEventListener('mouseleave', function() {
      if (this._tooltip) {
        this._tooltip.remove();
        delete this._tooltip;
      }
    });
  });

  console.log('All event listeners initialized');
});

// ===========================
// Add CSS Animations Dynamically
// ===========================
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(100%);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
  
  .spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
`;
document.head.appendChild(style);