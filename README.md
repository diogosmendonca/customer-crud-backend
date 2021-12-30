# Customer CRUD Backend

This project is an example of CRUD application of Customers and Locations developed in Laravel 8 and React 17. 

The compiled files of front-end are already available in public folder. If you want to generate those compiled files by yourself, please refer to the  [front-end part of the project](https://github.com/diogosmendonca/customer-crud-frontend).

For running the application in the local machine just install and start [sail](https://laravel.com/docs/8.x/sail) and access http://localhost/. To run sail use the command `sail up`.

The APIs are available in using `http://localhost/api/customer` and `http://localhost/api/locations` urls.

If you want to have example data in the application. Run `sail astisan db:seed`.

I developed automated tests for the APIs. For running it, use `sail test` command. The tests delete all data from the database, so use seed command to reseed the database after running the tests.

