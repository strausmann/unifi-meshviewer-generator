# Execute Before Tasks
.gp/bash/before-tasks.sh

# Copy Laravel .env file
cp .env.example .env

# Initiate composer and npm
composer install -o;