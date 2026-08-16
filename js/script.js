// ========== MAIN APPLICATION ==========
document.addEventListener('DOMContentLoaded', () => {
  // Initialize all functionality
  initTheme();
  initNavigation();
  initForms();
  initAds();
  initStatsAnimation();

  // Close modals when clicking the X button
  document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
      });
    });
  });
});
document.addEventListener('DOMContentLoaded', initPhoneValidation);

document.addEventListener('DOMContentLoaded', () => {
  initPhoneValidation();
  validateEmailField(field);
});

document.addEventListener('DOMContentLoaded', () => {
  initEmailValidation();
  initLinkedInValidation();
  validateLinkedInField(field);

});



// ========== MEMBERSHIP PLANS ==========
const PAYPAL_SUPPORTED_COUNTRIES = ['US', 'GB', 'CA', 'AU', 'DE', 'FR'];
const JOB_SEEKER_PLANS = [
  {
    id: "free-trial",
    name: "Free Trial",
    price: "$0",
    period: "7 days",
    benefits: ["Basic access", "Limited support"]
  },
  {
    id: "starter",
    name: "Starter",
    price: "$9.99",
    period: "per month",
    benefits: ["Premium access", "Email support", "Job matching"]
  },
  {
    id: "pro",
    name: "Pro",
    price: "$19.99",
    period: "per month",
    benefits: ["Full access", "Priority support", "Direct company contact"]
  }
];

const ENTERPRISE_PLANS = [
  {
    id: "starter",
    name: "Starter",
    price: "$29.99",
    period: "per month",
    benefits: ["Post 5 jobs", "Basic analytics", "Email support"]
  },
  {
    id: "business",
    name: "Business",
    price: "$59.99",
    period: "per month",
    benefits: ["Post 20 jobs", "Advanced analytics", "Premium support"]
  },
  {
    id: "enterprise",
    name: "Enterprise",
    price: "$99.99",
    period: "per month",
    benefits: ["Unlimited jobs", "Custom features", "Dedicated support"]
  }
];

// ========== THEME MANAGEMENT ==========
function initTheme() {
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
  
  // Check for saved theme preference or use system preference
  const currentTheme = localStorage.getItem('theme');
  if (currentTheme === 'dark' || (!currentTheme && prefersDarkScheme.matches)) {
    document.body.classList.add('dark-mode');
    // Update icon to sun if in dark mode
    const icon = document.querySelector('#theme-toggle-btn i');
    if (icon) icon.className = 'fas fa-sun';
  }
  
  // Toggle theme
  themeToggleBtn?.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
    localStorage.setItem('theme', theme);
    
    // Update icon
    const icon = document.querySelector('#theme-toggle-btn i');
    if (icon) {
      icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
  });
}

