# Yii2 Settings

Dynamic key-value settings for Yii2 with database storage, automatic type casting, and caching.

[![Latest Stable Version](https://poser.pugx.org/ed-smartass/yii2-settings/v/stable)](https://packagist.org/packages/ed-smartass/yii2-settings)
[![Total Downloads](https://poser.pugx.org/ed-smartass/yii2-settings/downloads)](https://packagist.org/packages/ed-smartass/yii2-settings)
[![License](https://poser.pugx.org/ed-smartass/yii2-settings/license)](https://packagist.org/packages/ed-smartass/yii2-settings)

## Features

- Database-backed key-value storage
- Five value types: `integer`, `float`, `string`, `boolean`, `array` (JSON)
- Automatic type detection on write, strict type casting on read
- Optional caching with `DbDependency` auto-invalidation
- Magic property access: `$settings->key` to read/write
- Config placeholders: inject settings into component configs via `%setting.name|default%`
- Works with any RDBMS supported by Yii2 (MySQL, PostgreSQL, SQLite, etc.)
- PHP 7.3+ and PHP 8.x compatible

## Installation

```bash
composer require ed-smartass/yii2-settings
```

Run the migration:

```bash
php yii migrate --migrationPath=@vendor/ed-smartass/yii2-settings/src/migrations
```

Or register the migration path in console config:

```php
return [
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@console/migrations',
                '@vendor/ed-smartass/yii2-settings/src/migrations',
            ],
        ],
    ],
];
```

## Configuration

```php
return [
    'components' => [
        'settings' => [
            'class' => 'Smartass\Yii2Settings\Settings',

            // Table name. Default: '{{%setting}}'
            'table' => '{{%setting}}',

            // DB connection component ID. Default: 'db'
            'db' => 'db',

            // Cache component ID, or null to disable caching. Default: 'cache'
            'cache' => 'cache',

            // Cache key prefix. Default: 'settings'
            'cacheKey' => 'settings',

            // Cache TTL in seconds, null = no expiration. Default: null
            'cacheDuration' => null,

            // Replace %placeholders% in component configs. Default: false
            'processConfig' => false,
        ],
    ],
];
```

## Usage

### Basic operations

```php
$settings = Yii::$app->settings;

// Set
$settings->set('site.name', 'My App');
$settings->set('per_page', 25);
$settings->set('maintenance', false);
$settings->set('mail.providers', ['smtp', 'sendmail']);

// Get
$settings->get('site.name');                    // 'My App'
$settings->get('missing_key');                  // null
$settings->get('missing_key', 'fallback');      // 'fallback'
$settings->get('theme', 'default', true);       // 'default' (also saved to DB)

// Delete
$settings->delete('site.name');

// Delete all
$settings->flush();

// Force reload from database
$settings->refresh();

// Get all settings as array
$settings->settings; // ['per_page' => 25, 'maintenance' => false, ...]
```

### Magic property access

Magic properties work for keys that are valid PHP identifiers (e.g. `site_name`).
Keys containing dots or hyphens (e.g. `site.name`, `smtp-host`) require `get()`/`set()` or brace syntax.

```php
// Read / write simple keys
$name = Yii::$app->settings->site_name;
Yii::$app->settings->site_name = 'New Name';

// Dot / hyphen keys — use get()/set() or brace syntax
$host = Yii::$app->settings->get('mail.smtp-host');
Yii::$app->settings->set('mail.smtp-host', 'smtp.example.com');
$host = Yii::$app->settings->{'mail.smtp-host'};
```

### Explicit type override

By default the type is detected automatically. You can force a specific type:

```php
use Smartass\Yii2Settings\Settings;

// Store numeric string as integer
$settings->set('port', '8080', Settings::TYPE_INTEGER);

// Store integer as string
$settings->set('code', 42, Settings::TYPE_STRING);
```

### Config placeholders

Enable `processConfig` to inject settings into not-yet-initialized component configs:

```php
// app config
return [
    'components' => [
        'settings' => [
            'class' => 'Smartass\Yii2Settings\Settings',
            'processConfig' => true,
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => '%mail.host|smtp.example.com%',  // setting name | default
                'port' => '%mail.port%',                    // setting name (no default)
            ],
        ],
    ],
];
```

Syntax: `%setting.name|default_value%` or `%setting.name%`

## API Reference

### `get($key, $default = null, $saveDefault = false)`

Returns the value for `$key`, or `$default` if not found. When `$saveDefault` is `true`, the default value is persisted to the database.

### `set($key, $value, $type = null)`

Creates or updates a setting. Passing `null` as `$value` deletes the setting. The `$type` parameter accepts one of the `Settings::TYPE_*` constants; when omitted, the type is detected automatically.

### `delete($key)`

Deletes a setting by key.

### `flush()`

Deletes all settings.

### `refresh()`

Clears the in-memory cache and invalidates the cache entry, forcing the next read to hit the database.

### Property: `$settings`

Returns all settings as an associative array `['key' => value, ...]`.

## Supported Types

| Type | Constant | PHP type | Storage |
|------|----------|----------|---------|
| `integer` | `Settings::TYPE_INTEGER` | `int` | String representation |
| `float` | `Settings::TYPE_FLOAT` | `float` | String representation |
| `string` | `Settings::TYPE_STRING` | `string` | As-is |
| `boolean` | `Settings::TYPE_BOOLEAN` | `bool` | `0` / `1` |
| `array` | `Settings::TYPE_ARRAY` | `array` | JSON |

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).

---

# Yii2 Settings (Русский)

Расширение для хранения динамических настроек приложения Yii2 в базе данных с поддержкой кэширования и автоматического приведения типов.

## Возможности

- Хранение настроек в таблице БД (ключ-значение)
- Пять типов данных: `integer`, `float`, `string`, `boolean`, `array` (JSON)
- Автоматическое определение типа при записи, строгое приведение при чтении
- Кэширование с автоматической инвалидацией через `DbDependency`
- Доступ через магические свойства: `$settings->key`
- Подстановка настроек в конфиги компонентов: `%setting.name|default%`
- Работает с любой СУБД, поддерживаемой Yii2
- Совместим с PHP 7.3+ и PHP 8.x

## Установка

```bash
composer require ed-smartass/yii2-settings
```

Выполните миграцию:

```bash
php yii migrate --migrationPath=@vendor/ed-smartass/yii2-settings/src/migrations
```

Или зарегистрируйте путь миграций в конфигурации консоли:

```php
return [
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationPath' => [
                '@console/migrations',
                '@vendor/ed-smartass/yii2-settings/src/migrations',
            ],
        ],
    ],
];
```

## Конфигурация

```php
return [
    'components' => [
        'settings' => [
            'class' => 'Smartass\Yii2Settings\Settings',

            // Имя таблицы. По умолчанию: '{{%setting}}'
            'table' => '{{%setting}}',

            // Компонент подключения к БД. По умолчанию: 'db'
            'db' => 'db',

            // Компонент кэша или null для отключения. По умолчанию: 'cache'
            'cache' => 'cache',

            // Ключ кэша. По умолчанию: 'settings'
            'cacheKey' => 'settings',

            // Время жизни кэша (сек), null = бессрочно. По умолчанию: null
            'cacheDuration' => null,

            // Подставлять %плейсхолдеры% в конфиги компонентов. По умолчанию: false
            'processConfig' => false,
        ],
    ],
];
```

## Использование

### Основные операции

```php
$settings = Yii::$app->settings;

// Запись
$settings->set('site.name', 'Мой сайт');
$settings->set('per_page', 25);
$settings->set('maintenance', false);
$settings->set('mail.providers', ['smtp', 'sendmail']);

// Чтение
$settings->get('site.name');                    // 'Мой сайт'
$settings->get('missing_key');                  // null
$settings->get('missing_key', 'fallback');      // 'fallback'
$settings->get('theme', 'default', true);       // 'default' (также сохраняется в БД)

// Удаление
$settings->delete('site.name');

// Удаление всех настроек
$settings->flush();

// Принудительная перезагрузка из БД
$settings->refresh();

// Все настройки в виде массива
$settings->settings; // ['per_page' => 25, 'maintenance' => false, ...]
```

### Магические свойства

Магические свойства работают для ключей, являющихся допустимыми идентификаторами PHP (например `site_name`).
Для ключей с точками или дефисами (например `site.name`, `smtp-host`) используйте `get()`/`set()` или фигурные скобки.

```php
// Чтение / запись простых ключей
$name = Yii::$app->settings->site_name;
Yii::$app->settings->site_name = 'Новое имя';

// Ключи с точками / дефисами — через get()/set() или фигурные скобки
$host = Yii::$app->settings->get('mail.smtp-host');
Yii::$app->settings->set('mail.smtp-host', 'smtp.example.com');
$host = Yii::$app->settings->{'mail.smtp-host'};
```

### Явное указание типа

```php
use Smartass\Yii2Settings\Settings;

// Сохранить числовую строку как integer
$settings->set('port', '8080', Settings::TYPE_INTEGER);

// Сохранить число как string
$settings->set('code', 42, Settings::TYPE_STRING);
```

### Подстановка в конфиги

Включите `processConfig` для подстановки настроек в конфиги ещё не инициализированных компонентов:

```php
return [
    'components' => [
        'settings' => [
            'class' => 'Smartass\Yii2Settings\Settings',
            'processConfig' => true,
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => '%mail.host|smtp.example.com%',  // имя настройки | значение по умолчанию
                'port' => '%mail.port%',                    // имя настройки (без значения по умолчанию)
            ],
        ],
    ],
];
```

Синтаксис: `%setting.name|default_value%` или `%setting.name%`

## Справочник API

| Метод | Описание |
|-------|----------|
| `get($key, $default, $saveDefault)` | Получить значение. `$saveDefault = true` сохраняет значение по умолчанию в БД |
| `set($key, $value, $type)` | Создать/обновить настройку. `$value = null` удаляет запись |
| `delete($key)` | Удалить настройку |
| `flush()` | Удалить все настройки |
| `refresh()` | Сбросить кэш, следующее чтение обратится к БД |
| `$settings->settings` | Все настройки в виде массива `['key' => value, ...]` |

## Типы данных

| Тип | Константа | PHP-тип | Хранение |
|-----|-----------|---------|----------|
| `integer` | `Settings::TYPE_INTEGER` | `int` | Строковое представление |
| `float` | `Settings::TYPE_FLOAT` | `float` | Строковое представление |
| `string` | `Settings::TYPE_STRING` | `string` | Как есть |
| `boolean` | `Settings::TYPE_BOOLEAN` | `bool` | `0` / `1` |
| `array` | `Settings::TYPE_ARRAY` | `array` | JSON |

## Тестирование

```bash
composer install
vendor/bin/phpunit
```

## Лицензия

MIT. См. [LICENSE](LICENSE).
