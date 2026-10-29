# Smart Digital Bulletin Board

Smart Digital Bulletin Board is a web-based bulletin board system for the College of Computer Studies. It provides faculty, officers, and admin users with announcements, events, calendar management, multimedia support, and a progressive web app shell. A Raspberry Pi can run the bulletin display in a browser connected to the hosted application.

## Key Features

- User authentication and session management
- Announcement creation, editing, archiving, and multimedia support
- Event management and calendar display
- Faculty and officer profile management
- PWA support with `manifest.json` and service worker
- Configurable themes and marquee displays

## Languages and Technologies

- **PHP** — server-side pages, application logic, and API endpoints
- **HTML** — page and bulletin display structure
- **CSS** — layout, styling, and themes
- **JavaScript** — browser interactions, slideshow behavior, and background data refresh
- **SQL** — database queries for MySQL/MariaDB
- **JSON** — PWA manifest and application data/configuration
- **Python** — supporting review utility; it is not required to run the web application

## Requirements

- PHP 7.4+ or later
- MySQL / MariaDB
- Apache web server (XAMPP recommended for local development)
- Browser with PWA support for installable experience
- For a Raspberry Pi display: Raspberry Pi OS with Chromium (or another modern browser), network access to the application, and a connected display

## Installation

1. Copy the project folder into your web root. Example for XAMPP:
   - `C:\xampp\htdocs\Smart-Digital-Bulletin-Board`

2. Create the `db_bulletin_board` MySQL/MariaDB database and import the project schema using phpMyAdmin or the MySQL CLI. The schema SQL file is not included in the current repository snapshot; obtain it from the project maintainer if you do not already have it.

3. Update database settings in `database/connect.php` if necessary.

4. Ensure the app is accessible through the proper project path.
   - The current `index.php` redirects to `/SmartBulletin/modules/homepage.php`.
   - If your folder is served under a different path, update `index.php` and any hard-coded paths accordingly.

## Running Locally

1. Start Apache and MySQL via XAMPP.
2. Open your browser and navigate to the project URL.
   - Example: `http://localhost/Smart-Digital-Bulletin-Board/`
3. If the login page does not appear, verify the redirect paths in `index.php`.

## Raspberry Pi Bulletin Display

Use the Raspberry Pi as a display client; the PHP application and MySQL/MariaDB database should be running on a server that the Pi can reach over the network.

1. Connect the Pi and the application server to the same network, or otherwise ensure the Pi can reach the server.
2. On the Pi, open Chromium and navigate to the bulletin display page. For a local XAMPP server, use its LAN address and project path, for example:
   - `http://<server-ip>/Smart-Digital-Bulletin-Board/modules/view_bulletin_layout.html`
3. If the project is deployed under a different folder or URL, use that deployment path and update any hard-coded application paths as needed.
4. Put the browser into full-screen or kiosk mode, keep the Pi powered and connected to the network, and configure the display not to sleep.

The Raspberry Pi does not need PHP or MySQL when it is only displaying a bulletin hosted on another computer. Install the PHP and database stack on the Pi only if you intend to host the application there as well.

## Project Structure

- `index.php` — entry redirect to login or homepage
- `manifest.json` — PWA metadata
- `sw.js` — service worker script
- `database/` — database connection and schema SQL
- `modules/` — main application modules and pages
- `css/` — themes and styles
- `js/` — client-side scripts
- `images/` — app icons and media assets
- `api/` — backend endpoints for AJAX data

## Notes

- This project is designed for deployment inside a PHP-capable web server environment.
- If installed as a PWA, the start URL is set to `./modules/homepage.php`.
- The app uses sessions to track authenticated users, so PHP session support must be enabled.
- Bulletin display pages retrieve content from the application's API, so the Pi must remain connected to the server for current content.

## License

Add a license here if desired.


<img width="1919" height="943" alt="image" src="https://github.com/user-attachments/assets/98110ae3-9bb3-42f2-af22-5f292fa4d7d7" />
<img width="1919" height="943" alt="image" src="https://github.com/user-attachments/assets/78d081fe-ba42-4332-855f-e5fa71166487" />
<img width="1919" height="943" alt="image" src="https://github.com/user-attachments/assets/c763b80e-d91f-41a6-954b-de7d46d3ad83" />
<img width="1919" height="949" alt="image" src="https://github.com/user-attachments/assets/bbff820d-0e90-498d-83df-7d2668f8a01b" />
<img width="1919" height="953" alt="image" src="https://github.com/user-attachments/assets/33f2b0c1-228e-45a8-97be-7e9af94de402" />



