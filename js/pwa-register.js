/**
 * PWA Service Worker Registration
 * Supports both Android and iOS devices
 * Version: 1.0.0
 */

(function() {
    'use strict';

    // Service Worker Registration
    if ('serviceWorker' in navigator) {
        // Determine the correct path to the service worker
        const swPath = window.location.pathname.includes('/modules/') 
            ? '/SmartBulletin/sw.js' 
            : './sw.js';

        window.addEventListener('load', function() {
            navigator.serviceWorker.register(swPath)
                .then(function(registration) {
                    console.log('[PWA] ServiceWorker registration successful with scope:', registration.scope);
                    
                    // Check for updates
                    registration.addEventListener('updatefound', function() {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', function() {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New content is available
                                console.log('[PWA] New version available');
                                // Optionally show update notification
                                if (window.confirm && confirm('New version available! Reload to update?')) {
                                    window.location.reload();
                                }
                            }
                        });
                    });

                    // Periodic update check (iOS compatible)
                    setInterval(function() {
                        registration.update();
                    }, 60000); // Check every minute
                })
                .catch(function(err) {
                    console.error('[PWA] ServiceWorker registration failed:', err);
                });
        });
    } else {
        console.warn('[PWA] Service Workers are not supported in this browser');
    }

    // iOS PWA Install Detection
    // iOS doesn't support beforeinstallprompt, so we detect standalone mode
    if (window.navigator.standalone === true) {
        console.log('[PWA] Running as iOS standalone app');
        document.body.classList.add('ios-standalone');
    }

    // Detect if running as PWA (iOS or Android)
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                         window.navigator.standalone === true ||
                         document.referrer.includes('android-app://');

    if (isStandalone) {
        console.log('[PWA] Running as installed PWA');
        document.body.classList.add('pwa-standalone');
    }

    // Android PWA Install Prompt
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', function(e) {
        console.log('[PWA] Install prompt triggered (Android)');
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install button or notification
        if (typeof showInstallPrompt === 'function') {
            showInstallPrompt();
        }
    });

    // Handle PWA install completion
    window.addEventListener('appinstalled', function(e) {
        console.log('[PWA] App was installed successfully');
        deferredPrompt = null;
    });

    // Expose install function for manual trigger
    window.installPWA = function() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    console.log('[PWA] User accepted the install prompt');
                } else {
                    console.log('[PWA] User dismissed the install prompt');
                }
                deferredPrompt = null;
            });
        } else {
            // iOS installation instructions
            if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                alert('To install this app on iOS:\n1. Tap the Share button\n2. Select "Add to Home Screen"');
            } else {
                alert('Installation not available. Please use your browser\'s install option.');
            }
        }
    };

    // iOS-specific: Prevent zoom on double tap
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function(event) {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            event.preventDefault();
        }
        lastTouchEnd = now;
    }, false);

    // iOS-specific: Handle viewport height changes (address bar)
    function setViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', setViewportHeight);
})();

