# Smart Garbage Collection System

Web-based IoT dashboard for residential estates (PHP, MySQL, HTML, CSS, JavaScript).

## Roles
| Role | Can see |
|---|---|
| Resident | Only their own bin level and their own invoices |
| Garbage collector | Bin levels only, and can mark a bin as collected |
| Estate admin | Everything: all bins, residents, collectors, collector activity, alerts, invoices |

Access is enforced on the server in every PHP page and API endpoint.

## Setup (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\smart-garbage`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin, click Import, choose `database/schema.sql`, and run it.
4. Open http://localhost/smart-garbage/seed.php once to create demo users, then delete `seed.php`.
5. Open http://localhost/smart-garbage/ and sign in.

Demo logins: admin@estate.test / Admin@123, collector@estate.test / Collect@123, amina@estate.test / Resident@123

## ESP32 endpoint
POST JSON to `api/readings.php`:
`{"api_key":"...","bin_code":"BIN-01","distance_cm":34}`
Change `API_KEY` in `includes/config.php` first.

## Test without hardware
curl.exe -X POST http://localhost/smart-garbage/api/readings.php -H "Content-Type: application/json" -d "{\"api_key\":\"change-this-key-before-deploying\",\"bin_code\":\"BIN-01\",\"distance_cm\":12}"
