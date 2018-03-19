# Installation

## Requirements

* eZ Platform 2+
* PHP 7.1+

## Installation steps

Run `composer require ezsystems/ezplatform-automated-translation` to install the bundle and its dependencies:

### Register the bundle

Activate the bundle in `app\AppKernel.php` file.

```php
// app\AppKernel.php

public function registerBundles()
{
   ...
   $bundles = array(
       new FrameworkBundle(),
       ...
       // eZ Platform Automated Translation Bundle
       new EzSystems\EzPlatformAutomatedTranslationBundle\EzPlatformAutomatedTranslationBundle()
   );
   ...
}
```

