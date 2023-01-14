# Symfony Multi-Application Project Skeleton

Organize and Manage Multiple Applications with Kernel Contexts.

This project skeleton is designed to implement the [Domain-Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) (DDD) 
and [Hexagonal Architecture](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)) patterns. It is also well-suited 
for use in a [microservice](https://en.wikipedia.org/wiki/Microservices) architecture.

### Installation

    composer create-project yceruto/symfony-skeleton ddd

### Context-based Kernel

A context-based kernel in Symfony refers to a custom implementation of the Kernel class that allows for running multiple 
applications, each with its own context (such as `api.example.com` and `admin.example.com`), within a single project repository. 
The different contexts are transparent to the end-user, but enable a clear separation of concerns and organization of 
the codebase. 

Each context corresponds to a separate and distinct entrypoint (site, api or admin), each with its own set of dedicated 
routes and configurations. Despite this separation, common code, such as dependencies and business logic, are shared among 
all contexts. The kernel uses the request's context to determine the appropriate entrypoint to handle the request, ensuring 
that the correct routes and configurations are used for each context.

### Context-based Configuration

The project structure includes a new `context/` directory where the configuration and presentation-related files are organized 
according to the kernel context. The `src/` directory contains the core functionality of the application, divided into modules for 
better organization and management.

    ├── config/
    │   ├── packages/
    │   ├── bundles.php
    │   └── services.yaml
    ├── context/
    │   ├── admin/
    │   │   ├── config/
    │   │   │   ├── packages/
    │   │   │   ├── bundles.php
    │   │   │   ├── routes.yaml
    │   │   │   ├── security.yaml
    │   │   │   └── services.yaml
    │   │   └── src/
    │   │       ├── Command/
    │   │       └── Controller/
    │   ├── api/
    │   ├── site/
    │   └── Kernel.php
    ├── src/
    │   ├── Module/
    │   │   └── SubModule/
    │   │       ├── Application/
    │   │       ├── Domain/
    │   │       └── Infrastructure/
    │   ├── Shared/
    │   │   ├── Domain/
    │   │   ├── Infrastructure/
    │   │   └── Presentation/
    ├── var/
    │   ├── cache/
    │   │   ├── admin/
    │   │   │   ├── dev/
    │   │   │   └── prod/
    │   │   ├── api/
    │   │   └── site/
    │   └── logs/

The project structure includes subdirectories such as admin, api, and site as part of the kernel context approach. These 
directories contain all files and configurations that are specific to each context. In contrast, files and directories such 
as `packages/`, `bundles.php`, and any others located at the root of the `config/` directory are recognized as global configuration 
for all contexts.

To optimize performance, each app, as defined by the kernel context, has its own Dependency Injection container file, routing 
configuration, and specific settings. However, common elements such as the `vendor/`, `config/`, and `src/` code are shared among 
all the contexts. This approach allows for efficient resource management and organization of the codebase.

### Keeping one entry point for all applications

    ├── public/
    │   └── index.php

In line with Symfony 4's philosophy, environment variables can be used to determine the app's mode (dev/test/prod) and 
whether debug mode is enabled. Additionally, a new environment variable called `APP_CONTEXT` must be created to specify 
the kernel context that should be run. This can be easily tested using PHP's built-in web server by setting the environment 
variable before starting the server:

    $ APP_CONTEXT=admin php -S 127.0.0.1:8000 -t public
    $ APP_CONTEXT=api php -S 127.0.0.1:8001 -t public   

### Use Symfony local webserver

To run multiple kernel contexts, you will need to use the [Symfony local server](https://symfony.com/doc/current/setup/symfony_server.html) and 
its [proxy](https://symfony.com/doc/current/setup/symfony_server.html#setting-up-the-local-proxy) functionality.

First, start the Symfony proxy by running the command `symfony proxy:start` in the project folder.

Next, create a symbolic link ([symlink](https://en.wikipedia.org/wiki/Symbolic_link)) for each of your applications that 
points to your project folder. These symbolic links can be stored in a folder within your project or outside of it, depending 
on your preference.

    ├── links/
    │   ├── admin
    |   ├── api
    |   └── site
    ├── config/
    ├── src/
    └── var/

After creating the symbolic links, you will need to configure each local server and start it. This is done by using the 
symbolic links created earlier. For example, you might run a command such as:

```
# start admin local server
APP_CONTEXT=admin symfony proxy:domain:attach admin --dir=[project folder path]/links/admin
APP_CONTEXT=admin symfony server:start --dir=[project folder path]/links/admin

# start api local server
APP_CONTEXT=api symfony proxy:domain:attach api --dir=[project folder path]/links/api
APP_CONTEXT=api symfony server:start --dir=[project folder path]/links/api

# start site local server
APP_CONTEXT=site symfony proxy:domain:attach site --dir=[project folder path]/links/site
APP_CONTEXT=site symfony server:start --dir=[project folder path]/links/site
```

To verify that each server is running, you can navigate to the appropriate URL in your web browser [localhost:7080](http://localhost:7080).

### Production and vhosts

In order to run multiple kernel contexts in a production environment or development environment, you will need to set the 
environment variable `APP_CONTEXT` for each virtual host configuration. This can be done by modifying the appropriate 
configuration files on your production server or development machine, depending on your preference:

    <VirtualHost admin.company.com:80>
        # ...
        
        SetEnv APP_CONTEXT admin
        
        # ...
    </VirtualHost>

    <VirtualHost api.company.com:80>
        # ...
        
        SetEnv APP_CONTEXT api
        
        # ...
    </VirtualHost>

### Executing commands per application

    ├── bin/
    │   └── console.php

Use `--kernel`, `-k` option to run any command for one specific app:

    $ bin/console about -k api

Or if you prefer, use environment variables on CLI:

    $ export APP_CONTEXT=api
    $ bin/console about                         # api application
    $ bin/console debug:router                  # api application
    $
    $ APP_CONTEXT=admin bin/console debug:router   # admin application

Additionally, you can set the default `APP_CONTEXT` environment variable in your `.env` file or by modifying the `bin/console` file. 
This allows you to specify the default kernel context that will be used if the environment variable is not set or overridden elsewhere.

### Running tests per application

    ├── tests/
    │   └── context/
    │       ├── admin
    │       │   └── AdminWebTestCase.php
    │       └── api/

The `tests/` directory will include a `context/` directory that mirrors the structure of the `context/` directory in the main codebase. 
To use this structure in your tests, you will need to update your `composer.json` file to map each directory within `tests/context/<CONTEXT>/`
to its corresponding PSR-4 namespace. This allows you to test each kernel context separately.

    "autoload-dev": {
        "psr-4": {
            "Admin\\Tests\\": "tests/context/admin/",
            "Api\\Tests\\": "tests/context/api/"
        }
    },

Run `composer dump-autoload` to re-generate the autoload config.

To run all the tests for a specific kernel context, create a separate `<CONTEXT>WebTestCase` class for each app. 
This allows you to execute all the tests together and test each kernel context independently.

### Adding more applications to the project

To create a new kernel context skeleton, run the command `bin/console make:ddd:context <CONTEXT>` in the terminal. This will 
generate the necessary files and directories for the new kernel context, allowing you to easily add new functionality to 
your application.

When installing new packages that generate new configuration files, it is important to move them to the correct sub-application 
directory if they are not intended to work for all applications. Additionally, you should update the `auto-scripts` section 
in `composer.json` to execute each command with the correct kernel option. To ensure that the cache is cleared for each individual 
application, it is recommended to include the script `"cache:clear -k <CONTEXT>": "symfony-cmd"` for each app in your `composer.json` file.

License
-------

This software is published under the [MIT License](LICENSE)
