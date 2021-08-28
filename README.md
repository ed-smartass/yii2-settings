# yii2-settings


### Installation

1. Install via composer
```
composer require ed-smartass/yii2-settings
```

2. Apply migrations

```
php yii migrate --migrationPath=@vendor/ed-smartass/yii2-settings/migrations
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
                '@vendor/ed-smartass/yii2-settings/migrations'
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
    'components' => [
        // ...
        'settings' => [
            'class' => 'Smartass\Yii2Settings\Settings',

            // Name of table with settings. Default: `{{%setting}}`.
            'table' => '{{%setting}}',

            // ID of db commponent in application. Default: `db`. 
            'dbComponent' => 'db',

            // If you want to cache setting. Default: `false`.
            'cache' => true, 

            // ID of cache commponent in application. Default: `cache`.
            'cacheComponent' => 'cache', 

            // Key for storing settings in cache. Default: `settings`.
            'cacheKey' => 'settings', 

            // Cache duration for settings. Default: `null`.
            'cacheDuration' => null, 
        ]
        // ...
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
Yii::$app->settings->access_token_ttl = 3600*24*7; // Only if setting `access_token_ttl` already exist
```


### Methods

* **get($key, $default = null, $saveDefault = false)** — get setting by key
    * **$key** — key of setting
    * **$default** — default value if setting does not exist
    * **$saveDefault** — save default value if setting does not exist

* **set($key, $value, $type = null)** — get setting by key
    * **$key** — key of setting
    * **$value** — value of setting (if value is `null` setting will be deleted)
    * **$type** — type of setting (`integer`, `float`, `string`, `boolean`, `array`), if type is `null` when type will be auto detected

* **delete($key)** — delete setting by key
    * **$key** — key of setting

* **flush($key)** — deleting all settings
