<?php

namespace Smartass\Yii2Settings;

use Yii;
use yii\base\Application;
use yii\base\Component;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\Json;

/**
 * Компонент настроек приложения. 
 * 
 * @property array $settings
 * 
 * @author Smartass <ed.smartass@gmail.com>
 */
class Settings extends Component
{
    /**
     * Тип: `Целое число`
     */
    const TYPE_INTEGER = 'integer';

    /**
     * Тип: `Число`
     */
    const TYPE_FLOAT = 'float';

    /**
     * Тип: `Строка`
     */
    const TYPE_STRING = 'string';

    /**
     * Тип: `Булевое значение`
     */
    const TYPE_BOOLEAN = 'boolean';

    /**
     * Тип: `Массив`
     */
    const TYPE_ARRAY = 'array';

    /**
     * @var string
     */
    public $table = '{{%setting}}';

    /**
     * @var \yii\db\Connection
     */
    public $db = 'db';

    /**
     * @var \yii\caching\Cache|null
     */
    public $cache = 'cache';

    /**
     * @var string
     */
    public $cacheKey = 'settings';

    /**
     * @var int|null
     */
    public $cacheDuration = null;

    /**
     * Обработка конфига приложения. 
     *
     * @var boolean
     */
    public $processConfig = false;

    /**
     * Настройки.
     *
     * @var array|null
     */
    protected $_settings;

