# Yii2 Settings

Расширение для хранения динамических настроек приложения Yii2 в базе данных с поддержкой кэширования и автоматического приведения типов.

[English version](../README.md)

[![Latest Stable Version](https://poser.pugx.org/ed-smartass/yii2-settings/v/stable)](https://packagist.org/packages/ed-smartass/yii2-settings)
[![Total Downloads](https://poser.pugx.org/ed-smartass/yii2-settings/downloads)](https://packagist.org/packages/ed-smartass/yii2-settings)
[![License](https://poser.pugx.org/ed-smartass/yii2-settings/license)](https://packagist.org/packages/ed-smartass/yii2-settings)

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

Включите `processConfig` и добавьте компонент в `bootstrap`, чтобы обработчик события был зарегистрирован при старте приложения:

```php
return [
    'bootstrap' => ['settings'],
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

MIT. См. [LICENSE](../LICENSE).