// ========== NAVIGATION ==========
function initNavigation() {
  // Highlight active section on scroll
  const sections = document.querySelectorAll('section');
  const navItems = document.querySelectorAll('.nav-item');

  const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.2,
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        navItems.forEach((navItem) => navItem.classList.remove('active'));
        const targetId = entry.target.getAttribute('id');
        const correspondingNavItem = document.querySelector(`.nav-item[data-section="${targetId}"]`);
        if (correspondingNavItem) {
          correspondingNavItem.classList.add('active');
        }
      }
    });
  }, observerOptions);

  sections.forEach((section) => observer.observe(section));

  // Redirect to Role Selection Page (Create New Account)
  document.getElementById('create-account-btn')?.addEventListener('click', () => {
    document.getElementById('role-selection').style.display = 'block';
    document.getElementById('role-selection').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  // Show Sign In Form
  document.getElementById('sign-in-btn')?.addEventListener('click', () => {
    document.querySelectorAll('.form-container').forEach(form => form.style.display = 'none');
    document.getElementById('sign-in-form').style.display = 'block';
    document.getElementById('forms').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

// ========== FORM MANAGEMENT ==========
function initForms() {
  // Role Selection Logic
  const roleButtons = document.querySelectorAll('.role-btn');
  const nextBtn = document.getElementById('next-btn');
  let selectedRole = null;

  roleButtons.forEach(button => {
    button.addEventListener('click', () => {
      roleButtons.forEach(btn => btn.classList.remove('selected'));
      button.classList.add('selected');
      nextBtn.disabled = false;
      selectedRole = button.getAttribute('data-role');
    });
  });

  nextBtn.addEventListener('click', () => {
    if (selectedRole) {
      document.querySelectorAll('.form-container').forEach(form => form.style.display = 'none');
      document.getElementById(`${selectedRole}-form`).style.display = 'block';
      document.getElementById('forms').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  // Enterprise Form Logic
  initEnterpriseForm();
  
  // Job Seeker Form Logic
  initJobSeekerForm();
  
  // Contact Form Logic
  initContactForm();
  
  // Sign In Form Logic
  initSignInForm();
  
  // Form reset functionality
  setupFormResets();
  
  // Payment modal event listeners
  document.getElementById('continue-to-payment')?.addEventListener('click', goToPaymentStep);
  document.getElementById('back-to-plans')?.addEventListener('click', goToPlanStep);
  document.getElementById('complete-payment')?.addEventListener('click', processPayment);
}

function initEnterpriseForm() {
  const enterpriseForm = document.getElementById('enterprise-form');
  const enterpriseSecondForm = document.getElementById('enterprise-second-form');
  const enterpriseAdsForm = document.getElementById('enterprise-ads-form');
  const yesAdsBtn = document.getElementById('yes-ads-btn');
  const noAdsBtn = document.getElementById('no-ads-btn');
  const submitAdsInfo = document.getElementById('submit-ads-info');
  const workerTypeSelect = document.getElementById('worker-type');
  const otherWorkerContainer = document.getElementById('other-worker-container');

  // Handle other worker type input visibility
  workerTypeSelect?.addEventListener('change', function() {
    const showOther = Array.from(this.selectedOptions).some(opt => opt.value === 'other');
    otherWorkerContainer.style.display = showOther ? 'block' : 'none';
  });

  // Main Enterprise Form Submission
  enterpriseForm?.addEventListener('submit', function(e) {
    // Validation
    if (!validateForm(this)) {
      e.preventDefault();
      return;
    }

    // Password validation
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    if (password !== confirmPassword) {
      document.getElementById('confirm-password').setCustomValidity('Passwords do not match.');
      this.classList.add('was-validated');
      e.preventDefault();
      return;
    }

    // Let the form submit to register.php
    // The backend will handle the database operations and redirect
  });

  // Enterprise - No Ads Path (for backwards compatibility)
  noAdsBtn?.addEventListener('click', function() {
    // This will be handled by register.php
    enterpriseSecondForm.style.display = 'none';
    showFinalMessage('enterprise-final-message-no-ads');
  });

  // Enterprise - Yes Ads Path (for backwards compatibility)
  yesAdsBtn?.addEventListener('click', function() {
    enterpriseSecondForm.style.display = 'none';
    openEnterprisePaymentModal();
    enterpriseAdsForm.style.display = 'block';
    enterpriseAdsForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  // Enterprise - Submit Ads Info (for backwards compatibility)
  submitAdsInfo?.addEventListener('click', function() {
    enterpriseAdsForm.style.display = 'none';
    openEnterprisePaymentModal();
  });
}


function initLinkedInValidation() {
  const linkedinInput = document.getElementById('linkedin');
  if (!linkedinInput) return;

  linkedinInput.addEventListener('input', function() {
    validateLinkedInField(this);
  });

  function validateLinkedInField(field) {
    const linkedinRegex = /^(http(s)?:\/\/)?(www\.)?linkedin\.com\/(in|company)\/[a-zA-Z0-9-]{5,100}\/?$/;
    const isValid = linkedinRegex.test(field.value) || field.value === '';
    
    field.classList.toggle('valid', isValid && field.value.length > 0);
    field.classList.toggle('invalid', !isValid && field.value.length > 0);
  }

  // Validation à la soumission
  const form = linkedinInput.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      validateLinkedInField(linkedinInput);
      if (linkedinInput.value.length > 0 && linkedinInput.classList.contains('invalid')) {
        e.preventDefault();
        linkedinInput.focus();
      }
    });
  }
}

function initEmailValidation() {
  const emailInput = document.getElementById('email');
  if (!emailInput) return;

  emailInput.addEventListener('input', function() {
    validateEmailField(this);
  });

  function validateEmailField(field) {
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const isValid = emailRegex.test(field.value);

    field.classList.toggle('valid', isValid);
    field.classList.toggle('invalid', !isValid && field.value.length > 0);
  }

  // Validation à la soumission
  const form = emailInput.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      validateEmailField(emailInput);
      if (!emailInput.checkValidity() || emailInput.classList.contains('invalid')) {
        e.preventDefault();
        emailInput.focus();
      }
    });
  }
}

function validateEmailField(field) {
  // Expression régulière pour valider l'email
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  
  // Vérifie si le champ est vide ou valide
  const isValid = field.value === '' || emailRegex.test(field.value);
  
  // Met à jour les classes CSS pour indiquer l'état de validation
  field.classList.toggle('valid', isValid && field.value.length > 0);
  field.classList.toggle('invalid', !isValid && field.value.length > 0);
  
  // Retourne true si valide, false sinon
  return isValid;
}

function validatePhoneField(field) {
  // Supprime tous les caractères non numériques
  const cleanedValue = field.value.replace(/\D/g, '');
  
  // Valide si le nombre de chiffres est entre 8 et 10
  const isValid = cleanedValue.length >= 8 && cleanedValue.length <= 10;
  
  // Met à jour les classes CSS pour indiquer l'état de validation
  field.classList.toggle('valid', isValid && cleanedValue.length > 0);
  field.classList.toggle('invalid', !isValid && cleanedValue.length > 0);
  
  // Formatage automatique : 123-456-7890
  let formattedValue = '';
  if (cleanedValue.length > 0) formattedValue = cleanedValue.substring(0, 3);
  if (cleanedValue.length > 3) formattedValue += '-' + cleanedValue.substring(3, 6);
  if (cleanedValue.length > 6) formattedValue += '-' + cleanedValue.substring(6, 10);
  
  // Met à jour la valeur du champ avec le formatage
  field.value = formattedValue;
  
  // Retourne true si valide, false sinon
  return isValid;
}

function initJobSeekerForm() {
  const jobSeekerForm = document.getElementById('job-seeker-form');
  const workerTypeSelect = document.getElementById('worker-type-j');
  const otherWorkerContainer = document.getElementById('other-worker-container-j');
  
  // Handle other worker type input visibility
  workerTypeSelect?.addEventListener('change', function() {
    const showOther = Array.from(this.selectedOptions).some(opt => opt.value === 'other');
    otherWorkerContainer.style.display = showOther ? 'block' : 'none';
  });
  
  // Validation for phone
  document.getElementById('job-seeker-phone')?.addEventListener('input', function() {
    validatePhoneField(this);
  });
  
  // Validation for email
  document.getElementById('job-seeker-email')?.addEventListener('input', function() {
    validateEmailField(this);
  });
  
  // Form submission
  jobSeekerForm?.addEventListener('submit', function(e) {
    // Validation
    if (!validateForm(this)) {
      e.preventDefault();
      return;
    }

    // Password validation
    const password = document.getElementById('job-seeker-password').value;
    const confirmPassword = document.getElementById('job-seeker-confirm-password').value;
    
    if (password !== confirmPassword) {
      document.getElementById('job-seeker-confirm-password').setCustomValidity('Passwords do not match.');
      this.classList.add('was-validated');
      e.preventDefault();
      return;
    }
    
    // Let the form submit to register.php
    // The backend will handle the database operations and redirect
  });
}

function initContactForm() {
  const contactForm = document.getElementById('contact-form');
  
  contactForm?.addEventListener('submit', function(e) {
    // Only validate the form, don't prevent default submission
    if (!validateForm(this)) {
      e.preventDefault();
      return;
    }
    
    // Store form reference for use after submission
    const form = this;
    
    // Show the success message after a short delay
    setTimeout(function() {
      form.style.display = 'none';
      showFinalMessage('contact-final-message');
    }, 500);
    
    // Let the form submit normally to the server
  });
}

function initSignInForm() {
  const signInForm = document.getElementById('sign-in-form');
  
  signInForm?.addEventListener('submit', function(e) {
    // Form is submitted directly to login.php
    // The backend handles login validation and redirection
    if (!validateForm(this)) {
      e.preventDefault();
    }
  });
  
  // Check for login/registration messages in URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('login_error')) {
    const errorMsg = decodeURIComponent(urlParams.get('login_error'));
    alert("Login Error: " + errorMsg);
  }
  
  if (urlParams.has('reg_error')) {
    const errorMsg = decodeURIComponent(urlParams.get('reg_error'));
    alert("Registration Error: " + errorMsg);
  }
  
  if (urlParams.has('reg_success')) {
    alert("Registration successful! You can now login.");
  }
}

function validatePhoneField(field) {
  const value = field.value.replace(/\D/g, '');
  const isValid = value.length >= 8 && value.length <= 10;
  
  field.classList.toggle('valid', isValid);
  field.classList.toggle('invalid', !isValid && value.length > 0);
}

function validateEmailField(field) {
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  const isValid = emailRegex.test(field.value);

  field.classList.toggle('valid', isValid);
  field.classList.toggle('invalid', !isValid && field.value.length > 0);
}

// ========== UTILITY FUNCTIONS ==========
function validateForm(form) {
  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    return false;
  }
  return true;
}

function saveUser(user) {
  // This function is maintained for backwards compatibility
  // New registrations will be handled by the PHP backend instead
  
  const users = JSON.parse(localStorage.getItem('hirehive_users')) || [];
  users.push(user);
  localStorage.setItem('hirehive_users', JSON.stringify(users));
  
  // In the future, this would call the API to create a subscription
}

function showFinalMessage(messageId) {
  const finalMessage = document.getElementById(messageId);
  finalMessage.style.display = 'block';
  finalMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setupFormResets() {
  document.querySelectorAll('[data-form-reset]').forEach(button => {
    button.addEventListener('click', function() {
      const formId = this.getAttribute('data-form-reset');
      const form = document.getElementById(formId);
      if (form) {
        form.reset();
        form.classList.remove('was-validated');
        document.querySelectorAll('.final-message').forEach(msg => {
          msg.style.display = 'none';
        });
        form.style.display = 'block';
      }
    });
  });
}

// ========== ADS MANAGEMENT ==========
function initAds() {
  // Search Ads
  document.getElementById('search-input')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') searchAds();
  });
  // Toggle all ads visibility
  document.getElementById('see-all-ads')?.addEventListener('click', function() {
    const adsSidebar = document.querySelector('.ads-sidebar');
    adsSidebar.classList.toggle('show-all');
    this.textContent = adsSidebar.classList.contains('show-all') ? 'Show fewer ads' : 'See all ads';
  });
}

