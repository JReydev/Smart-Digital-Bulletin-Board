// Lightweight heartbeat to detect multi-login session invalidation
(function initSessionHeartbeat(){
    // Don't run heartbeat on login pages or if we're already on a session-ended page
    if (window.location.pathname.includes('/login/') || 
        window.location.pathname.includes('/logout/') ||
        document.title.includes('Session Ended')) {
        return;
    }
    
    // Check if there's a session cookie - if not, don't run heartbeat at all
    // This prevents false session end popups when user has no session
    const hasSessionCookie = document.cookie.split(';').some(c => c.trim().startsWith('PHPSESSID='));
    if (!hasSessionCookie) {
        return; // No session to monitor, exit early
    }
    
    // Check if this is a reload after session invalidation
    // Note: This is kept as a fallback, but we now show popup immediately when detected
    if (sessionStorage.getItem('sessionInvalidated') === 'true') {
        sessionStorage.removeItem('sessionInvalidated');
        // Check if popup is already showing (to prevent duplicate)
        if (!document.getElementById('sessionTerminatedOverlay')) {
            showSessionTerminatedPopup();
        }
        return;
    }
    
    const CHECK_INTERVAL_MS = 15000; // 15s
    const STATUS_URL = '/SmartBulletin/modules/include/session_status.php';
    let heartbeatActive = true;
    let heartbeatInterval = null;
    
    async function check(){
        if (!heartbeatActive) return;
        
        try {
            const res = await fetch(STATUS_URL, { credentials: 'same-origin', cache: 'no-store' });
            if (!res.ok) {
                // If we get a non-OK response, check if it's a redirect to login
                // This might indicate session ended
                if (res.status === 302 || res.redirected) {
                    // Double-check by trying to access a protected endpoint
                    const doubleCheck = await fetch(STATUS_URL, { credentials: 'same-origin', cache: 'no-store', redirect: 'manual' });
                    if (doubleCheck.status === 0 || doubleCheck.type === 'opaqueredirect') {
                        heartbeatActive = false;
                        sessionStorage.setItem('sessionInvalidated', 'true');
                        window.location.reload();
                    }
                }
                return;
            }
            const data = await res.json();
            
            // Only trigger session end if:
            // 1. There was a session (hasSession: true)
            // 2. But it's now invalid (valid: false)
            // This prevents false positives when user has no session at all
            if (data.hasSession && !data.valid) {
                // Double-check: make another request to confirm session is really invalid
                // This prevents false positives from network issues
                const confirmRes = await fetch(STATUS_URL, { credentials: 'same-origin', cache: 'no-store' });
                if (confirmRes.ok) {
                    const confirmData = await confirmRes.json();
                    if (confirmData.hasSession && !confirmData.valid) {
                        heartbeatActive = false; // Stop heartbeat to prevent multiple triggers
                        // Show popup immediately - don't reload first
                        // This ensures the user sees the message before redirect
                        showSessionTerminatedPopup();
                        return; // Exit early, popup will handle redirect
                    }
                }
            } else if (!data.hasSession) {
                // No session exists - stop heartbeat as there's nothing to monitor
                heartbeatActive = false;
                if (heartbeatInterval) {
                    clearInterval(heartbeatInterval);
                    heartbeatInterval = null;
                }
            }
        } catch(e) {
            // ignore network errors - don't trigger session end on network issues
        }
    }
    
    function showSessionTerminatedPopup() {
        // Prevent showing multiple popups
        if (document.getElementById('sessionTerminatedOverlay')) {
            return;
        }
        
        // Stop heartbeat immediately to prevent multiple triggers
        heartbeatActive = false;
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
        // Prevent body scroll when modal is shown
        document.body.style.overflow = 'hidden';
        
        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.id = 'sessionTerminatedOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        `;
        
        // Create modal content
        const modal = document.createElement('div');
        modal.style.cssText = `
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        `;
        
        modal.innerHTML = `
            <div style="font-size: 4em; color: #ff4d4d; margin-bottom: 20px;">🔐</div>
            <h2 style="color: #7B0000; margin-bottom: 15px; font-size: 1.8em; font-weight: 600;">Session Terminated</h2>
            <p style="color: #333; margin-bottom: 25px; line-height: 1.6; font-size: 1.1em;">
                This account has been logged in on a different browser/device.
            </p>
            <div style="margin: 25px 0;">
                <div style="font-size: 2.5em; color: #7B0000; font-weight: 600; margin-bottom: 10px;" id="countdown">3</div>
                <p style="color: #666; font-size: 0.9em;">Redirecting to login page in <span id="countdownText">3</span> seconds...</p>
            </div>
            <button id="goToLoginBtn" onclick="window.location.replace('/SmartBulletin/modules/login/login.php');" 
                    style="background: #7B0000; color: white; border: none; padding: 12px 30px; border-radius: 25px; cursor: pointer; font-size: 1em; font-weight: 500; transition: all 0.3s ease; margin-top: 10px;">
                Go to Login Now
            </button>
        `;
        
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        
        // Add CSS animations
        if (!document.getElementById('sessionTerminatedStyles')) {
            const style = document.createElement('style');
            style.id = 'sessionTerminatedStyles';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideUp {
                    from { 
                        transform: translateY(30px);
                        opacity: 0;
                    }
                    to { 
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Wait for DOM to be ready, then set up countdown and button
        // Use setTimeout to ensure elements are in the DOM
        setTimeout(() => {
            // Add hover effect to button
            const loginBtn = document.getElementById('goToLoginBtn');
            if (loginBtn) {
                loginBtn.addEventListener('mouseenter', function() {
                    this.style.background = '#9B1000';
                    this.style.transform = 'translateY(-2px)';
                });
                loginBtn.addEventListener('mouseleave', function() {
                    this.style.background = '#7B0000';
                    this.style.transform = 'translateY(0)';
                });
            }
            
            // Countdown logic
            let countdown = 3;
            const countdownElement = document.getElementById('countdown');
            const countdownTextElement = document.getElementById('countdownText');
            
            if (!countdownElement || !countdownTextElement) {
                // If elements not found, redirect immediately
                console.error('Countdown elements not found, redirecting immediately');
                window.location.replace('/SmartBulletin/modules/login/login.php');
                return;
            }
            
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                countdownTextElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    // Redirect to login page
                    window.location.replace('/SmartBulletin/modules/login/login.php');
                }
            }, 1000);
        }, 100); // Small delay to ensure DOM is ready
    }
    
    // Start heartbeat after a delay to ensure page is fully loaded
    setTimeout(() => {
        if (heartbeatActive) {
            heartbeatInterval = setInterval(check, CHECK_INTERVAL_MS);
            // Initial check after page load
            setTimeout(check, 5000);
        }
    }, 2000);
})();
