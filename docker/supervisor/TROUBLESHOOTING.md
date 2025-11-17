# Supervisor Troubleshooting Guide

## Spawn Error Fix

If you get `ERROR (spawn error)`, try these steps:

### 1. Check Supervisor Logs
```bash
sudo tail -50 /var/log/supervisor/supervisord.log
sudo tail -50 /var/log/supervisor/horizon.log
sudo tail -50 /var/log/supervisor/horizon-error.log
```

### 2. Verify Paths
```bash
# Check if PHP is accessible
which php
/usr/bin/php --version

# Check if artisan exists
ls -la /home/kanishka/Workspace/NEXT-Ventures-order-kpi/artisan

# Test running horizon manually
cd /home/kanishka/Workspace/NEXT-Ventures-order-kpi
php artisan horizon
```

### 3. Update Configuration

Make sure the config file has:
- Full path to PHP: `/usr/bin/php`
- Full path to artisan
- Correct user
- Environment variables set

### 4. Reload Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-horizon
```

### 5. Check Permissions
```bash
# Make sure the user can access the directory
ls -la /home/kanishka/Workspace/NEXT-Ventures-order-kpi

# Check if .env file is readable
ls -la /home/kanishka/Workspace/NEXT-Ventures-order-kpi/.env
```

### 6. Test Command Manually
Run the exact command Supervisor will use:
```bash
sudo -u kanishka /usr/bin/php /home/kanishka/Workspace/NEXT-Ventures-order-kpi/artisan horizon
```

### 7. Alternative: Use Absolute Paths with Environment
If environment variables are needed, you might need to source them:
```bash
command=/bin/bash -c "cd /home/kanishka/Workspace/NEXT-Ventures-order-kpi && /usr/bin/php artisan horizon"
```

### Common Issues

**Issue: Permission Denied**
- Solution: Check file permissions on artisan and .env
- Run: `chmod +x artisan`

**Issue: .env not found**
- Solution: Make sure .env file exists and is readable
- Run: `ls -la .env`

**Issue: Redis connection failed**
- Solution: Make sure Redis is running and accessible
- Check: `redis-cli ping`

**Issue: Database connection failed**
- Solution: Verify database credentials in .env
- Test: `php artisan migrate:status`

