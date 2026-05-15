// SmartBulletin PWA Service Worker
// Version: 1.0.0
// Last Updated: 2024

const CACHE_NAME = 'smartbulletin-v1.0.3';
const STATIC_CACHE = 'smartbulletin-static-v1.0.3';
const DYNAMIC_CACHE = 'smartbulletin-dynamic-v1.0.3';
const API_CACHE = 'smartbulletin-api-v1.0.3';
const MEDIA_CACHE = 'smartbulletin-media-v1.0.3';

// Get the base URL dynamically
const getBaseUrl = () => {
  return self.location.origin + '/SmartBulletin';
};

// Cache configuration
const CACHE_CONFIG = {
  // Static assets that should be cached immediately
  STATIC_ASSETS: [
    './',
    './index.php',
    './ios-splash.html',
    './modules/homepage.php',
    './modules/login/login.php',
    './modules/view_bulletin.html',
    './modules/view_bulletin_layout.html',
    './modules/view_bulletin.php',
    './api/get_slides_data.php',
    // Main module pages
    './modules/calendar/calendar.php',
    './modules/announcement/announcement.php',
    './modules/announcement/add_announcement.php',
    './modules/announcement/announcementview.php',
    './modules/announcement/archive_announcement.php',
    './modules/announcement/archived_announcements.php',
    './modules/announcement/clear_announcement.php',
    './modules/announcement/delete_announcement.php',
    './modules/announcement/edit_announcement.php',
    './modules/announcement/reorder_media.php',
    './modules/announcement/restore_announcement.php',
    './modules/events/upcoming-events.php',
    './modules/events/add_event.php',
    './modules/events/archive_event.php',
    './modules/events/archived_events.php',
    './modules/events/delete_event.php',
    './modules/events/edit_event.php',
    './modules/events/restore_event.php',
    './modules/faculty/faculty.php',
    './modules/faculty/add_faculty.php',
    './modules/faculty/delete_faculty.php',
    './modules/faculty/edit_faculty.php',
    './modules/officers/officers.php',
    './modules/officers/add_officer.php',
    './modules/officers/add_partylist.php',
    './modules/officers/delete_officer.php',
    './modules/officers/delete_partylist.php',
    './modules/officers/edit_officer.php',
    './modules/officers/update_officer.php',
    './modules/admin/manage_accounts.php',
    './modules/admin/manage_marquee.php',
    './modules/account/account.php',
    './modules/logout/logout.php',
    './modules/logout/offline_logout.html',
    // Images
    './images/logo.png',
    './images/logoCCS.png',
    './images/logoFCMS.png',
    './images/logoOLFU.png',
    './images/fcmsIcon.png',
    './images/Iconfcms.png',
    './images/default-avatar.jpg',
    './images/1.jpg',
    // External CDN resources
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/treant-js@1.0/Treant.css',
    'https://cdn.jsdelivr.net/npm/perfect-scrollbar@1.5.5/css/perfect-scrollbar.css',
    'https://code.jquery.com/jquery-3.6.0.min.js',
    'https://cdn.jsdelivr.net/npm/raphael@2.3.0/raphael.min.js',
    'https://cdn.jsdelivr.net/npm/treant-js@1.0/Treant.js',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap'
  ],
  
  // API endpoints that should be cached with network-first strategy
  API_ENDPOINTS: [
    './api/get_slides_data.php'
  ],
  
  // Media file patterns
  MEDIA_PATTERNS: [
    /\/multimedia\/announcements\/.*\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$/i,
    /\/multimedia\/events\/.*\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$/i,
    /\/multimedia\/faculty\/.*\.(jpg|jpeg|png|gif|webp)$/i,
    /\/multimedia\/officers\/.*\.(jpg|jpeg|png|gif|webp)$/i,
    /\/multimedia\/profile\/.*\.(jpg|jpeg|png|gif|webp)$/i
  ],
  
  // Pages that should have offline fallbacks
  OFFLINE_PAGES: [
    './modules/homepage.php',
    './modules/view_bulletin.html',
    './modules/calendar/calendar.php',
    './modules/announcement/announcement.php',
    './modules/events/upcoming-events.php',
    './modules/faculty/faculty.php',
    './modules/officers/officers.php',
    './modules/admin/manage_accounts.php',
    './modules/admin/manage_marquee.php',
    './modules/account/account.php'
  ]
};

