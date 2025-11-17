# Supervisor Configuration for Queue Management

This directory contains Supervisor configuration files for managing Laravel Horizon queue workers.

## Files

- `supervisord.conf` - Main Supervisor daemon configuration
- `laravel-horizon.conf` - Laravel Horizon worker configuration

## Installation (Production)

### 1. Install Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### 2. Copy Configuration Files

```bash
sudo cp docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
sudo cp docker/supervisor/laravel-horizon.conf /etc/supervisor/conf.d/laravel-horizon.conf
```

### 3. Update Configuration

Edit `/etc/supervisor/conf.d/laravel-horizon.conf` and update:
- `user` - Change from `sail` to your production user (e.g., `www-data`)
- `command` - Update path if needed

### 4. Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-horizon
```

### 5. Check Status

```bash
sudo supervisorctl status
```

## Commands

```bash
# Start Horizon
sudo supervisorctl start laravel-horizon

# Stop Horizon
sudo supervisorctl stop laravel-horizon

# Restart Horizon
sudo supervisorctl restart laravel-horizon

# View logs
sudo tail -f /var/log/supervisor/horizon.log
```

## Development (Laravel Sail)

For development with Laravel Sail, you can run Horizon directly:

```bash
./vendor/bin/sail artisan horizon
```

Or add it to your `compose.yaml` as a service.

