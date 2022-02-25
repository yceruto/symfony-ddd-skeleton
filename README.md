# Symfony 5 project skeleton for multiple applications

This project skeleton is built for [Domain-Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) approach
and [Hexagonal Architecture](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)). It is also applicable to [microservice](https://en.wikipedia.org/wiki/Microservices) architecture.

### Installation

    composer create-project yceruto/symfony-skeleton ddd

### Context-based Kernel

The term refers to the practice of running (at the same time) more than one application with different contexts
(such as `api.example.com` and `admin.example.com`) on a single project repository. The fact that they are running on the
same physical repository is not apparent to the end user.

In short, each kernel context corresponds to one application.

### Context-based Configuration

The project structure contains a new `context/` directory where the config and presentation subjects are placed per kernel context,
and the `src/` directory hosts the core functionalities divided by modules:

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

There `admin`, `api` and `site` directories are part of this kernel context approach, and they will contain all what is 
only needed for each context. Whereas `packages/`, `bundles.php` and any other dir/file at root of the `config` will be 
recognized as global config for all contexts.

As a performance key, each app (by definition) has its own DI container file, routes and configuration, while sharing 
common things too like `vendor`, `config` and `src` code.

### Keeping one entry point for all applications

    ├── public/
    │   └── index.php

Following the same philosophy since Symfony 4, as well as you can set environment variables to decide the app mode 
(dev/test/prod) and whether debug mode is enabled you must create a new environment variable `APP_CONTEXT` to specify 
the kernel context you want to run. Lets playing with it using the PHP's built-in Web server:

    $ APP_CONTEXT=admin php -S 127.0.0.1:8000 -t public
    $ APP_CONTEXT=api php -S 127.0.0.1:8001 -t public   

### Use Symfony local webserver

You will need to use [Symfony local server](https://symfony.com/doc/current/setup/symfony_server.html) and its [proxy](https://symfony.com/doc/current/setup/symfony_server.html#setting-up-the-local-proxy).

First, start Symfony proxy by doing `symfony proxy:start` in the project folder.

Then, create a [symlink](https://en.wikipedia.org/wiki/Symbolic_link) pointing to your project folder for each of your 
applications. You can keep them in a folder in your project or outside it, as you prefer.

    ├── links/
    │   ├── admin
    |   ├── api
    |   └── site
    ├── config/
    ├── src/
    └── var/

Next, you'll need to configure each local server and start it. To do so, you will use the created symlinks, like so:

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

To check if each server is running, you can go to [localhost:7080](http://localhost:7080).

### Production and vhosts

Set the environment variable `APP_CONTEXT` for each vhost config in your production server and development machine if preferred:

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

Also, you can configure the default `APP_CONTEXT` environment variable in your `.env` file or in `bin/console`.

### Running tests per application

    ├── tests/
    │   └── context/
    │       ├── admin
    │       │   └── AdminWebTestCase.php
    │       └── api/

The `tests` directory will contain the `context/` directory and replicate its structure, just update your `composer.json` 
and map each directory `tests/context/<CONTEXT>/` with its PSR-4 namespace:

    "autoload-dev": {
        "psr-4": {
            "Admin\\Tests\\": "tests/context/admin/",
            "Api\\Tests\\": "tests/context/api/"
        }
    },

Run `composer dump-autoload` to re-generate the autoload config.

Here, creates a `<CONTEXT>WebTestCase` class per app in order to execute all tests together.

### Adding more applications to the project

Run `bin/console make:ddd:context <CONTEXT>` to create a new Kernel context skeleton.

Note: After install any new package that generate a new configuration file (into the common `config/packages` directory) 
make sure to move it to the correct sub-app directory if it is not intended to work for all applications. Also, you should 
update the `auto-scripts` section in `composer.json` to execute each command with the right kernel option, and it's also 
recommended having the script `"cache:clear -k <CONTEXT>": "symfony-cmd"` for each app.

License
-------

This software is published under the [MIT License](LICENSE)
