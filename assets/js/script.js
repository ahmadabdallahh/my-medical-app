// ملف JavaScript الرئيسي - النسخة المحسنة
// Main JavaScript File - Enhanced Version

document.addEventListener('DOMContentLoaded', function () {
  // Initialize all components
  initializeMobileMenu();
  initializeSmoothScrolling();
  initializeHeaderScroll();
  initializeAnimations();
  initializeFormValidation();
  initializeSearchFunctionality();
  initializeAppointmentBooking();
  initializeDatePickers();
  initializeTimeSlots();
  initializeFilters();
  initializeRatingSystem();
  initializeLoadingStates();
  initializeModals();
  initializeNotifications();
  initializeBackToTop();
  initializeParallaxEffects();
  initializeLazyLoading();
  initializePerformanceOptimizations();
});

// Mobile Menu Toggle with Enhanced Animation
function initializeMobileMenu() {
  const hamburger = document.querySelector('.hamburger');
  const navMenu = document.querySelector('.nav-menu');

  if (hamburger && navMenu) {
    hamburger.addEventListener('click', function () {
      hamburger.classList.toggle('active');
      navMenu.classList.toggle('active');

      // Add slide animation
      if (navMenu.classList.contains('active')) {
        navMenu.style.display = 'flex';
        navMenu.style.animation = 'slideInDown 0.3s ease forwards';
      } else {
        navMenu.style.animation = 'slideOutUp 0.3s ease forwards';
        setTimeout(() => {
          navMenu.style.display = 'none';
        }, 300);
      }
    });
  }
}

// Smooth Scrolling with Enhanced Performance
function initializeSmoothScrolling() {
  const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

  navLinks.forEach((link) => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = this.getAttribute('href');
      const targetSection = document.querySelector(targetId);

      if (targetSection) {
        const headerHeight = document.querySelector('.header').offsetHeight;
        const targetPosition = targetSection.offsetTop - headerHeight;

        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth',
        });
      }
    });
  });
}

// Header Background on Scroll with Performance Optimization
function initializeHeaderScroll() {
  const header = document.querySelector('.header');
  let ticking = false;

  function updateHeader() {
    if (window.scrollY > 100) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      requestAnimationFrame(updateHeader);
      ticking = true;
    }
  });
}

// Enhanced Animation on Scroll with Intersection Observer
function initializeAnimations() {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px',
  };

  const observer = new IntersectionObserver(function (entries) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('fade-in');

        // Add staggered animation for grid items
        if (
          entry.target.classList.contains('feature-card') ||
          entry.target.classList.contains('hospital-card') ||
          entry.target.classList.contains('specialty-card')
        ) {
          const delay =
            Array.from(entry.target.parentNode.children).indexOf(entry.target) *
            0.1;
          entry.target.style.animationDelay = `${delay}s`;
        }
      }
    });
  }, observerOptions);

  // Observe elements for animation
  const animateElements = document.querySelectorAll(
    '.feature-card, .hospital-card, .specialty-card, .stat, .contact-item'
  );
  animateElements.forEach((el) => {
    observer.observe(el);
  });
}

// Enhanced Form Validation with Real-time Feedback
function initializeFormValidation() {
  const forms = document.querySelectorAll('form');

  forms.forEach((form) => {
    const inputs = form.querySelectorAll('input, textarea, select');

    // Real-time validation
    inputs.forEach((input) => {
      input.addEventListener('blur', function () {
        validateField(this);
      });

      input.addEventListener('input', function () {
        if (this.classList.contains('error')) {
          validateField(this);
        }
      });
    });

    // Form submission validation
    form.addEventListener('submit', function (e) {
      const requiredFields = form.querySelectorAll('[required]');
      let isValid = true;

      requiredFields.forEach((field) => {
        if (!validateField(field)) {
          isValid = false;
        }
      });

      if (!isValid) {
        e.preventDefault();
        showMessage('يرجى ملء جميع الحقول المطلوبة بشكل صحيح', 'error');
      }
    });
  });
}

