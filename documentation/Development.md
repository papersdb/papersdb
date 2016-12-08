# Development

## Setting up development environment

These instructions will set up a PHP development environment for
PapersDB. These instructions work on Ubuntu 16.04 or later. *It is
assumed you have root privileges.*

1. Install the required packages:

    ```sh
    sudo add-apt-repository ppa:ondrej/php
    sudo apt-get update
    sudo apt-get install mysql-server apache2 libapache2-mod-php5.6 php5.6 php5.6-mysql php5.6-xml
    ```
1. Fork the PapersDB Github repository:
   https://github.com/papersdb/papersdb
1. Clone the PapersDB sofware repository:

    ```sh
    cd <your_projects_parent_directory>
    git clone git@github.com:<your_github_user_name>/papersdb.git
    ```
    Note that you must substitute `<your_projects_parent_directory>`
    and `<your_github_user_name>` with your own values.
1. Create a site to host your PapersDB developemnt
   environment in Apache:

    ```sh
    cd /etc/apache2/sites-available
    ```
    And, as user `root`, create a file named `papersdb.conf` with the
    following content:

    ```conf
    <VirtualHost *:8080>
        ServerAdmin webmaster@localhost
        ServerName papersdb
        DocumentRoot /var/www/papersdb/public_html
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>
    ```
    Note that port *8080* is used in this example. You can use any
    port that you like.
1. Create a symbolic link to the project for Apache to use:

    ```sh
    cd /var/www
    sudo mkdir papersdb
    cd papersdb
    ln -s <full_path_to_project_dir> public_html
    ```

    Replace `<full_path_to_project_dir>` with the path to the
    directory where you cloned the project.
1. Enable the site in Apache:

    ```sh
    sudo a2ensite papersdb.conf
    ```
1. Configure Apache to listen on the port for your site by adding a
   line to the '/etc/apache2/ports.conf' file.
    ```sh
    Listen 8080
    ```
    This can be added following the line containing `Listen 80`.

    *Note that if you chose a different port for the VirtualHost, you
    would put that port number instead of `8080`.*
1. As user `root`, install PEAR (PHP Extension and Application
   Repository):

    ```sh
    wget http://pear.php.net/go-pear.phar
    php go-pear.phar
    ```
    You will be asked for a file layout, just press <kbd>Enter</kbd>
    to choose the default layout.
1. As user `root`, install the following PEAR packages:

    ```sh
    pear install HTML_Table
    pear install HTML_QuickForm
    ```
1. As a normal user, make a symbolic link to the PEAR packages in your
   project directory:

    ```sh
    cd <your_project_directory>
    ln -s /usr/share/pear .
    ```
    Note that `<your_project_directory>` must be replaced with the
    directory where you cloned the project.
1. Create a MySQL database to hold the application data.

    ```sh
    cd <your_project_directory>
    mysqladmin -uroot -p create pubDBdev
    mysql -uroot -p pubDBdev < data/sql/schema.sql
    ```
1. Create a MySQL user to that can access the database.

    First start the MySQL command line tool:

    ```sh
    mysql -uroot -p pubDBdev
    ```

    Then enter these commands:

    ```sh
    create user 'papersdb'@'localhost' identified by '<password_here>';
    grant all privileges on pubDBdev.* to 'papersdb'@'localhost' with grant option;
    ```
    Note that you must replace `<password_here>` with a password of
    your own choosing.
1. Edit the `includes/pdDb.php` and add your MySQL user password.

    Look for this line:

    ```php
    private static $db_passwd = '';
    ```
    And place your password within the single quotes.
1. You can now restart the Apache server:

    ```sh
    sudo service apache2 restart
    ```
1. Now use a web browser and enter this url to see the application's
   main page: [http://localhost:8080/](http://localhost:8080/)

    Replace `8080` with the port number you chose, if different.
