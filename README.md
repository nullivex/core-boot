openlss/core-boot
=========

The OpenLSS boostrapper

Usage
----

```php
require('boot.php');
__boot();
```

Optionally you can predefine root path and a group root
```php
define('ROOT',__DIR__);
define('ROOT_GROUP',__DIR__.'/admin');
require('boot.php');
__boot();
```

What it does
----
  * By just loading the file
   * Sets all PHP errors to be exceptions
   * Sets up a default exception handler to print friendlier error messages than PHP does by default for uncaught exceptions
   * Sets the default timezone to UTC
   * Sets the ROOT constant if not already defined
  * By calling the __boot() function
   * Calls __boot_pre()
   * Calls __boot_post()
  * By calling __boot_pre()
   * Loads all the dynamic config files and user overrides
   * Sets the timezone from the config
  * By calling __boot_post()
   * Dynamically loads all module init code
   * Loads the Composer autoload file (which will init composer modules and enable autoloading)

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

### (void) __e($err=array())
Loads error codes
  * Array should be in the following format
   * CODE => 'CONSTANT'
    * EG: 1001 => 'E_USER_INVALID'
NOTE: Will throw a PHP E_NOTICE if there is a code or constant conflict
