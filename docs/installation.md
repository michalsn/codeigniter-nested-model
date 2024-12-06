# Installation

- [Composer Installation](#composer-installation)
- [Manual Installation](#manual-installation)

## Composer Installation

The only thing you have to do is to run this command, and you're ready to go.

```console
composer require michalsn/codeigniter-nested-model
```

## Manual Installation

In the example below we will assume that files from this project will be located in `app/ThirdParty/nested-model` directory.

Download this project and then enable it by editing the `app/Config/Autoload.php` file and adding the `Michalsn\CodeIgniterNestedModel` namespace to the `$psr4` array. You also have to add `Common.php` to the `$files` array, like in the below example:

```php
<?php

// ...

public $psr4 = [
    APP_NAMESPACE => APPPATH, // For custom app namespace
    'Michalsn\CodeIgniterNestedModel' => APPPATH . 'ThirdParty/nested-model/src',
];

// ...

public $files = [
    APPPATH . 'ThirdParty/nested-model/src/Common.php',
];

// ...

```
