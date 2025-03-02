# Finance App

This project uses PHP and a SQLite database. You can run the app either using PHP’s built-in web server or with Docker.

## Running the App with PHP’s Built-in Web Server

1. Open a terminal in the project directory.
2. Run the following command:

   ```bash
   php -S localhost:8000
   ```

3. Open your web browser and navigate to [http://localhost:8000](http://localhost:8000) to view the application.

The main entry point is `index.php`.

## Running the App with Docker

To run this application inside a Docker container, follow these steps:

1. Build the Docker image:
   
   ```bash
   docker build -t php-finance-app .
   ```

2. Run the container:
   
   ```bash
   docker run -p 8000:8000 php-finance-app
   ```

3. Open your browser and navigate to [http://localhost:8000](http://localhost:8000).

### Mounting the SQLite Database File (`finance.db`)

If you want to persist the SQLite database file outside the container, you can use Docker volumes.

1. Run the container with a volume mount for `finance.db`:

   ```bash
   docker run -p 8000:8000 \
     -v $(pwd)/finance.db:/var/www/html/finance.db \
     php-finance-app
   ```

   This ensures that the database file remains intact even if the container is removed.