// Cache size limits
const CACHE_LIMITS = {
  STATIC: 50, // MB
  DYNAMIC: 20, // MB
  API: 10, // MB
  MEDIA: 100 // MB
};

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('[SW] Installing service worker...');
  
  event.waitUntil(
    Promise.all([
      // Cache static assets
      caches.open(STATIC_CACHE).then(cache => {
        console.log('[SW] Caching static assets...');
        return cache.addAll(CACHE_CONFIG.STATIC_ASSETS).catch(error => {
          console.error('[SW] Error caching static assets:', error);
          // Cache individual assets if batch fails
          return Promise.allSettled(
            CACHE_CONFIG.STATIC_ASSETS.map(asset => 
              cache.add(asset).catch(err => 
                console.warn(`[SW] Failed to cache ${asset}:`, err)
              )
            )
          );
        });
      }),
      
      // Skip waiting to activate immediately
      self.skipWaiting()
    ])
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('[SW] Activating service worker...');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== STATIC_CACHE && 
                cacheName !== DYNAMIC_CACHE && 
                cacheName !== API_CACHE && 
                cacheName !== MEDIA_CACHE) {
              console.log('[SW] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      
      // Take control of all clients
      self.clients.claim()
    ])
  );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip chrome-extension and other non-http requests
  if (!url.protocol.startsWith('http')) {
    return;
  }
  
  // Skip requests that should bypass service worker
  if (shouldBypassServiceWorker(request)) {
    return;
  }
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Handle special pages that should always go to network
  if (isSpecialPage(request)) {
    event.respondWith(handleSpecialPage(request));
    return;
  }
  
  // Normalize URLs for better matching
  const normalizedUrl = normalizeUrl(request.url);
  
  // Determine caching strategy based on request type
  if (isAPIRequest(request)) {
    event.respondWith(handleAPIRequest(request));
  } else if (isMediaRequest(request)) {
    event.respondWith(handleMediaRequest(request));
  } else if (isStaticAsset(request)) {
    event.respondWith(handleStaticAsset(request));
  } else if (isPageRequest(request)) {
    event.respondWith(handlePageRequest(request));
  } else {
    event.respondWith(handleDynamicRequest(request));
  }
});

// Normalize URLs to handle relative paths correctly
function normalizeUrl(url) {
  try {
    const urlObj = new URL(url);
    // If it's a relative path, make it absolute
    if (url.startsWith('./') || url.startsWith('../')) {
      return new URL(url, self.location.origin + '/SmartBulletin/').href;
    }
    return urlObj.href;
  } catch (error) {
    console.warn('[SW] Error normalizing URL:', url, error);
    return url;
  }
}

// Helper functions to determine request types
function isSpecialPage(request) {
  const specialPages = [
    'logout.php',
    'login.php',
    'auth_required.php',
    'no_auth_required.php',
    'auth_check.php'
  ];
  
  return specialPages.some(page => 
    request.url.includes(page)
  );
}

// Check if request should bypass service worker (for POST requests and form actions)
function shouldBypassServiceWorker(request) {
  // Allow all non-GET requests to go to network
  if (request.method !== 'GET') {
    return true;
  }
  
  // Allow specific action pages to go to network
  const actionPages = [
    'archive_event.php',
    'delete_event.php',
    'add_event.php',
    'edit_event.php',
    'restore_event.php',
    'archive_announcement.php',
    'delete_announcement.php',
    'add_announcement.php',
    'edit_announcement.php',
    'restore_announcement.php'
  ];
  
  return actionPages.some(page => 
    request.url.includes(page)
  );
}

function isAPIRequest(request) {
  return CACHE_CONFIG.API_ENDPOINTS.some(endpoint => 
    request.url.includes(endpoint)
  );
}

function isMediaRequest(request) {
  return CACHE_CONFIG.MEDIA_PATTERNS.some(pattern => 
    pattern.test(request.url)
  );
}

