<?php

namespace Smartass\Yii2Settings;

use Yii;
use yii\base\Component;
use yii\db\Query;
use yii\helpers\ArrayHelper;
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
     * Таблица хранения настроек.
     *
     * @var string
     */
    public $table = '{{%setting}}';

    /**
     * Название компонента БД. 
     *
     * @var string
     */
    public $dbComponent = 'db';

    /**
     * Использование кеша. 
     *
     * @var boolean
     */
    public $cache = false;

    /**
     * Название компонента кеша. 
     *
     * @var string
     */
    public $cacheComponent = 'cache';

    /**
     * Ключ хранения настроек в кеше. 
     *
     * @var string
     */
    public $cacheKey = 'settings';

    /**
     * Длительность кеширования. 
     *
     * @var int|null
     */
    public $cacheDuration = null;

    /**
     * Настройки. 
     *
     * @var array|null
     */
    protected $_settings;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        /** @todo Валидация параметров */
    }

    /**
     * {@inheritdoc}
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return isset($this->settings[$name]) || parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if (isset($this->settings[$name])) {
            return $this->get($name);
        }

        return parent::__get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return isset($this->settings[$name]) || parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (isset($this->settings[$name])) {
            $this->set($name, $value);
        } else {
            parent::__set($name, $value);
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
                $this->_settings = Yii::$app->get($this->cacheComponent)->getOrSet($this->cacheKey, function() {
                    return $this->fetchSettings();
                }, $this->cacheDuration);
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

        foreach($query->each(100, Yii::$app->get($this->dbComponent)) as $setting) {
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
                break;
            case static::TYPE_INTEGER:
                return (int)$value;
                break;
            case static::TYPE_FLOAT:
                return (float)$value;
                break;
            case static::TYPE_BOOLEAN:
                return (bool)$value;
                break;
            case static::TYPE_STRING:
                return (string)$value;
                break;
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
        $value = ArrayHelper::getValue($this->settings, $key);

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
        if ($this->_settings === null) {
            $this->_settings = $this->fetchSettings();  
        }

        if ($value === null) {
            unset($this->_settings[$key]);

            if ($this->cache) {
                Yii::$app->get($this->cacheComponent)->set($this->cacheKey, $this->_settings, $this->cacheDuration);
            }

            return;
        }

        if (!$type) {
            $type = $this->detectType($value);
        }
        
        $exists = (new Query())
            ->from($this->table)
            ->andWhere(['key' => $key])
            ->limit(1)
            ->exists(Yii::$app->get($this->dbComponent));

        if ($exists) {
            Yii::$app->get($this->dbComponent)
                ->createCommand()
                ->update($this->table, [
                    'type' => $type,
                    'value' => $this->encodeValue($value, $type),
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['key' => $key])
                ->execute();
        } else {
            Yii::$app->get($this->dbComponent)
                ->createCommand()
                ->insert($this->table, [
                    'key' => $key,
                    'type' => $type,
                    'value' => $this->encodeValue($value, $type)
                ])
                ->execute();
        }

        ArrayHelper::setValue($this->_settings, $key, $value);

        if ($this->cache) {
            Yii::$app->get($this->cacheComponent)->set($this->cacheKey, $this->_settings, $this->cacheDuration);
        }
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
        Yii::$app->get($this->dbComponent)
            ->createCommand()
            ->delete($this->table)
            ->execute();

        if ($this->cache) {
            Yii::$app->get($this->cacheComponent)
                ->delete($this->cacheComponent);
        }
    }
}