function searchAds() {
  const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
  const adCards = document.querySelectorAll('.ad-card');
  
  if (searchTerm) {
    let foundMatch = false;
    
    adCards.forEach(card => {
      const enterpriseName = card.querySelector('h3').textContent.toLowerCase();
      if (enterpriseName.includes(searchTerm)) {
        card.style.display = 'block';
        foundMatch = true;
        
        // Scroll to the matching card
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        card.style.display = 'none';
      }
    });
    
    if (!foundMatch) {
      alert(`No enterprises found matching "${searchTerm}"`);
    }
  } else {
    // If search is empty, show all ads
    adCards.forEach(card => {
      card.style.display = 'block';
    });
  }
}


// ========== STATS ANIMATION ==========
function initStatsAnimation() {
  const statNumbers = document.querySelectorAll('.stat-number');
  const observerOptions = {
    threshold: 0.5
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateNumber(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  statNumbers.forEach(number => observer.observe(number));
}

function animateNumber(target) {
  const count = parseInt(target.getAttribute('data-count'));
  const duration = 2000;
  const step = count / (duration / 16);
  let current = 0;
  
  const timer = setInterval(() => {
    current += step;
    if (current >= count) {
      clearInterval(timer);
      current = count;
    }
    target.textContent = Math.floor(current) + (target.getAttribute('data-count').endsWith('%') ? '%' : '');
  }, 16);
}

// ========== PAYMENT MODAL FUNCTIONS ==========
function openPaymentModal(role) {
  const modal = document.getElementById('payment-modal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  
  // Set title based on role
  const title = document.getElementById('payment-modal-title');
  const subtitle = document.getElementById('payment-modal-subtitle');
  
  if (role === 'job-seeker') {
    title.textContent = 'Job Seeker Membership';
    subtitle.textContent = 'Choose your plan to access premium features';
  } else {
    title.textContent = 'Enterprise Membership';
    subtitle.textContent = 'Choose your plan to access recruitment tools';
  }
  
  // Reset modal state
  document.getElementById('step-1').classList.add('active');
  document.getElementById('step-2').classList.remove('active');
  document.getElementById('plan-selection-step').classList.add('active');
  document.getElementById('payment-details-step').classList.remove('active');
  
  // Initialize plan selection
  initPlanSelection(role);
  
  // Set up event listeners
  setupPaymentModalListeners();
}

function closePaymentModal() {
  const modal = document.getElementById('payment-modal');
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
}

function initPlanSelection(role) {
  const planCardsContainer = document.getElementById('plan-cards-container');
  planCardsContainer.innerHTML = '';
  
  const plans = role === 'job-seeker' ? JOB_SEEKER_PLANS : ENTERPRISE_PLANS;
  
  plans.forEach((plan, index) => {
    const planCard = document.createElement('div');
    planCard.className = `plan-card ${index === 1 ? 'selected' : ''}`;
    planCard.dataset.plan = plan.id;
    
    planCard.innerHTML = `
      <div class="plan-top">
        <div class="plan-name">${plan.name}</div>
        <div class="plan-price">${plan.price}</div>
        <div class="plan-period">${plan.period}</div>
      </div>
      <ul class="plan-benefits">
        ${plan.benefits.map(benefit => `<li>${benefit}</li>`).join('')}
      </ul>
    `;
    
    planCard.addEventListener('click', function() {
      document.querySelectorAll('.plan-card').forEach(card => card.classList.remove('selected'));
      this.classList.add('selected');
    });
    
    planCardsContainer.appendChild(planCard);
  });
  document.querySelector('.btn-secondary[onclick="closePaymentModal()"]').classList.add('btn-cancel');
}

function setupPaymentModalListeners() {
  // Close modal when clicking X
  document.querySelector('.close-modal').addEventListener('click', closePaymentModal);
  
  // Payment method selection
  document.querySelectorAll('.payment-method').forEach(method => {
    method.addEventListener('click', function() {
      document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
      this.classList.add('active');
    });
  });
  
  // Country selection
  document.getElementById('country').addEventListener('change', checkPayPalAvailability);
}

function goToPaymentStep() {
  // Validate that a plan is selected
  const selectedPlan = document.querySelector('.plan-card.selected');
  if (!selectedPlan) {
    alert('Please select a plan before continuing');
    return;
  }
  
  // Update steps
  document.getElementById('step-1').classList.remove('active');
  document.getElementById('step-2').classList.add('active');
  
  // Update content
  document.getElementById('plan-selection-step').classList.remove('active');
  document.getElementById('payment-details-step').classList.add('active');
  
  // Initialize country selection
  checkPayPalAvailability();
}

function goToPlanStep() {
  // Update steps
  document.getElementById('step-1').classList.add('active');
  document.getElementById('step-2').classList.remove('active');
  
  // Update content
  document.getElementById('plan-selection-step').classList.add('active');
  document.getElementById('payment-details-step').classList.remove('active');
}

function checkPayPalAvailability() {
  const countrySelect = document.getElementById('country');
  const locationWarning = document.getElementById('location-warning');
  const paypalMethod = document.querySelector('.payment-method[data-method="paypal"]');
  
  if (!countrySelect.value || PAYPAL_SUPPORTED_COUNTRIES.includes(countrySelect.value)) {
    locationWarning.style.display = 'none';
    if (paypalMethod) paypalMethod.style.display = 'flex';
  } else {
    locationWarning.style.display = 'block';
    if (paypalMethod) paypalMethod.style.display = 'none';
    document.querySelector('.payment-method[data-method="visa"]').classList.add('active');
  }
}

function processPayment(e) {
  e.preventDefault();
  
  // Get selected plan
  const selectedPlanCard = document.querySelector('.plan-card.selected');
  if (!selectedPlanCard) {
    alert('Please select a plan');
    return;
  }
  
  const selectedPlan = selectedPlanCard.dataset.plan;
  const planName = selectedPlanCard.querySelector('.plan-name').textContent;
  
  // For free trial/basic, no payment validation needed
  if (selectedPlan === 'free-trial' || selectedPlan === 'starter') {
    alert(selectedPlan === 'free-trial' ? 'Free trial activated! You will not be charged.' : `${planName} plan activated!`);
    closePaymentModal();
    
    // Here you would typically call an API to create a subscription
    // For now, we'll just show a success message
    
    return;
  }
  
  // Validate form for paid plans
  const paymentMethod = document.querySelector('.payment-method.active')?.dataset.method;
  
  if (paymentMethod === 'visa') {
    const cardNumber = document.getElementById('card-number').value;
    const cardName = document.getElementById('card-name').value;
    const expiryDate = document.getElementById('expiry-date').value;
    const cvv = document.getElementById('cvv').value;
    
    if (!cardNumber || !cardName || !expiryDate || !cvv) {
      alert('Please fill in all payment details');
      return;
    }
    
    // Here, we would typically call a payment processor API
    // But for demonstration, we'll just show a success message
  }
  
  // Process payment
  alert(`Payment processed successfully for ${planName} plan via ${paymentMethod === 'paypal' ? 'PayPal' : 'Visa'}!`);
  closePaymentModal();
  
  // In a real implementation, this would submit to create-subscription.php
  // which would validate the payment and create a subscription record
  
  // Show appropriate final message based on user role
  const tempUser = sessionStorage.getItem('temp_enterprise_user');
  const role = tempUser ? 'enterprise' : 'job-seeker';
  showFinalMessage(`${role}-final-message${role === 'enterprise' ? '-ads' : ''}`);
}

// Update the existing payment modal opening functions
function openJobSeekerPaymentModal() {
  openPaymentModal('job-seeker');
}

function openEnterprisePaymentModal() {
  openPaymentModal('enterprise');
}

document.addEventListener('DOMContentLoaded', () => {
  const backToTopButton = document.querySelector('.back-to-top');
  
  // Show/hide the button based on scroll position
  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
      backToTopButton.classList.add('visible');
    } else {
      backToTopButton.classList.remove('visible');
    }
  });
  
  // Scroll to top when clicked
  backToTopButton.addEventListener('click', () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
});

function initPhoneValidation() {
  const phoneInput = document.getElementById('phone');
  if (!phoneInput) return;

  phoneInput.addEventListener('input', function() {
    // Nettoyage et formatage
    let value = this.value.replace(/\D/g, '');
    let formatted = '';
    
    // Formatage : 123-456-7890
    if (value.length > 0) formatted = value.substring(0, 3);
    if (value.length > 3) formatted += '-' + value.substring(3, 6);
    if (value.length > 6) formatted += '-' + value.substring(6, 10);
    
    this.value = formatted;

    // Validation : 8 à 10 chiffres
    const digitCount = value.length;
    const isValid = digitCount >= 8 && digitCount <= 10;
    
    this.classList.toggle('valid', isValid);
    this.classList.toggle('invalid', !isValid && value.length > 0);
  });

  // Validation à la soumission
  const form = phoneInput.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      const digitCount = phoneInput.value.replace(/\D/g, '').length;
      if (digitCount < 8 || digitCount > 10) {
        phoneInput.classList.add('invalid');
        phoneInput.focus();
        e.preventDefault();
      }
    });
  }
}

