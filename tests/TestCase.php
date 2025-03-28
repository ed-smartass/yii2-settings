<?php

namespace Smartass\Yii2Settings\Tests;

use yii\di\Container;
use yii\web\Application;
use yii\helpers\ArrayHelper;

/**
 * Base test case class for Yii2 Settings tests
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        $this->destroyApplication();
        parent::tearDown();
    }

    /**
     * Creates a mock application
     * @param array $config
     */
    protected function mockApplication($config = [])
    {
        new Application(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'db' => [
                    'class' => \yii\db\Connection::class,
                    'dsn' => 'sqlite::memory:',
                ],
                'cache' => [
                    'class' => 'yii\caching\ArrayCache',
                ],
                'settings' => [
                    'class' => 'Smartass\Yii2Settings\Settings',
                ],
            ],
        ], $config));
    }

    /**
     * Destroys the mock application
     */
    protected function destroyApplication()
    {
        \Yii::$app = null;
        \Yii::$container = new Container();
    }
}
