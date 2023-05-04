Nimbusec, PHP API Client
========================

[![Total Downloads](https://poser.pugx.org/nimbusec/nimbusec-php/downloads)](https://packagist.org/packages/nimbusec/nimbusec-php)
[![Latest Stable Version](https://poser.pugx.org/nimbusec/nimbusec-php/v/stable)](https://packagist.org/packages/nimbusec/nimbusec-php)
[![License](https://poser.pugx.org/nimbusec/nimbusec-php/license)](https://packagist.org/packages/nimbusec/nimbusec-php)

The official Nimbusec API client written in PHP.

It provides an interface for communicating and requesting our internal system, easily and securely. The authentication is done through OAuth and it uses GuzzleHTTP for HTTP requests. Fully integrable with Composer, the client conforms to PSR-4 for autoloading.

It covers most of our interal resources and gives the functionality for quering and submit to them, respectively.

More information about the structure of our API can be found at [our knowledge base](https://kb.nimbusec.com/API/API).

Requirements
---------------

The API client requires PHP >=5.6.0 to run successfully.

Installing Client
--------------------

The recommended way is through [Composer](https://getcomposer.org/).

```bash
# Install Composer in the current directory (the default name will be composer.phar)
$ curl -sS https://getcomposer.org/installer | php

# or install it globally
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

Next, run the composer command to install the latest stable version of the API client.

```bash
# Local installation
php composer.phar require nimbusec/nimbusec-php

# or
composer require nimbusec/nimbusec-php
```

After installing, use Composer's autoloader to get it running:

```php
<?php
require ("vendor/autoload.php")

use Nimbusec\API as API;

// put code in here
...
```

Keep the client up-to-date by running Composer's update command:

```bash
composer update nimbusec/nimbusec-php
```

Basic usage
-----------

```php
<?php

// Include autoloader to load Nimbusec API automatically.
require_once("vendor/autoload.php");

// Write alias for Nimbusec API.
use Nimbusec\API as API;

// Set credentials.
$NIMBUSEC_KEY = "YOUR KEY";
$NIMBUSEC_SECRET = "YOUR SECRET";

// Create a Nimbusec API client instance.
// The default URL parameter can be omitted.
$api = new API($NIMBUSEC_KEY, $NIMBUSEC_SECRET, API::DEFAULT_URL);

try {
    // Fetch domains.
    $domains = $api->findDomains();
    foreach ($domains as $domain) {
        echo $domain["name"] . "\n";
    }

} catch (Exception $e) {
    echo "[x] an error occured: {$e->getMessage()}\n";
}
```

Take a look at our provided [example scripts](https://github.com/cumulodev/nimbusec-php/blob/master/examples) for futher usages.
Note that some examples cannot be executed dynamically, whenever this is the case, you'll find a "TODO:"-comment with a short description on how to use the corresponding endpoint. 
**Mind that these examples do naturally change entries in the database!** They should only be used as a reference in combination with our [swagger documentation](https://openapi.nimbusec.com/#/)

Contribution
------------

Want to help improving our API client by finding bugs?
Great! Then clone or fork this repository and install the development dependencies with Composer:

```bash
git clone https://github.com/cumulodev/nimbusec-php cumulodev/nimbusec-php
cd cumulodev/nimbusec-php
composer update
```

This installs all PHPUnit dependencies that you need to run our Unittest.

Our Unittests can be found at /tests and expect three environment variables to be set:

```bash
export SDK_KEY="your key"
export SDK_SECRET="your key"
export SDK_URL="https://api.nimbusec.com"

# another one is optional but not required for all tests
export SDK_BUNDLE="the id of your bundle"
```

When having them set, run the Composer script to test them:

```bash
composer test

# alternatively
./vendor/bin/phpunit --verbose
```

Did you encouter any problems? Then contact us at via email (see below), write an issue or even provide a solution by a pull request.
Of course, you are also free to investigate our code and report anything suspicious.

Thank you for help. We appreciate it.

Further Information
-------------------

For further information please visit [https://nimbusec.com](https://nimbusec.com) or you can write us an e-mail to office@nimbusec.com