function validateField(field) {
  const value = field.value.trim();
  let isValid = true;

  // Remove previous error states
  field.classList.remove('error', 'success');

  // Required field validation
  if (field.hasAttribute('required') && !value) {
    field.classList.add('error');
    isValid = false;
  }

  // Email validation
  if (field.type === 'email' && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      field.classList.add('error');
      isValid = false;
    }
  }

  // Phone validation
  if (field.type === 'tel' && value) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    if (!phoneRegex.test(value)) {
      field.classList.add('error');
      isValid = false;
    }
  }

  // Password validation - فقط 6 أحرف على الأقل
  if (field.type === 'password' && value) {
    if (value.length < 6) {
      field.classList.add('error');
      isValid = false;
    } else {
      // لا نعرض مؤشر القوة، فقط نتحقق من الحد الأدنى
      const strength = calculatePasswordStrength(value);
      // يمكن إزالة updatePasswordStrengthIndicator إذا لم تكن ضرورية
      // updatePasswordStrengthIndicator(field, strength);
    }
  }

  // Add success state for valid fields
  if (isValid && value) {
    field.classList.add('success');
  }

  return isValid;
}

function calculatePasswordStrength(password) {
  // متطلبات بسيطة: فقط 6 أحرف على الأقل
  if (password.length < 6) {
    return 0; // ضعيف جداً
  }

  // إذا كانت 6 أحرف أو أكثر، تعتبر مقبولة
  return 3; // متوسط (مقبول)
}

function updatePasswordStrengthIndicator(field, strength) {
  const container = field.parentElement;
  let indicator = container.querySelector('.password-strength');

  if (!indicator) {
    indicator = document.createElement('div');
    indicator.className = 'password-strength';
    container.appendChild(indicator);
  }

  const strengthText = ['ضعيف جداً', 'ضعيف', 'متوسط', 'قوي', 'قوي جداً'];
  const strengthColors = [
    '#dc2626',
    '#f59e0b',
    '#fbbf24',
    '#10b981',
    '#059669',
  ];

  indicator.textContent = strengthText[strength - 1] || '';
  indicator.style.color = strengthColors[strength - 1] || '#6b7280';
}

// Enhanced Search Functionality
function initializeSearchFunctionality() {
  const searchForm = document.getElementById('search-form');
  if (searchForm) {
    const searchInput = searchForm.querySelector('input[name="search"]');

    // Debounced search
    let searchTimeout;
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        performSearch(this.value);
      }, 300);
    });

    searchForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const searchTerm = searchInput.value.trim();

      if (searchTerm) {
        window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
      }
    });
  }
}

function performSearch(query) {
  // Implement live search functionality here
  console.log('Searching for:', query);
}

// Enhanced Appointment Booking
function initializeAppointmentBooking() {
  const bookButtons = document.querySelectorAll('.book-appointment');

  bookButtons.forEach((button) => {
    button.addEventListener('click', function (e) {
      e.preventDefault();

      // Add loading state
      const originalText = this.innerHTML;
      this.innerHTML = '<span class="loading"></span> جاري التحميل...';
      this.disabled = true;

      const doctorId = this.dataset.doctorId;
      const clinicId = this.dataset.clinicId;

      // Simulate API call
      setTimeout(() => {
        if (doctorId && clinicId) {
          window.location.href = `book.php?doctor=${doctorId}&clinic=${clinicId}`;
        } else {
          this.innerHTML = originalText;
          this.disabled = false;
          showMessage('حدث خطأ في تحميل البيانات', 'error');
        }
      }, 1000);
    });
  });
}

