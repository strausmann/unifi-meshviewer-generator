# Copy Laravel .env file
cp .env.example .env

# Set the GitPod URL
sed -i "s|APP_URL=|APP_URL=$(gp url 8001)|g" .env

# Initiate composer and npm
composer install;