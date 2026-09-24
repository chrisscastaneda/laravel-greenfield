# Laravel Greenfield Boilerplate Project

Create a basic Laravel boilerplate app to serve as a starting off point for a greenfield SaaS app.

## Basic Features and Requirements

### 0. Fully Dockerized Laravel Application

The application should leverage docker and docker compose to define an consistent runtime environment across all reasonable host environments, and we should be able to spin up an instance of the application with a simple `docker compose up` command.  Specifically, the application needs to be easy to spin up and deploy in a production environment on a live web-server, whether that's through a specialized hosting service like Laravel Forge or Laravel Cloud, or just on a basic AWA EC2 instance, as well as on a localhost development environment, provided that all these environments support the use of docker. 

The application should leverage the latest stable versions the core frameworks and platforms we're building on top of.  Specifically, the core technologies are:

- Laravel, version 13, as the core SaaS framework.
- PHP, versions 8.5 (versions 8.3 or 8.4 could be permissible if there's a strong reason to not go with the latest version of PHP), as the underlying runtime.
- PostgreSQL, version 18, as the database driver.  We'll want to make sure that the data persists between spinning the application down then back up.
- Nginx, version 1.30.5, as the web-server.  Note that we're gonna want to serve this app over HTTPS right off the bat, so we'll need to make sure to generate an SSL certificate at some point in the initial development process.

The key deliverable here will be a docker-compose.yml file, and any supporting artifacts.

### 1. Laravel Boilerplate and Basic User Management 

We're building a bare-bones Laravel application to serve as a starting off point for a greenfield SaaS app.  The only feature we need to implement immediately is a basic user management, registration, and authentication system.  We can speed up development by leaning on Laravel's official Starter Kits (see documentation: https://laravel.com/framework/docs/13.x/starter-kits).  For the this project let's build on top of the Vue starter kit option.  Let's also plan to use WorkOS as our authentication provider, so let's plan on grabbing the WorkOS credentials from our .env file.  As such, we can forgo email verification as WorkOS will handle that for us.  As you're setting this up, be sure to leverage Laravel best practices around code organization and security.

### 2. Testing and Documentation

Be sure to include unit tests to cover the most important elements of the application, namely we want to ensure that user authentication functions as expected.  Also be sure to document the setup of the application in a README.md file.  The README should provide a brief overview of the project, and include clear steps outlining how to spin up the application using docker, any steps required to initialize the project, like setting up the .env, and package installation requirements, running migrations, setting up an SSL certificate, compiling any frontend assets, etc.