function isStaticAsset(request) {
  return CACHE_CONFIG.STATIC_ASSETS.some(asset => 
    request.url.includes(asset)
  ) || 
  request.url.includes('/images/') ||
  request.url.includes('.css') ||
  request.url.includes('.js') ||
  request.url.includes('cdnjs.cloudflare.com') ||
  request.url.includes('cdn.jsdelivr.net') ||
  request.url.includes('fonts.googleapis.com') ||
  request.url.includes('code.jquery.com');
}

function isPageRequest(request) {
  return request.destination === 'document' || 
         request.url.endsWith('.php') ||
         request.url.endsWith('.html');
}

// Caching strategies
async function handleSpecialPage(request) {
  // Special pages like logout should always go to network
  // and never be cached or show offline page
  try {
    const networkResponse = await fetch(request);
    return networkResponse;
  } catch (error) {
    console.log('[SW] Network failed for special page, redirecting to login:', request.url);
    
    // For special pages, redirect to appropriate offline page
    if (request.url.includes('logout.php')) {
      return new Response(null, {
        status: 302,
        headers: {
          'Location': './modules/logout/offline_logout.html'
        }
      });
    }
    
    // For other special pages, try to fetch from network
    return fetch(request);
  }
}

async function handleAPIRequest(request) {
  const cache = await caches.open(API_CACHE);
  
  try {
    // Network-first strategy for API calls
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Cache successful responses
      const responseClone = networkResponse.clone();
      await cache.put(request, responseClone);
      
      // Clean up old API cache entries
      await cleanCache(cache, CACHE_LIMITS.API);
      
      return networkResponse;
    }
  } catch (error) {
    console.log('[SW] Network failed for API request, trying cache:', request.url);
  }
  
  // Fallback to cache
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  // Return offline response for API
  return new Response(
    JSON.stringify({
      status: 'error',
      message: 'Offline - No cached data available',
      data: []
    }),
    {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    }
  );
}

async function handleMediaRequest(request) {
  const cache = await caches.open(MEDIA_CACHE);
  
  try {
    // Cache-first strategy for media files
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Fetch from network and cache
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const responseClone = networkResponse.clone();
      await cache.put(request, responseClone);
      
      // Clean up old media cache entries
      await cleanCache(cache, CACHE_LIMITS.MEDIA);
      
      return networkResponse;
    }
  } catch (error) {
    console.log('[SW] Failed to fetch media:', request.url);
  }
  
  // Return fallback image for media
  return new Response(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f0f0f0"/><text x="100" y="100" text-anchor="middle" fill="#999" font-family="Arial" font-size="14">Image not available</text></svg>',
    {
      status: 200,
      headers: { 'Content-Type': 'image/svg+xml' }
    }
  );
}

async function handleStaticAsset(request) {
  const cache = await caches.open(STATIC_CACHE);
  
  // Cache-first strategy for static assets
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const responseClone = networkResponse.clone();
      await cache.put(request, responseClone);
      return networkResponse;
    }
  } catch (error) {
    console.log('[SW] Failed to fetch static asset:', request.url);
  }
  
  // Return 404 for missing static assets
  return new Response('Asset not found', { status: 404 });
}

