/* ==================================================
   WEBSITE INTERACTIVITY JAVASCRIPT
   --------------------------------------------------
   Purpose: Enhances user experience with navbar effects, FAQ toggles, smooth scrolling, animations, and programme filtering.
   Functionality: Manages navbar scrolling, menu toggling, FAQ expansion, smooth scrolling, element animations, and programme search/filtering.
   UI Role: Provides a responsive and interactive interface for navigation, content interaction, and programme browsing.
   Version: 1.0
   Last Updated: July 23, 2025
   ==================================================
*/

document.addEventListener('DOMContentLoaded', () => {
    /* ==================================================
       NAVBAR SCROLL EFFECT
       --------------------------------------------------
       Purpose: Adds a visual effect to the navbar when scrolling.
       Functionality: Toggles a class on the navbar when the user scrolls past a threshold.
       UI Role: Enhances navigation visibility with a scroll-based style change.
       Logic: Listens for scroll events and toggles the 'window-scroll' class based on scroll position.
    */
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('nav');
        // Adds or removes 'window-scroll' class based on scroll position
        nav.classList.toggle('window-scroll', window.scrollY > 100);
    });

    /* ==================================================
       NAVBAR MENU TOGGLE
       --------------------------------------------------
       Purpose: Toggles the mobile navigation menu.
       Functionality: Shows/hides the menu and switches between open/close buttons.
       UI Role: Enables mobile-friendly navigation with a toggleable menu.
       Logic: Listens for clicks on menu buttons and outside the menu to toggle visibility.
    */
    const menu = document.querySelector('.nav_menu');
    const menuBtn = document.querySelector('#open-menu-btn');
    const closeBtn = document.querySelector('#close-menu-btn');
    if (menuBtn && closeBtn && menu) {
        menuBtn.addEventListener('click', () => {
            // Opens the menu and swaps button visibility
            menu.classList.add('open');
            menuBtn.style.display = 'none';
            closeBtn.style.display = 'inline-block';
        });

        closeBtn.addEventListener('click', () => {
            // Closes the menu and swaps button visibility
            menu.classList.remove('open');
            closeBtn.style.display = 'none';
            menuBtn.style.display = 'inline-block';
        });

        // Closes menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target) && !menuBtn.contains(e.target) && !closeBtn.contains(e.target)) {
                menu.classList.remove('open');
                closeBtn.style.display = 'none';
                menuBtn.style.display = 'inline-block';
            }
        });
    }

    /* ==================================================
       FAQ TOGGLE
       --------------------------------------------------
       Purpose: Toggles FAQ sections to show/hide answers.
       Functionality: Expands or collapses FAQs and updates icons.
       UI Role: Makes FAQs interactive for users to view answers.
       Logic: Listens for clicks on FAQs, toggles the 'open' class, and updates plus/minus icons.
    */
    const faqs = document.querySelectorAll('.faq');
    faqs.forEach(faq => {
        faq.addEventListener('click', () => {
            // Toggles the open state of the FAQ
            faq.classList.toggle('open');
            // Updates the icon based on open state
            const icon = faq.querySelector('.faq_icon i');
            if (faq.classList.contains('open')) {
                icon.className = 'uil uil-minus';
            } else {
                icon.className = 'uil uil-plus';
            }
        });
    });

    /* ==================================================
       SMOOTH SCROLLING
       --------------------------------------------------
       Purpose: Enables smooth scrolling for anchor links.
       Functionality: Scrolls to target sections smoothly when clicking links.
       UI Role: Improves navigation by providing a smooth scrolling experience.
       Logic: Listens for clicks on anchor links and scrolls to the target section.
    */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                // Scrolls smoothly to the target section
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    /* ==================================================
       SCROLL ANIMATIONS
       --------------------------------------------------
       Purpose: Animates elements as they enter the viewport.
       Functionality: Fades in and translates elements using IntersectionObserver.
       UI Role: Enhances visual appeal with on-scroll animations.
       Logic: Observes elements and applies opacity and transform styles when they intersect.
    */
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Animates element into view
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.addEventListener('DOMContentLoaded', () => {
        // Selects elements for animation
        const animatedElements = document.querySelectorAll('.category, .faq, .testimonial');
        animatedElements.forEach(el => {
            // Sets initial styles for animation
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            // Observes element for visibility
            observer.observe(el);
        });
    });

    /* ==================================================
       BUTTON HOVER EFFECTS
       --------------------------------------------------
       Purpose: Adds hover effects to buttons.
       Functionality: Scales and lifts buttons on mouseenter/mouseleave.
       UI Role: Enhances button interactivity with visual feedback.
       Logic: Applies transform styles on hover and resets on leave.
    */
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', function () {
            // Scales and lifts button on hover
            this.style.transform = 'translateY(-3px) scale(1.05)';
        });
        button.addEventListener('mouseleave', function () {
            // Resets button transform
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    

    /* ==================================================
       TESTIMONIAL HOVER EFFECTS
       --------------------------------------------------
       Purpose: Adds hover effects to testimonial elements.
       Functionality: Scales avatars and lifts testimonial bodies on hover.
       UI Role: Enhances visual engagement for testimonials.
       Logic: Applies transform styles to avatars and bodies on mouseenter/mouseleave.
    */
    document.querySelectorAll('.testimonial').forEach(testimonial => {
        testimonial.addEventListener('mouseenter', function () {
            const avatar = this.querySelector('.avatar');
            const body = this.querySelector('.testimonial_body');
            // Scales avatar and lifts body on hover
            if (avatar) {
                avatar.style.transform = 'scale(1.1)';
            }
            if (body) {
                body.style.transform = 'translateY(-5px)';
            }
        });
        testimonial.addEventListener('mouseleave', function () {
            const avatar = this.querySelector('.avatar');
            const body = this.querySelector('.testimonial_body');
            // Resets transforms on leave
            if (avatar) {
                avatar.style.transform = 'scale(1)';
            }
            if (body) {
                body.style.transform = 'translateY(0)';
            }
        });
    });

    /* ==================================================
       NAVIGATION ACTIVE STATE
       --------------------------------------------------
       Purpose: Highlights the active navigation link based on scroll position.
       Functionality: Updates the active link as sections enter the viewport.
       UI Role: Indicates the current section in the navigation menu.
       Logic: Tracks scroll position and updates the 'active' class on nav links.
    */
    const navLinks = document.querySelectorAll('.nav_menu a');
    window.addEventListener('scroll', () => {
        let current = '';
        const sections = document.querySelectorAll('section');
        // Determines the current section based on scroll position
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (window.pageYOffset >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });
        // Updates active class on navigation links
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    /* ==================================================
       LOADING ANIMATION
       --------------------------------------------------
       Purpose: Animates page load with a loader and header elements.
       Functionality: Fades out the loader and animates header elements on load.
       UI Role: Provides a smooth loading experience for users.
       Logic: Hides the loader and sequentially animates header elements.
    */
    window.addEventListener('load', () => {
        const loader = document.querySelector('.loader');
        if (loader) {
            // Fades out and hides the loader
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }
        // Animates header elements with staggered timing
        const headerElements = document.querySelectorAll('.header_left h1, .header_left p, .header_left .btn');
        headerElements.forEach((el, index) => {
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 200);
        });
    });

   
    /* ==================================================
       FORM VALIDATION
       --------------------------------------------------
       Purpose: Adds visual feedback for form submissions.
       Functionality: Shows a success animation on submit for non-specific forms.
       UI Role: Enhances user feedback for form interactions.
       Logic: Excludes specific forms, shows success animation, and resets button.
    */
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Skips forms with custom JavaScript
        if (form.id === 'interest-form' || form.id === 'loginForm') {
            return;
        }
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            // Adds success animation to submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="uil uil-check"></i> Enviado!';
                submitBtn.style.background = 'linear-gradient(45deg, #43e97b, #38f9d7)';
                // Resets button after 3 seconds
                setTimeout(() => {
                    submitBtn.innerHTML = 'Enviar';
                    submitBtn.style.background = 'linear-gradient(45deg, #667eea, #764ba2)';
                }, 3000);
            }
        });
    });

    /* ==================================================
       MOBILE MENU CLOSE ON CLICK
       --------------------------------------------------
       Purpose: Closes the mobile menu when clicking outside.
       Functionality: Hides the menu and toggles buttons on outside clicks.
       UI Role: Improves mobile usability by closing the menu automatically.
       Logic: Checks for clicks outside the menu and buttons to close it.
    */
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav_menu') && !e.target.closest('#open-menu-btn')) {
            menu.classList.remove('open');
            closeBtn.style.display = 'none';
            menuBtn.style.display = 'inline-block';
        }
    });

    /* ==================================================
       KEYBOARD NAVIGATION
       --------------------------------------------------
       Purpose: Enhances keyboard accessibility for menu closing.
       Functionality: Closes the mobile menu on Escape key press.
       UI Role: Improves accessibility for keyboard users.
       Logic: Listens for Escape key and closes the menu.
    */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // Closes mobile menu on Escape key
            menu.classList.remove('open');
            closeBtn.style.display = 'none';
            menuBtn.style.display = 'inline-block';
        }
    });

    /* ==================================================
       PERFORMANCE OPTIMIZATION
       --------------------------------------------------
       Purpose: Optimizes scroll event handling.
       Functionality: Uses requestAnimationFrame to throttle scroll updates.
       UI Role: Ensures smooth performance for scroll-based animations.
       Logic: Prevents excessive scroll event calls with a ticking flag.
    */
    let ticking = false;
    function updateOnScroll() {
        // Placeholder for scroll-based animations
        ticking = false;
    }
    window.addEventListener('scroll', () => {
        if (!ticking) {
            // Throttles scroll events with requestAnimationFrame
            requestAnimationFrame(updateOnScroll);
            ticking = true;
        }
    });

    /* ==================================================
       ACCESSIBILITY IMPROVEMENTS
       --------------------------------------------------
       Purpose: Enhances accessibility with focus indicators.
       Functionality: Adds visible focus outlines to interactive elements.
       UI Role: Improves usability for keyboard navigation.
       Logic: Applies and removes outline styles on focus/blur events.
    */
    document.addEventListener('DOMContentLoaded', () => {
        // Selects focusable elements
        const focusableElements = document.querySelectorAll('a, button, input, textarea, select');
        focusableElements.forEach(element => {
            element.addEventListener('focus', function () {
                // Adds focus outline
                this.style.outline = '2px solid #667eea';
                this.style.outlineOffset = '2px';
            });
            element.addEventListener('blur', function () {
                // Removes focus outline
                this.style.outline = 'none';
            });
        });
    });

    /* ==================================================
       PROGRAMME SEARCH & FILTER
       --------------------------------------------------
       Purpose: Filters programme items based on search and category.
       Functionality: Shows/hides programme cards based on search terms and filter buttons.
       UI Role: Enables users to find programmes by level or keywords.
       Logic: Listens for search input and filter clicks, matches items, and toggles visibility.
    */
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.querySelector('#programme-search');
        const filterButtons = document.querySelectorAll('.programme_filters .btn');
        const programmeItems = document.querySelectorAll('.programmes_list .category');

        const handleFiltering = () => {
            // Gets active filter and search term
            const activeFilter = document.querySelector('.programme_filters .btn.active').dataset.filter;
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            // Filters programme items
            programmeItems.forEach(item => {
                const itemLevel = item.dataset.level;
                const itemTitle = item.dataset.title.toLowerCase();
                const itemKeywords = item.dataset.keywords.toLowerCase();
                // Checks if item matches filter and search criteria
                const levelMatch = (activeFilter === 'all' || itemLevel === activeFilter);
                const searchMatch = (itemTitle.includes(searchTerm) || itemKeywords.includes(searchTerm));
                // Shows or hides item based on matches
                item.style.display = levelMatch && searchMatch ? 'flex' : 'none';
            });
        };

        if (filterButtons.length > 0) {
            // Adds click listeners to filter buttons
            filterButtons.forEach(button => {
                button.addEventListener('click', () => {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    handleFiltering();
                });
            });
        }

        if (searchInput) {
            // Adds keyup listener for search input
            searchInput.addEventListener('keyup', handleFiltering);
        }
    });

    /* ==================================================
       PROGRAMME DESCRIPTION TOGGLE
       --------------------------------------------------
       Purpose: Toggles between brief and full programme descriptions.
       Functionality: Shows/hides full description and updates button text.
       UI Role: Provides interactive programme information display.
       Logic: Toggles display states and button text based on current state.
    */
    window.showFullDescription = function(index) {
        const cards = document.querySelectorAll('.category');
        const card = cards[index];
        
        if (!card) return;
        
        const briefDesc = card.querySelector('.programme-description-brief');
        const fullDesc = card.querySelector('.programme-description-full');
        const button = card.querySelector('.btn-secondary');
        
        if (!briefDesc || !fullDesc || !button) return;
        
        if (fullDesc.style.display === 'none') {
            // Show full description
            briefDesc.style.display = 'none';
            fullDesc.style.display = 'block';
            button.textContent = 'View Less';
        } else {
            // Show brief description
            briefDesc.style.display = 'block';
            fullDesc.style.display = 'none';
            button.textContent = 'View Full Description';
        }
    };
});