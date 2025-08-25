// Johnny Depp Portfolio Website - JavaScript
// Handles navigation, animations, portfolio filtering, and form interactions

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== GLOBAL VARIABLES =====
    const navbar = document.getElementById('navbar');
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    const contactForm = document.getElementById('contactForm');
    const formMessages = document.getElementById('form-messages');
    
    // ===== NAVIGATION FUNCTIONALITY =====
    
    // Mobile navigation toggle
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
        });
        
        // Close mobile menu when clicking on a link
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Navbar scroll effect
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // Active navigation link highlighting
    function updateActiveNav() {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');
        
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (window.scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', updateActiveNav);
    
    // ===== SMOOTH SCROLLING =====
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Scroll indicator click handler
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', function() {
            const nextSection = document.querySelector('.featured') || document.querySelector('section:nth-of-type(2)');
            if (nextSection) {
                const offsetTop = nextSection.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // ===== PORTFOLIO FILTERING =====
    
    const filterBtns = document.querySelectorAll('.filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    
    if (filterBtns.length > 0 && portfolioItems.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active filter button
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter portfolio items
                portfolioItems.forEach(item => {
                    if (filter === 'all' || item.classList.contains(filter)) {
                        item.style.display = 'block';
                        item.style.animation = 'fadeInUp 0.6s ease forwards';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    }
    
    // ===== INTERSECTION OBSERVER FOR ANIMATIONS =====
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                
                // Special handling for timeline items
                if (entry.target.classList.contains('timeline-item')) {
                    const isOdd = Array.from(entry.target.parentNode.children).indexOf(entry.target) % 2 === 0;
                    entry.target.classList.add(isOdd ? 'slide-in-left' : 'slide-in-right');
                }
                
                // Special handling for stats
                if (entry.target.classList.contains('stat-number')) {
                    animateNumber(entry.target);
                }
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animatedElements = document.querySelectorAll(
        '.featured-item, .news-card, .post-card, .philosophy-item, ' +
        '.award-item, .timeline-item, .portfolio-item, .category-card, ' +
        '.contact-card, .faq-item, .stat-item'
    );
    
    animatedElements.forEach(el => observer.observe(el));
    
    // ===== NUMBER ANIMATION =====
    
    function animateNumber(element) {
        const target = parseInt(element.textContent) || 0;
        const increment = target / 30; // Animation duration
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target + (element.textContent.includes('+') ? '+' : '');
                clearInterval(timer);
            } else {
                element.textContent = Math.ceil(current) + (element.textContent.includes('+') ? '+' : '');
            }
        }, 50);
    }
    
    // ===== CONTACT FORM HANDLING =====
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = contactForm.querySelector('.btn-submit');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            // Clear previous messages
            if (formMessages) {
                formMessages.style.display = 'none';
                formMessages.className = 'form-messages';
            }
            
            // Get form data
            const formData = new FormData(contactForm);
            
            // Client-side validation
            if (!validateForm(formData)) {
                resetSubmitButton();
                return;
            }
            
            // Submit form via AJAX
            fetch('php/contact-form.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFormMessage('success', data.message || 'Thank you for your message. We will get back to you soon!');
                    contactForm.reset();
                } else {
                    showFormMessage('error', data.message || 'There was an error sending your message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFormMessage('error', 'There was an error sending your message. Please try again.');
            })
            .finally(() => {
                resetSubmitButton();
            });
        });
    }
    
    // Form validation
    function validateForm(formData) {
        const requiredFields = ['firstName', 'lastName', 'email', 'inquiryType', 'subject', 'message'];
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        for (let field of requiredFields) {
            const value = formData.get(field);
            if (!value || value.trim() === '') {
                showFormMessage('error', `Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()} field.`);
                return false;
            }
        }
        
        // Validate email
        const email = formData.get('email');
        if (!emailRegex.test(email)) {
            showFormMessage('error', 'Please enter a valid email address.');
            return false;
        }
        
        // Check privacy policy agreement
        if (!formData.get('privacy')) {
            showFormMessage('error', 'Please agree to the privacy policy and terms of service.');
            return false;
        }
        
        return true;
    }
    
    // Show form messages
    function showFormMessage(type, message) {
        if (formMessages) {
            formMessages.textContent = message;
            formMessages.className = `form-messages ${type}`;
            formMessages.style.display = 'block';
            
            // Scroll to message
            formMessages.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    formMessages.style.display = 'none';
                }, 5000);
            }
        }
    }
    
    // Reset submit button
    function resetSubmitButton() {
        const submitBtn = contactForm.querySelector('.btn-submit');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        btnText.style.display = 'inline-block';
        btnLoading.style.display = 'none';
        submitBtn.disabled = false;
    }
    
    // ===== NEWSLETTER FORM =====
    
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const submitBtn = this.querySelector('.btn');
            const originalText = submitBtn.textContent;
            
            // Simple email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            // Show loading state
            submitBtn.textContent = 'Subscribing...';
            submitBtn.disabled = true;
            
            // Simulate subscription (in real implementation, this would call your newsletter API)
            setTimeout(() => {
                alert('Thank you for subscribing to our newsletter!');
                this.reset();
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });
    }
    
    // ===== LAZY LOADING FOR IMAGES =====
    
    const images = document.querySelectorAll('img[data-src]');
    if (images.length > 0) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    // ===== ENHANCED INTERACTIONS =====
    
    // Card hover effects
    const cards = document.querySelectorAll('.featured-item, .news-card, .post-card, .portfolio-item');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Social links external handling
    const socialLinks = document.querySelectorAll('.social-link');
    socialLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            // In a real implementation, these would link to actual social media profiles
            console.log('Social link clicked:', this.innerHTML);
        });
    });
    
    // ===== ACCESSIBILITY ENHANCEMENTS =====
    
    // Keyboard navigation for mobile menu
    if (navToggle) {
        navToggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
    
    // Focus management for modal-like elements
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Close mobile menu on escape
            if (navMenu && navMenu.classList.contains('active')) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
    
    // ===== PERFORMANCE OPTIMIZATIONS =====
    
    // Throttle scroll events
    let ticking = false;
    function throttledScroll() {
        if (!ticking) {
            requestAnimationFrame(() => {
                updateActiveNav();
                ticking = false;
            });
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', throttledScroll);
    
    // Preload critical images
    function preloadImages() {
        const criticalImages = [
            'https://pixabay.com/get/g89fa98f53b71e81554c3cf02dfbf942a78f6f183cb0651714a6aeeb1823f7728777a5cc254c893d55f447d07422482654605cf812b9eb3e409e6b8baff5db819_1280.jpg'
        ];
        
        criticalImages.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }
    
    preloadImages();
    
    // ===== PAGE SPECIFIC FUNCTIONALITY =====
    
    // Homepage specific
    if (document.body.classList.contains('home')) {
        // Add any home page specific functionality here
    }
    
    // Portfolio specific
    if (window.location.pathname.includes('portfolio')) {
        // Initialize portfolio with all items visible
        if (portfolioItems.length > 0) {
            portfolioItems.forEach(item => {
                item.style.animation = 'fadeInUp 0.6s ease forwards';
            });
        }
    }
    
    // Contact page specific
    if (window.location.pathname.includes('contact')) {
        // Auto-focus first form field
        const firstInput = document.querySelector('#firstName');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
    
    // ===== ERROR HANDLING =====
    
    window.addEventListener('error', function(e) {
        console.error('JavaScript error:', e.error);
        // In production, you might want to send this to a logging service
    });
    
    // ===== INITIALIZATION COMPLETE =====
    
    console.log('Johnny Depp Portfolio - JavaScript initialized successfully');
    
    // Trigger initial animations
    setTimeout(() => {
        const initialElements = document.querySelectorAll('.fade-in');
        initialElements.forEach(el => {
            if (el.getBoundingClientRect().top < window.innerHeight) {
                el.classList.add('visible');
            }
        });
    }, 100);
});

// ===== UTILITY FUNCTIONS =====

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Smooth scroll to element
function scrollToElement(element, offset = 80) {
    if (element) {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
}

// Format phone number (if needed for form validation)
function formatPhoneNumber(phoneNumber) {
    const cleaned = ('' + phoneNumber).replace(/\D/g, '');
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
    if (match) {
        return '(' + match[1] + ') ' + match[2] + '-' + match[3];
    }
    return phoneNumber;
}

// ===== GLOBAL EXPORTS (if needed) =====
window.portfolioUtils = {
    scrollToElement,
    isInViewport,
    debounce,
    formatPhoneNumber
};
