# Symfony 5 skeleton with Virtual Kernel for multiple applications

### Name-based Virtual Kernel

The term "Virtual Kernel" refers to the practice of running more than one application (such as `api.example.com` and `admin.example.com`) on a single project repository. Virtual kernels are "name-based", meaning that you have multiple kernel names running on the same project. The fact that they are running on the same physical repository is not apparent to the end user.

In short, each kernel name corresponds to one application.

### Application-based Configuration

The idea is replicate the default project structure for each application, which is represented by a subdirectory with the name of the vertual kernel. It should look like this:

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
    │   └── bundles.php
    ├── src/
    │   ├── Admin/
    │   ├── Api/
    │   ├── Site/
    │   └── VirtualKernel.php
    ├── var/
    │   ├── cache/
    │   │   ├── admin/
    │   │   │   ├── dev/
    │   │   │   └── prod/
    │   │   ├── api/
    │   │   └── site/
    │   └── logs/

This way the `VirtualKernel` will execute induvidual apps with dedicated config files (`var/cache/<name>/<env>/*`):

 * `<name><Env>DebugProjectContainer*`
 * `<name><Env>DebugProjectContainerUrlGenerator*`
 * `<name><Env>DebugProjectContainerUrlMatcher*`
 
This is the performance key as each app (by definition) has its own DI container file, routes and configuration.

### Keeping one entry point for all applications

    ├── public/
    │   └── index.php

Following the same filosofy of Symfony 4, whereas environment variables decides which environment and debug mode should be used to run your app, you can use the new environment variable `APP_NAME` to specify the application you want to run. 
Let's playing with it using the PHP's built-in Web server and prefixing the new environment variable:

    $ APP_NAME=admin php -S 127.0.0.1:8000 -t public
    $ APP_NAME=api php -S 127.0.0.1:8001 -t public   

### Executing commands per application

    ├── bin/
    │   └── console.php

Use `--kernel`, `-k` option to run any command for one specific app:

    $ bin/console about -k api
    
Or if you prefer, use environment variables on CLI:

    $ export APP_NAME=api
    $ bin/console about                         # api application
    $ bin/console debug:router                  # api application
    $
    $ APP_NAME=admin bin/console debug:router   # admin application

Also you can configure the default `APP_NAME` environment variable in your `.env` file or in `bin/console`.

### Running tests per application

    ├── tests/
    │   ├── Admin/
    │   │   └── AdminWebTestCase.php
    │   ├── Api/

The `tests` directory is pretty similar to `src` directory, just update your `composer.json` and map each directory `tests/<Name>/` with its PSR-4 namespace:

    "autoload-dev": {
        "psr-4": {
            "Admin\\Tests\\": "tests/Admin/",
            "Api\\Tests\\": "tests/Api/"
        }
    },

Run `composer dump-autoload` to re-generate the autoload config.
    
Here, creates a `<Name>WebTestCase` class per app in order to execute all tests together.

### Production and vhosts

Set the environment variable `APP_NAME` for each vhost config in your production server and development machine if prefer:

    <VirtualHost admin.company.com:80>
        # ...
        
        SetEnv APP_NAME admin
        
        # ...
    </VirtualHost>

    <VirtualHost api.company.com:80>
        # ...
        
        SetEnv APP_NAME api
        
        # ...
    </VirtualHost>
 
### Adding more applications to the project

Run `bin/console create-app <name>` to create a new application.

Note: After install any new package that generate a new configuration file (into the common `config/packages` directory) make sure to move it to the correct sub-app directory if it is not intended to work for all applications.
Also you should update the `auto-scripts` section in `composer.json` to execute each command with the right kernel option, and it's also recommended to have the script `"cache:clear -k <name>": "symfony-cmd"` for each app.

License
-------

This software is published under the [MIT License](LICENSE)
