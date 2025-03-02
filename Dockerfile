# filepath: /finance-app/finance-app/Dockerfile

FROM php:8.0-cli

# Install SQLite extension
RUN docker-php-ext-install pdo pdo_sqlite

# Set the working directory
WORKDIR /var/www/html

# Copy the application files
COPY . .

# Expose the port the app runs on
EXPOSE 8000

# Command to run the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "index.php"]