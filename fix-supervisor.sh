#!/bin/bash
# Script untuk fix supervisor config

echo "=========================================="
echo "Laravel Queue Worker Supervisor Fix"
echo "=========================================="
echo ""

# Detect PHP path
PHP_PATH=$(which php)
if [ -z "$PHP_PATH" ]; then
    echo "ERROR: PHP not found in PATH"
    echo "Please install PHP or update PATH"
    exit 1
fi

echo "PHP Path: $PHP_PATH"

# Detect project path
PROJECT_PATH="/var/www/event.desrc/event-desrc"
if [ ! -f "$PROJECT_PATH/artisan" ]; then
    echo "ERROR: Laravel project not found at $PROJECT_PATH"
    echo "Please update PROJECT_PATH in this script"
    exit 1
fi

echo "Project Path: $PROJECT_PATH"

# Detect user (try www-data first, fallback to current user)
if id "www-data" &>/dev/null; then
    WORKER_USER="www-data"
else
    WORKER_USER=$(whoami)
    echo "WARNING: www-data user not found, using $WORKER_USER"
fi

echo "Worker User: $WORKER_USER"
echo ""

# Test command
echo "Testing command..."
sudo -u $WORKER_USER $PHP_PATH $PROJECT_PATH/artisan --version
if [ $? -ne 0 ]; then
    echo "ERROR: Cannot execute artisan command"
    echo "Check permissions or user access"
    exit 1
fi

echo "Command test OK!"
echo ""

# Create config
echo "Creating supervisor config..."
sudo tee /etc/supervisor/conf.d/laravel-worker.conf > /dev/null << EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_PATH $PROJECT_PATH/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
directory=$PROJECT_PATH
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$WORKER_USER
numprocs=1
redirect_stderr=true
stdout_logfile=$PROJECT_PATH/storage/logs/worker.log
stderr_logfile=$PROJECT_PATH/storage/logs/worker-error.log
stopwaitsecs=3600
environment=HOME="/home/$WORKER_USER",USER="$WORKER_USER"
EOF

echo "Config created!"
echo ""

# Ensure log directory exists
echo "Creating log directory..."
sudo mkdir -p $PROJECT_PATH/storage/logs
sudo chown -R $WORKER_USER:$WORKER_USER $PROJECT_PATH/storage/logs
sudo chmod -R 755 $PROJECT_PATH/storage/logs

echo "Log directory ready!"
echo ""

# Reload supervisor
echo "Reloading supervisor..."
sudo supervisorctl reread
sudo supervisorctl update

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "To start the worker:"
echo "  sudo supervisorctl start laravel-worker:*"
echo ""
echo "To check status:"
echo "  sudo supervisorctl status laravel-worker:*"
echo ""
echo "To view logs:"
echo "  sudo supervisorctl tail laravel-worker:laravel-worker_00"
echo ""