async function handlePageRequest(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  
  // Check if this is a static page that should be cached
  const isStaticPage = CACHE_CONFIG.STATIC_ASSETS.some(asset => 
    request.url.includes(asset)
  );
  
  if (isStaticPage) {
    // For static pages, try cache first
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      // Update cache in background
      fetch(request).then(response => {
        if (response.ok) {
          cache.put(request, response.clone());
        }
      }).catch(() => {});
      return cachedResponse;
    }
  }
  
  try {
    // Network-first strategy for pages
    const networkResponse = await fetch(request);
    
    // Only show session-ended page if:
    // 1. Server redirected to login
    // 2. AND there was actually a session cookie in the request
    // This prevents showing session ended page when user has no session at all
    const redirectedToLogin = networkResponse.redirected && networkResponse.url.includes('/SmartBulletin/modules/login/login.php');
    if (redirectedToLogin) {
      // Check if the original request had a session cookie
      // If no session cookie exists, this is just a normal "not logged in" redirect, not a session termination
      const requestHeaders = request.headers;
      const cookieHeader = requestHeaders.get('Cookie') || '';
      const hasSessionCookie = cookieHeader.includes('PHPSESSID=');
      
      // Only show session ended page if there was actually a session
      if (hasSessionCookie) {
        return new Response(
          getSessionEndedPage(request.url),
          {
            status: 200,
            headers: { 'Content-Type': 'text/html' }
          }
        );
      }
      // If no session cookie, let the normal redirect to login happen
      // Return the redirect response as-is (don't intercept it)
      return networkResponse;
    }

    if (networkResponse.ok) {
      const responseClone = networkResponse.clone();
      await cache.put(request, responseClone);
      
      // Clean up old dynamic cache entries
      await cleanCache(cache, CACHE_LIMITS.DYNAMIC);
      
      return networkResponse;
    }
  } catch (error) {
    console.log('[SW] Network failed for page request, trying cache:', request.url);
  }
  
  // Fallback to cache
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  // Check if this is a main module page that should have better offline handling
  const isMainModulePage = CACHE_CONFIG.OFFLINE_PAGES.some(page => 
    request.url.includes(page)
  );
  
  if (isMainModulePage) {
    // Return a more helpful offline page for main modules
    return new Response(
      getModuleOfflinePage(request.url),
      {
        status: 200,
        headers: { 'Content-Type': 'text/html' }
      }
    );
  }
  
  // Return standard offline page
  return new Response(
    getOfflinePage(request.url),
    {
      status: 200,
      headers: { 'Content-Type': 'text/html' }
    }
  );
}

async function handleDynamicRequest(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  
  try {
    // Stale-while-revalidate strategy for other requests
    const cachedResponse = await cache.match(request);
    
    const networkResponsePromise = fetch(request).then(response => {
      if (response.ok) {
        const responseClone = response.clone();
        cache.put(request, responseClone);
      }
      return response;
    });
    
    return cachedResponse || networkResponsePromise;
  } catch (error) {
    console.log('[SW] Failed to handle dynamic request:', request.url);
    return new Response('Request failed', { status: 500 });
  }
}

// Cache management functions
async function cleanCache(cache, limitMB) {
  const keys = await cache.keys();
  if (keys.length === 0) return;
  
  // Simple cleanup - remove oldest entries if cache is too large
  // In a production app, you'd want more sophisticated cache management
  const maxEntries = Math.floor(limitMB * 1024 * 1024 / 50000); // Rough estimate
  if (keys.length > maxEntries) {
    const entriesToDelete = keys.slice(0, keys.length - maxEntries);
    await Promise.all(entriesToDelete.map(key => cache.delete(key)));
  }
}

