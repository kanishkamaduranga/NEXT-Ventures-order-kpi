# Installing PHP Redis Extension

## The Problem

Laravel Horizon requires the PHP Redis extension to be installed. If you see the error:
```
Class "Redis" not found
```

You need to install the `php-redis` extension.

## Installation

### For Ubuntu/Debian:

```bash
# For PHP 8.4 (current version)
sudo apt-get update
sudo apt-get install php8.4-redis

# Or for PHP 8.3
sudo apt-get install php8.3-redis

# Or for PHP 8.2
sudo apt-get install php8.2-redis
```

### Verify Installation:

```bash
php -m | grep redis
# Should output: redis

php -i | grep -i redis
# Should show Redis extension information
```

### Restart Services:

After installation, you may need to restart:
- PHP-FPM (if using): `sudo systemctl restart php8.4-fpm`
- Supervisor: `sudo systemctl restart supervisor`
- Web server (if applicable): `sudo systemctl restart apache2` or `sudo systemctl restart nginx`

## Alternative: Use Laravel Sail

If you're developing locally, it's easier to use Laravel Sail which has Redis extension pre-installed:

```bash
# Start Sail
./vendor/bin/sail up -d

# Run Horizon inside Sail
./vendor/bin/sail artisan horizon
```

## Check Current PHP Version

```bash
php -v
# This will show your PHP version (e.g., PHP 8.4.14)
```

Then install the corresponding redis extension package.

