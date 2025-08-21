/**
 * GLightbox Integration for Ekwa Video Block
 * Lazy loads GLightbox library on user interaction
 */

(function() {
    'use strict';

    let glightboxLoaded = false;
    let glightboxInstance = null;

    /**
     * Load GLightbox library and initialize
     */
    function loadGLightbox() {
        if (glightboxLoaded) return Promise.resolve();

        return new Promise(function(resolve, reject) {
            // Load CSS
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = ekwaVideoData.pluginUrl + 'assets/vendor/glightbox.min.css';
            link.onload = function() {
                // Load JS
                const script = document.createElement('script');
                script.src = ekwaVideoData.pluginUrl + 'assets/vendor/glightbox.min.js';
                script.onload = function() {
                    glightboxLoaded = true;
                    initializeGLightbox();
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            };
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }

    /**
     * Initialize GLightbox
     */
    function initializeGLightbox() {
        if (typeof GLightbox !== 'undefined' && !glightboxInstance) {
            // Initialize with clean configuration - no transcript in lightbox
            glightboxInstance = GLightbox({
                selector: '.glightbox',
                touchNavigation: true,
                loop: false,
                autoplayVideos: true,
                videosWidth: '90vw',
                descPosition: 'bottom',
                moreText: '',
                moreLength: 0
            });

            console.log('GLightbox initialized successfully');
        }
    }    /**
     * Setup lazy loading triggers
     */
    function setupLazyLoading() {
        let hasTriggered = false;
        let clickHandlerAdded = false;

        function triggerLoad() {
            if (hasTriggered) return;
            hasTriggered = true;

            loadGLightbox().catch(function(error) {
                console.error('Failed to load GLightbox:', error);
            });

            // Remove event listeners
            window.removeEventListener('scroll', triggerLoad);
            window.removeEventListener('mousemove', triggerLoad);
            window.removeEventListener('touchstart', triggerLoad);
        }

        function handleLightboxClick(event) {
            const lightboxTrigger = event.target.closest('.glightbox');
            if (lightboxTrigger && !glightboxLoaded) {
                event.preventDefault();
                event.stopPropagation();

                console.log('Loading GLightbox...');

                // Show loading indicator
                lightboxTrigger.style.opacity = '0.7';

                loadGLightbox().then(function() {
                    console.log('GLightbox loaded, opening lightbox...');
                    // Restore original content
                    lightboxTrigger.style.opacity = '1';

                    // Remove this click handler to prevent loop
                    document.removeEventListener('click', handleLightboxClick);

                    // Give GLightbox a moment to initialize, then trigger manually
                    setTimeout(function() {
                        if (glightboxInstance) {
                            // Find the index of this element in all glightbox elements
                            const allLightboxElements = document.querySelectorAll('.glightbox');
                            const elementIndex = Array.from(allLightboxElements).indexOf(lightboxTrigger);
                            console.log('Opening lightbox at index:', elementIndex);
                            glightboxInstance.openAt(elementIndex);
                        } else {
                            console.error('GLightbox instance not available');
                            // Fallback: try normal click
                            lightboxTrigger.click();
                        }
                    }, 200);
                }).catch(function(error) {
                    console.error('Failed to load GLightbox:', error);
                    lightboxTrigger.style.opacity = '1';
                });
            }
        }

        // Check if lightbox videos exist on page
        if (document.querySelector('.glightbox')) {
            // Add event listeners for lazy loading
            window.addEventListener('scroll', triggerLoad, { passive: true });
            window.addEventListener('mousemove', triggerLoad, { passive: true });
            window.addEventListener('touchstart', triggerLoad, { passive: true });

            // Handle immediate clicks on lightbox triggers only if GLightbox not loaded
            if (!clickHandlerAdded) {
                document.addEventListener('click', handleLightboxClick);
                clickHandlerAdded = true;
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupLazyLoading);
    } else {
        setupLazyLoading();
    }

})();
