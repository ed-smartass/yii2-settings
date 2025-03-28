# Yii2 Settings

A simple, flexible and efficient key-value storage extension for Yii2 applications. This extension allows you to store application settings in the database and optionally cache them for better performance.

[![Latest Stable Version](https://poser.pugx.org/ed-smartass/yii2-settings/v/stable)](https://packagist.org/packages/ed-smartass/yii2-settings)
[![Total Downloads](https://poser.pugx.org/ed-smartass/yii2-settings/downloads)](https://packagist.org/packages/ed-smartass/yii2-settings)
[![License](https://poser.pugx.org/ed-smartass/yii2-settings/license)](https://packagist.org/packages/ed-smartass/yii2-settings)

## Features

- Store application settings in a database table
- Support for multiple data types (integer, float, string, boolean, array)
- Automatic type detection
- Cache integration for improved performance
- Access settings as component properties
- Process application configuration with settings
- Easy to integrate with existing Yii2 applications

### Installation

1. Install via composer
```
composer require ed-smartass/yii2-settings
```

2. Apply migrations

```
php yii migrate --migrationPath=@vendor/ed-smartass/yii2-settings/src/migrations
```

Or add to console config
```php
return [
    // ...
    'controllerMap' => [
        // ...
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@console/migrations', // Default migration folder
                '@vendor/ed-smartass/yii2-settings/src/migrations'
            ]
        ]
        // ...
    ]
    // ...
];
```

3. Config application
```php
return [
    // ...
    'bootstrap' => [
        // ...
        'settings'
    ],
    'components' => [
        // ...
        'settings' => [
            'class' => 'Smartass\Yii2Settings\Settings',

            // Name of table with settings. Default: `{{%setting}}`.
            'table' => '{{%setting}}',

            // ID of db component in application. Default: `db`. 
            'db' => 'db',

            // ID of cache component in application. Default: `cache`.
            // Set to `null` to disable caching. 
            'cache' => 'cache', 

            // Key for storing settings in cache. Default: `settings`.
            'cacheKey' => 'settings', 

            // Cache duration for settings. Default: `null` (no expiration).
            'cacheDuration' => null, 

            // If you want to change Application config set to `true`
            // and set value at config like `'language' => '%main.language|ru%'`.
            // Where: `main.language` is setting name and `ru` is default value
            // Default: `false`.
            'processConfig' => false, 
        ]
    ]
    // ...
];
```

### Usage

```php
// List all settings
Yii::$app->settings->settings

// Get setting
Yii::$app->settings->get('access_token_ttl');
// Or
Yii::$app->settings->access_token_ttl;

// Set setting
Yii::$app->settings->set('access_token_ttl', 3600*24*7);
// Or
Yii::$app->settings->access_token_ttl = 3600*24*7; // Only if setting `access_token_ttl` already exists

// Delete setting
Yii::$app->settings->delete('access_token_ttl');
// Or
Yii::$app->settings->set('access_token_ttl', null);

// Delete all settings
Yii::$app->settings->flush();

// Refresh cache
Yii::$app->settings->refresh();
```

### Methods

* **get($key, $default = null, $saveDefault = false)** — get setting by key
    * **$key** — key of setting
    * **$default** — default value if setting does not exist
    * **$saveDefault** — save default value if setting does not exist

* **set($key, $value, $type = null)** — set setting
    * **$key** — key of setting
    * **$value** — value of setting (if value is `null` setting will be deleted)
    * **$type** — type of setting (`integer`, `float`, `string`, `boolean`, `array`), if type is `null` then type will be automatically detected

* **delete($key)** — delete setting by key
    * **$key** — key of setting

* **flush()** — delete all settings

* **refresh()** — clear the internal settings cache and delete cache entry if caching is enabled

### Supported Value Types

* `integer` — Integer values
* `float` — Float values
* `string` — String values
* `boolean` — Boolean values
* `array` — Array values (stored as JSON)