// Payment Form Validation
document.addEventListener('DOMContentLoaded', function() {
  // Elements
  const paymentForm = document.getElementById('payment-details-step');
  const cardNumber = document.getElementById('card-number');
  const cardName = document.getElementById('card-name');
  const expiryDate = document.getElementById('expiry-date');
  const cvv = document.getElementById('cvv');
  const country = document.getElementById('country');
  const completePaymentBtn = document.getElementById('complete-payment');

  // Validation functions
  function validateCardNumber(number) {
    // Remove all non-digit characters
    const cleaned = number.replace(/\D/g, '');
    // Basic check for card length (13-19 digits) and Luhn algorithm
    return cleaned.length >= 13 && 
           cleaned.length <= 19 && 
           luhnCheck(cleaned);
  }

  function luhnCheck(value) {
    let sum = 0;
    for (let i = 0; i < value.length; i++) {
      let digit = parseInt(value[i]);
      if ((value.length - i) % 2 === 0) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }
      sum += digit;
    }
    return sum % 10 === 0;
  }

  function validateExpiryDate(date) {
    const match = date.match(/^(\d{2})\/(\d{2})$/);
    if (!match) return false;
    
    const month = parseInt(match[1]);
    const year = parseInt(match[2]);
    const currentYear = new Date().getFullYear() % 100;
    const currentMonth = new Date().getMonth() + 1;
    
    // Check if month is valid (1-12)
    if (month < 1 || month > 12) return false;
    
    // Check if year is not in the past
    if (year < currentYear) return false;
    
    // If current year, check if month is not in the past
    if (year === currentYear && month < currentMonth) return false;
    
    return true;
  }

  function validateCVV(cvv) {
    return /^\d{3,4}$/.test(cvv);
  }

  function validateCardName(name) {
    return name.trim().length >= 3 && /^[a-zA-Z\s]+$/.test(name);
  }

  // Event listeners for real-time validation
  cardNumber.addEventListener('input', function() {
    // Format as 4-digit groups
    const value = this.value.replace(/\D/g, '');
    let formatted = '';
    for (let i = 0; i < value.length; i++) {
      if (i > 0 && i % 4 === 0) formatted += ' ';
      formatted += value[i];
    }
    this.value = formatted;
    
    // Validation
    if (validateCardNumber(this.value)) {
      this.classList.remove('invalid');
      this.classList.add('valid');
    } else {
      this.classList.remove('valid');
      this.classList.add('invalid');
    }
  });

  cardName.addEventListener('input', function() {
    if (validateCardName(this.value)) {
      this.classList.remove('invalid');
      this.classList.add('valid');
    } else {
      this.classList.remove('valid');
      this.classList.add('invalid');
    }
  });

  expiryDate.addEventListener('input', function() {
    // Auto-insert slash after MM
    const value = this.value.replace(/\D/g, '');
    if (value.length > 2) {
      this.value = `${value.substring(0, 2)}/${value.substring(2, 4)}`;
    }
    
    if (validateExpiryDate(this.value)) {
      this.classList.remove('invalid');
      this.classList.add('valid');
    } else {
      this.classList.remove('valid');
      this.classList.add('invalid');
    }
  });

  cvv.addEventListener('input', function() {
    if (validateCVV(this.value)) {
      this.classList.remove('invalid');
      this.classList.add('valid');
    } else {
      this.classList.remove('valid');
      this.classList.add('invalid');
    }
  });

  country.addEventListener('change', function() {
    if (this.value) {
      this.classList.remove('invalid');
      this.classList.add('valid');
    } else {
      this.classList.remove('valid');
      this.classList.add('invalid');
    }
  });

  // Form submission validation
  completePaymentBtn.addEventListener('click', function(e) {
    e.preventDefault();
    let isValid = true;

    // Validate all fields
    if (!validateCardNumber(cardNumber.value)) {
      cardNumber.classList.add('invalid');
      isValid = false;
    }

    if (!validateCardName(cardName.value)) {
      cardName.classList.add('invalid');
      isValid = false;
    }

    if (!validateExpiryDate(expiryDate.value)) {
      expiryDate.classList.add('invalid');
      isValid = false;
    }

    if (!validateCVV(cvv.value)) {
      cvv.classList.add('invalid');
      isValid = false;
    }

    if (!country.value) {
      country.classList.add('invalid');
      isValid = false;
    }

    if (isValid) {
      // Process payment here
      alert('Payment processed successfully!');
      // You would typically submit the form or call your payment API here
    } else {
      alert('Please correct the errors in the form.');
    }
  });
});