// Enhanced Date Picker
function initializeDatePickers() {
  const dateInputs = document.querySelectorAll('input[type="date"]');

  dateInputs.forEach((input) => {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    input.setAttribute('min', today);

    // Prevent selecting past dates
    input.addEventListener('change', function () {
      const selectedDate = new Date(this.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (selectedDate < today) {
        this.value = today.toISOString().split('T')[0];
        showMessage('لا يمكن اختيار تاريخ في الماضي', 'warning');
      }
    });
  });
}

// Enhanced Time Slot Selection
function initializeTimeSlots() {
  const timeSlots = document.querySelectorAll('.time-slot');

  timeSlots.forEach((slot) => {
    slot.addEventListener('click', function () {
      // Remove active class from all slots
      timeSlots.forEach((s) => s.classList.remove('active'));

      // Add active class to clicked slot
      this.classList.add('active');

      // Update hidden input
      const timeInput = document.getElementById('selected-time');
      if (timeInput) {
        timeInput.value = this.dataset.time;
      }

      // Add ripple effect
      createRippleEffect(this, event);
    });
  });
}

function createRippleEffect(element, event) {
  const ripple = document.createElement('span');
  const rect = element.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x = event.clientX - rect.left - size / 2;
  const y = event.clientY - rect.top - size / 2;

  ripple.style.width = ripple.style.height = size + 'px';
  ripple.style.left = x + 'px';
  ripple.style.top = y + 'px';
  ripple.classList.add('ripple');

  element.appendChild(ripple);

  setTimeout(() => {
    ripple.remove();
  }, 600);
}

// Enhanced Filters
function initializeFilters() {
  const specialtyFilter = document.getElementById('specialty-filter');
  const hospitalFilter = document.getElementById('hospital-filter');

  if (specialtyFilter) {
    specialtyFilter.addEventListener('change', function () {
      filterDoctors(this.value);
    });
  }

  if (hospitalFilter) {
    hospitalFilter.addEventListener('change', function () {
      filterClinics(this.value);
    });
  }
}

function filterDoctors(specialtyId) {
  const doctorCards = document.querySelectorAll('.doctor-card');

  doctorCards.forEach((card) => {
    const cardSpecialtyId = card.dataset.specialtyId;

    if (specialtyId === '' || cardSpecialtyId === specialtyId) {
      card.style.display = 'block';
      card.style.animation = 'fadeIn 0.5s ease';
    } else {
      card.style.display = 'none';
    }
  });
}

function filterClinics(hospitalId) {
  const clinicCards = document.querySelectorAll('.clinic-card');

  clinicCards.forEach((card) => {
    const cardHospitalId = card.dataset.hospitalId;

    if (hospitalId === '' || cardHospitalId === hospitalId) {
      card.style.display = 'block';
      card.style.animation = 'fadeIn 0.5s ease';
    } else {
      card.style.display = 'none';
    }
  });
}

// Enhanced Rating System
function initializeRatingSystem() {
  const ratingStars = document.querySelectorAll('.rating-stars .star');

  ratingStars.forEach((star, index) => {
    star.addEventListener('click', function () {
      const rating = index + 1;
      setRating(rating);
    });

    star.addEventListener('mouseenter', function () {
      const rating = index + 1;
      highlightStars(rating);
    });
  });

  const ratingContainer = document.querySelector('.rating-stars');
  if (ratingContainer) {
    ratingContainer.addEventListener('mouseleave', function () {
      const currentRating = this.dataset.rating || 0;
      highlightStars(currentRating);
    });
  }
}

function setRating(rating) {
  const ratingContainer = document.querySelector('.rating-stars');
  const stars = ratingContainer.querySelectorAll('.star');

  ratingContainer.dataset.rating = rating;

  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.add('active');
      star.style.animation = 'starGlow 0.3s ease';
    } else {
      star.classList.remove('active');
    }
  });

  // Update hidden input
  const ratingInput = document.getElementById('rating-input');
  if (ratingInput) {
    ratingInput.value = rating;
  }
}

function highlightStars(rating) {
  const stars = document.querySelectorAll('.rating-stars .star');

  stars.forEach((star, index) => {
    if (index < rating) {
      star.classList.add('active');
    } else {
      star.classList.remove('active');
    }
  });
}

// Enhanced Loading States
function initializeLoadingStates() {
  const loadingButtons = document.querySelectorAll('.btn[data-loading]');

  loadingButtons.forEach((button) => {
    button.addEventListener('click', function () {
      const originalText = this.innerHTML;
      this.innerHTML = '<span class="loading"></span> جاري التحميل...';
      this.disabled = true;

      // Re-enable after 3 seconds (for demo)
      setTimeout(() => {
        this.innerHTML = originalText;
        this.disabled = false;
      }, 3000);
    });
  });
}

// Enhanced Modal System
function initializeModals() {
  const modalTriggers = document.querySelectorAll('[data-modal]');
  const modals = document.querySelectorAll('.modal');
  const modalCloses = document.querySelectorAll('.modal-close, .modal-overlay');

  modalTriggers.forEach((trigger) => {
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      const modalId = this.dataset.modal;
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Add entrance animation
        modal.style.animation = 'modalSlideIn 0.3s ease';
      }
    });
  });

  modalCloses.forEach((close) => {
    close.addEventListener('click', function () {
      modals.forEach((modal) => {
        modal.classList.remove('active');
      });
      document.body.style.overflow = 'auto';
    });
  });

  // Close modal on escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      modals.forEach((modal) => {
        modal.classList.remove('active');
      });
      document.body.style.overflow = 'auto';
    }
  });
}