    /**
     * @var bool
     */
    protected $_initialized = false;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, \yii\db\Connection::class);

        $this->cache = $this->cache ? Instance::ensure($this->cache, \yii\caching\Cache::class) : null;

        if ($this->cache && !$this->cacheKey) {
            throw new \yii\base\InvalidConfigException('The "cacheKey" property must be set when using cache.');
        }

        $this->_initialized = true;

        if ($this->processConfig) {
            Yii::$app->on(Application::EVENT_BEFORE_ACTION, function() {
                foreach (Yii::$app->getComponents() as $id => $definition) {
                    if (is_array($definition) && !Yii::$app->has($id, true)) {
                        Yii::$app->set($id, $this->processConfig($definition));
                    }
                }
            });
        }
    }

    /**
     * @param array $definitions
     * @return array
     */
    protected function processConfig($definitions)
    {
        if (is_array($definitions)) {
            foreach($definitions as $key => $value) {
                if (is_string($value)) {
                    if (preg_match('/^%([^|%]+)\|([^%]*)%$/', $value, $matches) === 1) {
                        $definitions[$key] = $this->get($matches[1], $matches[2]);
                    } else if (preg_match('/^%([^|%]+)%$/', $value, $matches) === 1) {
                        $settings = $this->getSettings();
                        if (array_key_exists($matches[1], $settings)) {
                            $definitions[$key] = $settings[$matches[1]];
                        }
                    }
                    
                } else if (is_array($value)) {
                    $definitions[$key] = $this->processConfig($definitions[$key]);
                }
            }
        }

        return $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return array_key_exists($name, $this->getSettings()) || parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->getSettings())) {
            return $this->get($name);
        }

        return parent::__get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (parent::canSetProperty($name, $checkVars, $checkBehaviors)) {
            return true;
        }

        // Read-only properties (getter exists, no setter) are not settable
        return !method_exists($this, 'get' . $name) && !method_exists($this, 'set' . $name);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (parent::canSetProperty($name)) {
            parent::__set($name, $value);
        } elseif (method_exists($this, 'get' . $name) || !$this->_initialized) {
            parent::__set($name, $value); // throws read-only / unknown property exception
        } else {
            $this->set($name, $value);
        }
    }

    /**
     * Получение названий настроек. 
     *
     * @return array
     */
    public function getSettings()
    {
        if ($this->_settings === null) {
            if ($this->cache) {
                $tableName = $this->db->getSchema()->getRawTableName($this->table);
                $this->_settings = $this->cache->getOrSet($this->cacheKey, function() {
                    return $this->fetchSettings();
                }, $this->cacheDuration, new \yii\caching\DbDependency([
                    'db' => $this->db,
                    'sql' => 'SELECT MAX(updated_at), COUNT(*) FROM ' . $this->db->quoteTableName($tableName),
                ]));
            } else {
                $this->_settings = $this->fetchSettings();
            }
        }

        return $this->_settings;
    }

    /**
     * Получение настроек из БД. 
     *
     * @return array
     */
    protected function fetchSettings()
    {
        $settings = [];

        $query = (new Query())
            ->from($this->table);

        foreach($query->each(100, $this->db) as $setting) {
            $settings[$setting['key']] = $this->decodeValue($setting['value'], $setting['type']);
        }

        return $settings;
    }

    /**
     * Декодирование значения. 
     *
     * @param array $setting
     * @return mixed
     */
    protected function decodeValue($value, $type)
    {
        switch ($type) {
            case static::TYPE_ARRAY:
                return Json::decode($value);
            case static::TYPE_INTEGER:
                return (int)$value;
            case static::TYPE_FLOAT:
                return (float)$value;
            case static::TYPE_BOOLEAN:
                return (bool)$value;
            case static::TYPE_STRING:
            default:
                return (string)$value;
        }
    }

    /**
     * Кодирование значения. 
     *
     * @return void
     */
    protected function encodeValue($value, $type)
    {
        if ($type === static::TYPE_ARRAY) {
            return Json::encode($value);
        } else if ($type === static::TYPE_BOOLEAN) {
            return (int)$value;
        } else {
            return (string)$value;
        }
    }

    /**
     * Определение типа. 
     *
     * @param mixed $value
     * @return string
     */
    protected function detectType($value)
    {
        if (is_bool($value)) {
            return static::TYPE_BOOLEAN;
        } else if (is_array($value)) {
            return static::TYPE_ARRAY;
        } else if (is_integer($value)) {
            return static::TYPE_INTEGER;
        } else if (is_float($value)) {
            return static::TYPE_FLOAT;
        } else {
            return static::TYPE_STRING;
        }
    }

    /**
     * Получение значения настройки. 
     *
     * @param string $key
     * @param mixed $default
     * @param bool $saveDefault
     * @return mixed
     */
    public function get($key, $default = null, $saveDefault = false)
    {
        $settings = $this->getSettings();
        $value = array_key_exists($key, $settings) ? $settings[$key] : null;

        if ($value === null) {
            $value = $default;

            if ($saveDefault) {
                $this->set($key, $default);
            }
        }

        return $value;
    }

    /**
     * Сохранение настройки. 
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return void
     */
    public function set($key, $value, $type = null)
    {
        if ($value === null) {
            $this->db->createCommand()
                ->delete($this->table, ['key' => $key])
                ->execute();
        } else {
            if (!in_array($type, [static::TYPE_INTEGER, static::TYPE_FLOAT, static::TYPE_STRING, static::TYPE_BOOLEAN, static::TYPE_ARRAY])) {
                $type = $this->detectType($value);
            }
            
            $now = date('Y-m-d H:i:s');

            $this->db->createCommand()->upsert($this->table, [
                'key' => $key,
                'type' => $type,
                'value' => $this->encodeValue($value, $type),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'type' => $type,
                'value' => $this->encodeValue($value, $type),
                'updated_at' => $now,
            ])->execute();
        }

        $this->refresh();
    }

    /**
     * Удаление настройки. 
     *
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        $this->set($key, null);
    }

    /**
     * Удаление всех настроек. 
     *
     * @return void
     */
    public function flush()
    {
        $this->db
            ->createCommand()
            ->delete($this->table)
            ->execute();
            
        $this->refresh();
    }

    /**
     * @return void
     */
    public function refresh()
    {
        $this->_settings = null;

        if ($this->cache) {
            $this->cache->delete($this->cacheKey);
        }
    }
}
