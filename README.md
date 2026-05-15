# Smart Digital Bulletin Board

Smart Digital Bulletin Board is a PHP-based bulletin board system for the College of Computer Studies. It provides faculty, officers, and admin users with announcements, events, calendar management, multimedia support, and a progressive web app shell.

## Key Features

- User authentication and session management
- Announcement creation, editing, archiving, and multimedia support
- Event management and calendar display
- Faculty and officer profile management
- PWA support with `manifest.json` and service worker
- Configurable themes and marquee displays

## Requirements

- PHP 7.4+ or later
- MySQL / MariaDB
- Apache web server (XAMPP recommended for local development)
- Browser with PWA support for installable experience

## Installation

1. Copy the project folder into your web root. Example for XAMPP:
   - `C:\xampp\htdocs\Smart-Digital-Bulletin-Board`

2. Create the database and tables using `database/db_bulletin_board.sql`.
   - You can import this file using phpMyAdmin or MySQL CLI.

3. Update database settings in `database/connect.php` if necessary.

4. Ensure the app is accessible through the proper project path.
   - The current `index.php` redirects to `/SmartBulletin/modules/homepage.php`.
   - If your folder is served under a different path, update `index.php` and any hard-coded paths accordingly.

## Running Locally

1. Start Apache and MySQL via XAMPP.
2. Open your browser and navigate to the project URL.
   - Example: `http://localhost/Smart-Digital-Bulletin-Board/`
3. If the login page does not appear, verify the redirect paths in `index.php`.

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

## License

Add a license here if desired.