// Enhanced Notification System
function initializeNotifications() {
  const notifications = document.querySelectorAll('.notification');

  notifications.forEach((notification) => {
    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        notification.style.animation = 'slideOutUp 0.3s ease';
        setTimeout(() => {
          notification.remove();
        }, 300);
      });
    }

    // Auto remove after 5 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.style.animation = 'slideOutUp 0.3s ease';
        setTimeout(() => {
          notification.remove();
        }, 300);
      }
    }, 5000);
  });
}

// Enhanced Back to Top Button
function initializeBackToTop() {
  const backToTopBtn = document.querySelector('.back-to-top');
  if (backToTopBtn) {
    let ticking = false;

    function updateBackToTop() {
      if (window.scrollY > 300) {
        backToTopBtn.classList.add('visible');
      } else {
        backToTopBtn.classList.remove('visible');
      }
      ticking = false;
    }

    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(updateBackToTop);
        ticking = true;
      }
    });

    backToTopBtn.addEventListener('click', function () {
      window.scrollTo({
        top: 0,
        behavior: 'smooth',
      });
    });
  }
}

// Parallax Effects
function initializeParallaxEffects() {
  const parallaxElements = document.querySelectorAll('.parallax');

  window.addEventListener('scroll', function () {
    const scrolled = window.pageYOffset;

    parallaxElements.forEach((element) => {
      const speed = element.dataset.speed || 0.5;
      const yPos = -(scrolled * speed);
      element.style.transform = `translateY(${yPos}px)`;
    });
  });
}

// Lazy Loading for Images
function initializeLazyLoading() {
  const images = document.querySelectorAll('img[data-src]');

  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target;
        img.src = img.dataset.src;
        img.classList.remove('lazy');
        imageObserver.unobserve(img);
      }
    });
  });

  images.forEach((img) => imageObserver.observe(img));
}

// Performance Optimizations
function initializePerformanceOptimizations() {
  // Preload critical resources
  const criticalResources = ['assets/css/style.css', 'assets/js/script.js'];

  criticalResources.forEach((resource) => {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = resource;
    link.as = resource.endsWith('.css') ? 'style' : 'script';
    document.head.appendChild(link);
  });

  // Optimize scroll events
  let scrollTimeout;
  window.addEventListener('scroll', function () {
    if (scrollTimeout) {
      clearTimeout(scrollTimeout);
    }
    scrollTimeout = setTimeout(() => {
      // Perform scroll-based operations here
    }, 16); // ~60fps
  });
}

// Utility Functions
function showMessage(message, type = 'info') {
  const messageDiv = document.createElement('div');
  messageDiv.className = `message ${type}`;
  messageDiv.innerHTML = `
        <i class="fas fa-${getMessageIcon(type)}"></i>
        <span>${message}</span>
        <button class="message-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

  // Insert at the top of the body
  document.body.insertBefore(messageDiv, document.body.firstChild);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (messageDiv.parentNode) {
      messageDiv.style.animation = 'slideOutUp 0.3s ease';
      setTimeout(() => {
        messageDiv.remove();
      }, 300);
    }
  }, 5000);
}

function getMessageIcon(type) {
  const icons = {
    success: 'check-circle',
    error: 'exclamation-circle',
    warning: 'exclamation-triangle',
    info: 'info-circle',
  };
  return icons[type] || 'info-circle';
}

// AJAX Functions with Enhanced Error Handling
function loadDoctors(specialtyId = '', hospitalId = '') {
  const doctorsContainer = document.getElementById('doctors-container');
  if (!doctorsContainer) return;

  doctorsContainer.innerHTML =
    '<div class="loading-container"><div class="loading"></div><p>جاري التحميل...</p></div>';

  const params = new URLSearchParams();
  if (specialtyId) params.append('specialty', specialtyId);
  if (hospitalId) params.append('hospital', hospitalId);

  fetch(`api/doctors.php?${params.toString()}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        displayDoctors(data.doctors);
      } else {
        showMessage(data.message || 'حدث خطأ في تحميل البيانات', 'error');
      }
    })
    .catch((error) => {
      console.error('Error:', error);
      showMessage('حدث خطأ في الاتصال بالخادم', 'error');
    });
}

