# Symfony 5 skeleton with Virtual Kernel for multiple applications

### Installation

    composer create-project yceruto/symfony-skeleton ddd

### Context-based Virtual Kernel

The term "Virtual Kernel" refers to the practice of running more than one application (such as `api.example.com` and `admin.example.com`) on a single project repository. Virtual kernels are "context-based", meaning that you have multiple kernel (with different context) running on the same project. The fact that they are running on the same physical repository is not apparent to the end user.

In short, each kernel context corresponds to one application.

### Application-based Configuration

The idea is to replicate the default project structure for each application, which is represented by a subdirectory with the context of the virtual kernel. It should look like this:

    ├── config/
    │   ├── admin/
    │   │   ├── packages/
    │   │   ├── bundles.php
    │   │   ├── routes.yaml
    │   │   ├── security.yaml
    │   │   └── services.yaml
    │   ├── api/
    │   ├── site/
    │   ├── packages/
    │   ├── bundles.php
    │   └── services.yaml
    ├── src/
    │   ├── Admin/
    │   ├── Api/
    │   ├── Site/
    │   ├── Shared/
    │   └── Kernel.php
    ├── var/
    │   ├── cache/
    │   │   ├── admin/
    │   │   │   ├── dev/
    │   │   │   └── prod/
    │   │   ├── api/
    │   │   └── site/
    │   └── logs/

There `admin`, `api` and `site` directories are part of this multiple kernel approach, and they will contain all what is only needed for each app. Whereas `packages/`, `bundles.php` and any other dir/file at root of the `config` will be recognized as global config for all apps.

As a performance key, each app (by definition) has its own DI container file, routes and configuration, while sharing common things too like vendors, config and src code.

### Keeping one entry point for all applications

    ├── public/
    │   └── index.php

Following the same philosophy since Symfony 4, as well as you can set environment variables to decide the app mode (dev/test/prod) and whether debug mode is enabled you must create a new environment variable `APP_CONTEXT` to specify the app you want to run. Lets playing with it using the PHP's built-in Web server:

    $ APP_CONTEXT=admin php -S 127.0.0.1:8000 -t public
    $ APP_CONTEXT=api php -S 127.0.0.1:8001 -t public   

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
    │   ├── Admin/
    │   │   └── AdminWebTestCase.php
    │   ├── Api/

The `tests` directory is pretty similar to `src` directory, just update your `composer.json` and map each directory `tests/<CONTEXT>/` with its PSR-4 namespace:

    "autoload-dev": {
        "psr-4": {
            "Admin\\Tests\\": "tests/Admin/",
            "Api\\Tests\\": "tests/Api/"
        }
    },

Run `composer dump-autoload` to re-generate the autoload config.
    
Here, creates a `<CONTEXT>WebTestCase` class per app in order to execute all tests together.

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
 
### Adding more applications to the project

Run `bin/console make:app-context <CONTEXT>` to create a new application context skeleton.

Note: After install any new package that generate a new configuration file (into the common `config/packages` directory) make sure to move it to the correct sub-app directory if it is not intended to work for all applications.
Also, you should update the `auto-scripts` section in `composer.json` to execute each command with the right kernel option, and it's also recommended having the script `"cache:clear -k <CONTEXT>": "symfony-cmd"` for each app.

License
-------

This software is published under the [MIT License](LICENSE)
