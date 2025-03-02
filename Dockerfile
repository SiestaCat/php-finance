FROM php:8.3-cli

# Update package list and install SQLite development library before installing PDO extensions
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo pdo_sqlite

# Set the working directory
WORKDIR /var/www/html

# Copy the application files
COPY . .

# Expose the port the app runs on
EXPOSE 8000

# Command to run the PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000"]