# Supervisor Setup Instructions

## Quick Setup

After updating the configuration file, run these commands:

```bash
# 1. Copy the updated configuration
sudo cp docker/supervisor/laravel-horizon.conf /etc/supervisor/conf.d/laravel-horizon.conf

# 2. Tell Supervisor to reload configuration
sudo supervisorctl reread

# 3. Update Supervisor (adds new programs)
sudo supervisorctl update

# 4. Start Horizon
sudo supervisorctl start laravel-horizon

# 5. Check status
sudo supervisorctl status
```

## Troubleshooting

### If you get "no such process" error:

This means Supervisor hasn't loaded the configuration yet. Run:
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### If you're using Laravel Sail (Docker):

The configuration in `laravel-horizon.conf` is set for host system. For Docker/Sail, you have two options:

**Option 1: Run Horizon inside Docker container**
```bash
./vendor/bin/sail artisan horizon
```

**Option 2: Update config for Docker paths**
- Change `command` to: `php /var/www/html/artisan horizon`
- Change `user` to: `sail`
- Change `directory` to: `/var/www/html`

### Check logs:

```bash
# Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log

# Horizon logs
sudo tail -f /var/log/supervisor/horizon.log
```

### Restart Supervisor service:

```bash
sudo systemctl restart supervisor
```

