# Modules

This is a library for PHP to enable a form of dependency injection.

It enumerates modules from the current project which are tagged with an
annotation into a static module manifest. This can be hooked in as a composer
script or run manually. For development purposes it is possible to generate the
manifest dynamically with every request.

## Example Usage

src/php/Example/ExampleModule.php
```php
namespace Example;

/**
 * Class ExampleModule
 * @package Example
 * @@Module
 */
class ExampleModule
{
    function __construct() {
        // Code here.
    }
}
```

index.php
```php
<?php
include 'vendor/autoload.php';
use Modules\ModuleLoader;

setupInjection(ModuleLoader::dynamicallyLoadModules());
$module = injectInstance('Module')->create();
```