// Module-specific offline page generator
function getModuleOfflinePage(requestedUrl) {
  const moduleName = getModuleName(requestedUrl);
  
  return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - ${moduleName} - SmartBulletin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .offline-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 77, 77, 0.15);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            margin: 20px;
        }
        .offline-icon {
            font-size: 4em;
            color: #ff4d4d;
            margin-bottom: 20px;
        }
        h1 {
            color: #ff4d4d;
            margin-bottom: 15px;
            font-size: 2em;
        }
        h2 {
            color: #7B0000;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        p {
            margin-bottom: 20px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
        }
        .retry-btn {
            background: #7B0000;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .retry-btn:hover {
            background: #9B1000;
            transform: translateY(-2px);
        }
        .btn-group {
            margin-top: 20px;
        }
        .module-info {
            background: rgba(123, 0, 0, 0.1);
            border: 1px solid rgba(123, 0, 0, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">📡</div>
        <h1>You're Offline</h1>
        <h2>${moduleName} Module</h2>
        <div class="module-info">
            <p>This module requires an internet connection to function properly. Some features may not be available while offline.</p>
        </div>
        <p><strong>Requested URL:</strong><br><small>${requestedUrl}</small></p>
        <div class="btn-group">
            <a href="/SmartBulletin/modules/login/login.php" class="retry-btn">Go to Login</a>
        </div>
    </div>
    <script>
        // No retry button; direct to login
    </script>
</body>
</html>`;
}

function getModuleName(url) {
  if (url.includes('calendar')) return 'Calendar';
  if (url.includes('announcement')) return 'Announcements';
  if (url.includes('events')) return 'Events';
  if (url.includes('faculty')) return 'Faculty';
  if (url.includes('officers')) return 'Officers';
  if (url.includes('admin')) return 'Admin';
  if (url.includes('account')) return 'Account';
  return 'Module';
}

// Offline page generator
function getOfflinePage(requestedUrl) {
  // Check if this is a special page that shouldn't show offline
  if (requestedUrl.includes('logout.php')) {
    // Redirect to offline logout page
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting - SmartBulletin</title>
    <script>
        // Redirect to offline logout page
        window.location.href = '/SmartBulletin/modules/logout/offline_logout.html';
    </script>
</head>
<body>
    <p>Redirecting to logout page...</p>
</body>
</html>`;
  }
  
  if (requestedUrl.includes('login.php') || requestedUrl.includes('auth_required.php')) {
    // Redirect to login instead of showing offline page
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting - SmartBulletin</title>
    <script>
        // Redirect to login page
        window.location.href = '/SmartBulletin/modules/login/login.php';
    </script>
</head>
<body>
    <p>Redirecting to login...</p>
</body>
</html>`;
  }
  
  return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartBulletin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .offline-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 77, 77, 0.15);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            margin: 20px;
        }
        .offline-icon {
            font-size: 4em;
            color: #ff4d4d;
            margin-bottom: 20px;
        }
        h1 {
            color: #ff4d4d;
            margin-bottom: 15px;
            font-size: 2em;
        }
        p {
            margin-bottom: 20px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
        }
        .retry-btn {
            background: #7B0000;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .retry-btn:hover {
            background: #9B1000;
            transform: translateY(-2px);
        }
        .login-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        .login-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">🔐</div>
        <h1>Session Ended</h1>
        <p>It looks like your account was signed in on another device. For your security, you have been signed out on this device. Please log in again to continue.</p>
        <p><strong>Requested URL:</strong><br><small>${requestedUrl}</small></p>
        <button class="retry-btn login-btn" onclick="window.location.href='/SmartBulletin/modules/login/login.php'">Go to Login</button>
    </div>
    <script>
        // No retry; go straight to login
    </script>
</body>
</html>`;
}

// Session-ended page generator when server redirects to login
function getSessionEndedPage(requestedUrl) {
  return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Ended - SmartBulletin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 77, 77, 0.15);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            margin: 20px;
        }
        .icon { font-size: 4em; color: #ff4d4d; margin-bottom: 20px; }
        h1 { color: #ff4d4d; margin-bottom: 15px; font-size: 2em; }
        p { margin-bottom: 20px; line-height: 1.6; color: rgba(255, 255, 255, 0.8); }
        .btn {
            background: #7B0000;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #9B1000; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔐</div>
        <h1>Session Ended</h1>
        <p>We noticed a sign in to your account from another device, so you were signed out here. To continue, please log in again.</p>
        <p><strong>Requested URL:</strong><br><small>${requestedUrl}</small></p>
        <a class="btn" href="/SmartBulletin/modules/login/login.php">Go to Login</a>
    </div>
</body>
</html>`;
}

// Background sync for offline actions
self.addEventListener('sync', event => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

async function doBackgroundSync() {
  console.log('[SW] Background sync triggered');
  // Implement background sync logic here
  // For example, sync offline form submissions when connection is restored
}

// Push notifications (if implemented)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: '/SmartBulletin/images/logo.png',
      badge: '/SmartBulletin/images/fcmsIcon.png',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: data.primaryKey
      },
      actions: [
        {
          action: 'explore',
          title: 'View Details',
          icon: '/SmartBulletin/images/logo.png'
        },
        {
          action: 'close',
          title: 'Close',
          icon: '/SmartBulletin/images/logo.png'
        }
      ]
    };
    
    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Notification click handler
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/SmartBulletin/modules/homepage.php')
    );
  }
});

// Message handler for communication with main thread
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'GET_CACHE_SIZE') {
    getCacheSize().then(size => {
      event.ports[0].postMessage({ cacheSize: size });
    });
  }
});

// Get cache size information
async function getCacheSize() {
  const cacheNames = await caches.keys();
  let totalSize = 0;
  
  for (const cacheName of cacheNames) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    totalSize += keys.length;
  }
  
  return totalSize;
}

console.log('[SW] Service worker loaded successfully');
