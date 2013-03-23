openlss/core-boot
=========

The OpenLSS boostrapper

Usage
----

```php
require('boot.php');
__boot();
```

Optionally you can predefine root paths and a group
```php
define('ROOT',__DIR__);
define('ROOT_GROUP',__DIR__.'/admin');
require('boot.php');
__boot();
```

What it does
----
  * By just loading the file
   * Sets up all PHP errors to be exceptions
   * Sets up a default exception error handler to print nicer error messages than PHP for uncaught exceptions
   * Sets the default timezone to UTC
   * Sets the ROOT constant is not already defined
  * By calling the __boot() function
   * Calls __boot_pre()
   * Calls __boot_post()
  * By calling __boot_pre()
   * Loads all the dynamic config files and user overrides
   * Sets the timezone from the config
  * By calling __boot_post()
   * Dynamically loads all module init code

Reference
---
The bootstrapper gives several low level functions to be used for loading the LSS environment

### (void) __boot()
Boots up the environment

### (void) __boot_pre()
See above for details

### (void) __boot_post()
See above for details

### (bool) __init_load_files($dir_path,$callback=false,$callback_params=array(),$recurse=true)
Loads all PHP files from a given directory either by just including them or passing them to a callback
  * $dir_path			The path to load files from
  * $callback			A function to be called with the path to each file for custom loading
  * $callback_params	An array of parameters to be passed to the callback in addition to the location of the file
  * $recurse			When set to TRUE will recurse into lower directories and load all files

### (bool) ld()
Global Auto Loader similar to LD for linux
  * Takes unlimited arguments with the following syntax
   * LIBRARIES (default)
    * 'lib_name' - load the lib automatically based on its name
     * will load group level and if not found will load root level
     * will also try collection loading for libs in a collection
     * EG: if item_taxes is passed it will check lib/item/taxes.php
     * NOTE: even forced locations still perform this lookup
    * '/lib_name' - force lib to load from root, other locations will not be tried
     * 'group/lib_name' - cross load lib from other group other locations will not be tried
   * FUNCTIONS
    * 'func/pkg' - will load functions in the same fashion
    * can also be forced with /func/pkg admin/func/pkg etc

### (mixed) ld_exists($name,$prefix='lib')
Global auto loader existence checker
  * Will check to see if a lib exists and supports all the syntax of the global lib loader
  * Returns the following
   * true: class has already been loaded by name
   * false: class does not exist and hasnt been loaded
   * string: absolute file path to the class to be loaded

### (void) __e($err=array())
Loads error codes
  * Array should be in the following format
   * CODE => 'CONSTANT'
    * EG: 1001 => 'E_USER_INVALID'
NOTE: Will throw a PHP E_NOTICE if there is a code or constant conflict