function displayDoctors(doctors) {
  const container = document.getElementById('doctors-container');
  if (!container) return;

  if (doctors.length === 0) {
    container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-user-md"></i>
                <h3>لا توجد نتائج</h3>
                <p>جرب البحث بكلمات مختلفة أو اختر تخصص آخر</p>
            </div>
        `;
    return;
  }

  const doctorsHTML = doctors
    .map(
      (doctor) => `
        <div class="doctor-card" data-specialty-id="${doctor.specialty_id}">
            <div class="doctor-image">
                <img src="${
                  doctor.image || 'assets/images/default-doctor.jpg'
                }" alt="${doctor.full_name}" loading="lazy">
                <div class="doctor-badge">${doctor.specialty_name}</div>
            </div>
            <div class="doctor-content">
                <h3 class="doctor-name">${doctor.full_name}</h3>
                <p class="doctor-specialty">${doctor.specialty_name}</p>
                <p class="doctor-clinic">
                    <i class="fas fa-hospital"></i>
                    ${doctor.hospital_name} - ${doctor.clinic_name}
                </p>
                <div class="doctor-rating">
                    <div class="stars">
                        ${generateStars(doctor.rating)}
                    </div>
                    <span>${doctor.rating}/5</span>
                </div>
                <div class="doctor-actions">
                    <a href="doctor.php?id=${
                      doctor.id
                    }" class="btn btn-outline btn-small">
                        <i class="fas fa-eye"></i>
                        عرض التفاصيل
                    </a>
                    <a href="book.php?doctor=${doctor.id}&clinic=${
        doctor.clinic_id
      }" class="btn btn-primary btn-small">
                        <i class="fas fa-calendar-plus"></i>
                        احجز موعد
                    </a>
                </div>
            </div>
        </div>
    `
    )
    .join('');

  container.innerHTML = doctorsHTML;
}

function generateStars(rating) {
  let stars = '';
  for (let i = 1; i <= 5; i++) {
    stars += `<i class="fas fa-star ${i <= rating ? 'active' : ''}"></i>`;
  }
  return stars;
}

// Enhanced Form Submission with AJAX
function submitForm(formId, successCallback = null) {
  const form = document.getElementById(formId);
  if (!form) return;

  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.innerHTML;

  submitBtn.innerHTML = '<span class="loading"></span> جاري الإرسال...';
  submitBtn.disabled = true;

  fetch(form.action, {
    method: 'POST',
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showMessage(data.message || 'تم الإرسال بنجاح', 'success');
        if (successCallback) successCallback(data);
      } else {
        showMessage(data.message || 'حدث خطأ', 'error');
      }
    })
    .catch((error) => {
      console.error('Error:', error);
      showMessage('حدث خطأ في الاتصال بالخادم', 'error');
    })
    .finally(() => {
      submitBtn.innerHTML = originalText;
      submitBtn.disabled = false;
    });
}

// Local Storage Functions with Error Handling
function saveToLocalStorage(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value));
    return true;
  } catch (error) {
    console.error('Error saving to localStorage:', error);
    return false;
  }
}

function getFromLocalStorage(key) {
  try {
    const item = localStorage.getItem(key);
    return item ? JSON.parse(item) : null;
  } catch (error) {
    console.error('Error reading from localStorage:', error);
    return null;
  }
}

// Theme Toggle with Enhanced UI
function toggleTheme() {
  const body = document.body;
  const currentTheme = body.dataset.theme || 'light';
  const newTheme = currentTheme === 'light' ? 'dark' : 'light';

  body.dataset.theme = newTheme;
  saveToLocalStorage('theme', newTheme);

  // Add transition effect
  body.style.transition = 'background-color 0.3s ease, color 0.3s ease';

  // Update theme-specific elements
  updateThemeElements(newTheme);
}

function updateThemeElements(theme) {
  // Update theme-specific elements here
  const themeToggle = document.querySelector('.theme-toggle');
  if (themeToggle) {
    themeToggle.innerHTML =
      theme === 'light'
        ? '<i class="fas fa-moon"></i>'
        : '<i class="fas fa-sun"></i>';
  }
}

// Initialize theme on page load
const savedTheme = getFromLocalStorage('theme');
if (savedTheme) {
  document.body.dataset.theme = savedTheme;
  updateThemeElements(savedTheme);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes slideOutUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .loading-container {
        text-align: center;
        padding: 2rem;
    }

    .loading-container p {
        margin-top: 1rem;
        color: var(--text-secondary);
    }

    .password-strength {
        font-size: 0.85rem;
        margin-top: 0.5rem;
        font-weight: 500;
    }

    .form-group input.success,
    .form-group textarea.success {
        border-color: var(--success);
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }

    .form-group input.error,
    .form-group textarea.error {
        border-color: var(--error);
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }
`;
document.head.appendChild(style);
