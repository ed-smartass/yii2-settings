<?php

namespace Smartass\Yii2Settings\Tests;

use Smartass\Yii2Settings\Settings;

/**
 * Tests for Settings component
 */
class SettingsTest extends TestCase
{
    /**
     * @var Settings
     */
    protected $settings;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table matching the actual migration schema
        \Yii::$app->db->createCommand(
            'CREATE TABLE {{%setting}} (
                "key" VARCHAR(255) NOT NULL PRIMARY KEY,
                "type" VARCHAR(255),
                "value" TEXT,
                "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
                "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        )->execute();

        $this->settings = \Yii::$app->settings;
    }

    protected function tearDown(): void
    {
        try {
            \Yii::$app->db->createCommand()->dropTable('{{%setting}}')->execute();
        } catch (\Exception $e) {
            // Table may not exist if the test recreated the application
        }
        parent::tearDown();
    }

    // ─── set() / type detection ───

    public function testSetString()
    {
        $this->settings->set('key', 'hello');
        $this->assertSame('hello', $this->settings->get('key'));
    }

    public function testSetInteger()
    {
        $this->settings->set('key', 42);
        $this->assertSame(42, $this->settings->get('key'));
        $this->assertIsInt($this->settings->get('key'));
    }

    public function testSetFloat()
    {
        $this->settings->set('key', 3.14);
        $this->assertSame(3.14, $this->settings->get('key'));
        $this->assertIsFloat($this->settings->get('key'));
    }

    public function testSetBoolean()
    {
        $this->settings->set('bool_true', true);
        $this->assertSame(true, $this->settings->get('bool_true'));
        $this->assertIsBool($this->settings->get('bool_true'));

        $this->settings->set('bool_false', false);
        // false is encoded as 0, decoded via (bool) → false
        $this->assertSame(false, $this->settings->get('bool_false'));
    }

    public function testSetArray()
    {
        $array = ['a' => 1, 'b' => [2, 3]];
        $this->settings->set('key', $array);
        $this->assertSame($array, $this->settings->get('key'));
        $this->assertIsArray($this->settings->get('key'));
    }

    public function testSetEmptyString()
    {
        $this->settings->set('key', '');
        $this->assertSame('', $this->settings->get('key'));
    }

    public function testSetZeroInteger()
    {
        $this->settings->set('key', 0);
        $this->assertSame(0, $this->settings->get('key'));
        $this->assertIsInt($this->settings->get('key'));
    }

    public function testSetEmptyArray()
    {
        $this->settings->set('key', []);
        $this->assertSame([], $this->settings->get('key'));
    }

    public function testSetExplicitType()
    {
        // Store integer as string explicitly
        $this->settings->set('key', 42, Settings::TYPE_STRING);
        $this->assertSame('42', $this->settings->get('key'));
        $this->assertIsString($this->settings->get('key'));
    }

    public function testSetOverwritesExistingValue()
    {
        $this->settings->set('key', 'first');
        $this->assertSame('first', $this->settings->get('key'));

        $this->settings->set('key', 'second');
        $this->assertSame('second', $this->settings->get('key'));
    }

    public function testSetOverwritesWithDifferentType()
    {
        $this->settings->set('key', 'string');
        $this->assertSame('string', $this->settings->get('key'));

        $this->settings->set('key', 123);
        $this->assertSame(123, $this->settings->get('key'));
        $this->assertIsInt($this->settings->get('key'));
    }

    public function testSetNullDeletesSetting()
    {
        $this->settings->set('key', 'value');
        $this->assertSame('value', $this->settings->get('key'));

        $this->settings->set('key', null);
        $this->assertNull($this->settings->get('key'));
    }

    // ─── get() ───

    public function testGetNonExistentReturnsNull()
    {
        $this->assertNull($this->settings->get('no_such_key'));
    }

    public function testGetNonExistentReturnsDefault()
    {
        $this->assertSame('fallback', $this->settings->get('no_such_key', 'fallback'));
    }

    public function testGetWithSaveDefault()
    {
        $result = $this->settings->get('auto_saved', 'default_val', true);
        $this->assertSame('default_val', $result);

        // The default was persisted
        $this->assertSame('default_val', $this->settings->get('auto_saved'));
    }

    public function testGetDoesNotSaveDefaultWhenFlagIsFalse()
    {
        $this->settings->get('tmp', 'default_val', false);
        $this->assertNull($this->settings->get('tmp'));
    }

    // ─── delete() ───

    public function testDelete()
    {
        $this->settings->set('to_delete', 'value');
        $this->assertSame('value', $this->settings->get('to_delete'));

        $this->settings->delete('to_delete');
        $this->assertNull($this->settings->get('to_delete'));
    }

    public function testDeleteNonExistentKeyDoesNotFail()
    {
        // Should not throw
        $this->settings->delete('nonexistent');
        $this->assertNull($this->settings->get('nonexistent'));
    }

    // ─── flush() ───

    public function testFlush()
    {
        $this->settings->set('k1', 'v1');
        $this->settings->set('k2', 'v2');
        $this->settings->set('k3', 'v3');

        $this->settings->flush();

        $this->assertNull($this->settings->get('k1'));
        $this->assertNull($this->settings->get('k2'));
        $this->assertNull($this->settings->get('k3'));
        $this->assertEmpty($this->settings->settings);
    }

    public function testFlushOnEmptyTableDoesNotFail()
    {
        $this->settings->flush();
        $this->assertEmpty($this->settings->settings);
    }

    // ─── refresh() ───

    public function testRefreshReloadsFromDatabase()
    {
        $this->settings->set('key', 'original');

        // Populate the internal _settings cache
        $this->assertSame('original', $this->settings->get('key'));

        // Directly update the database bypassing the component
        \Yii::$app->db->createCommand()->update('{{%setting}}', [
            'value' => 'changed_externally',
        ], ['key' => 'key'])->execute();

        // Still using internal cache (_settings array)
        $this->assertSame('original', $this->settings->get('key'));

        // After refresh, _settings is cleared and data is re-fetched from DB
        $this->settings->refresh();
        $this->assertSame('changed_externally', $this->settings->get('key'));
    }

    // ─── getSettings() ───

    public function testGetSettingsReturnsAllSettings()
    {
        $this->settings->set('a', 1);
        $this->settings->set('b', 'two');
        $this->settings->set('c', true);

        $all = $this->settings->settings;
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);
        $this->assertArrayHasKey('c', $all);
        $this->assertSame(1, $all['a']);
        $this->assertSame('two', $all['b']);
        $this->assertSame(true, $all['c']);
    }

    public function testGetSettingsReturnsEmptyArrayWhenNoSettings()
    {
        $this->assertSame([], $this->settings->settings);
    }

    // ─── Magic __get / __set ───

    public function testMagicGetterReturnsSetting()
    {
        $this->settings->set('site_name', 'My Site');
        $this->assertSame('My Site', $this->settings->site_name);
    }

    public function testMagicSetterUpdatesExistingSetting()
    {
        $this->settings->set('site_name', 'Old Name');
        $this->settings->site_name = 'New Name';
        $this->assertSame('New Name', $this->settings->get('site_name'));
    }

    public function testMagicSetterCreatesNewSetting()
    {
        $this->settings->brand_new_key = 'fresh value';
        $this->assertSame('fresh value', $this->settings->get('brand_new_key'));
    }

    public function testMagicSetterThrowsOnReadOnlyProperty()
    {
        $this->expectException(\yii\base\InvalidCallException::class);
        $this->settings->settings = ['should', 'throw'];
    }

    // ─── canGetProperty / canSetProperty ───

    public function testCanGetPropertyForExistingSetting()
    {
        $this->settings->set('existing', 'value');
        $this->assertTrue($this->settings->canGetProperty('existing'));
    }

    public function testCanGetPropertyForNonExistingSetting()
    {
        $this->assertFalse($this->settings->canGetProperty('nonexistent'));
    }

    public function testCanGetPropertyForComponentProperty()
    {
        $this->assertTrue($this->settings->canGetProperty('settings'));
    }

    public function testCanSetPropertyForComponentProperty()
    {
        $this->assertTrue($this->settings->canSetProperty('table'));
    }

    // ─── Type detection ───

    public function testDetectTypeViaReflection()
    {
        // We test type detection indirectly through set/get
        $this->settings->set('bool', true);
        $this->settings->set('int', 42);
        $this->settings->set('float', 1.5);
        $this->settings->set('string', 'text');
        $this->settings->set('array', [1, 2]);

        // Verify types are stored and decoded correctly
        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'bool'])->one();
        $this->assertSame('boolean', $row['type']);

        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'int'])->one();
        $this->assertSame('integer', $row['type']);

        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'float'])->one();
        $this->assertSame('float', $row['type']);

        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'string'])->one();
        $this->assertSame('string', $row['type']);

        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'array'])->one();
        $this->assertSame('array', $row['type']);
    }

    // ─── encode / decode value ───

    public function testBooleanFalseEncodeDecode()
    {
        $this->settings->set('flag', false);
        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'flag'])->one();
        $this->assertEquals('0', $row['value']);
        $this->assertSame('boolean', $row['type']);
    }

    public function testBooleanTrueEncodeDecode()
    {
        $this->settings->set('flag', true);
        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'flag'])->one();
        $this->assertEquals('1', $row['value']);
        $this->assertSame('boolean', $row['type']);
    }

    public function testArrayEncodeDecode()
    {
        $data = ['nested' => ['key' => 'val'], 'list' => [1, 2, 3]];
        $this->settings->set('data', $data);
        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'data'])->one();
        $this->assertSame($data, json_decode($row['value'], true));
    }

    // ─── Timestamps ───

    public function testCreatedAtAndUpdatedAtAreSet()
    {
        $this->settings->set('timed', 'val');
        $row = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'timed'])->one();
        $this->assertNotNull($row['created_at']);
        $this->assertNotNull($row['updated_at']);
    }

    public function testCreatedAtIsPreservedOnUpdate()
    {
        $this->settings->set('key', 'first');
        $row1 = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'key'])->one();
        $createdAt = $row1['created_at'];

        $this->settings->set('key', 'second');
        $row2 = (new \yii\db\Query())->from('{{%setting}}')->where(['key' => 'key'])->one();

        $this->assertSame($createdAt, $row2['created_at']);
    }

    // ─── Cache integration ───

    public function testCacheIsUsed()
    {
        $this->settings->set('cached', 'value');

        // Access to populate internal settings cache
        $this->assertSame('value', $this->settings->get('cached'));

        // Direct DB change should not affect cached result
        \Yii::$app->db->createCommand()->update('{{%setting}}', [
            'value' => 'changed',
        ], ['key' => 'cached'])->execute();

        // Still returns old value from the internal _settings cache
        $this->assertSame('value', $this->settings->get('cached'));
    }

    public function testRefreshClearsCache()
    {
        $this->settings->set('cached', 'value');
        $this->settings->settings; // trigger cache fill

        $this->settings->refresh();

        $cached = \Yii::$app->cache->get($this->settings->cacheKey);
        $this->assertFalse($cached);
    }

    // ─── Without cache ───

    public function testWorksWithoutCache()
    {
        $this->destroyApplication();
        $this->mockApplication([
            'components' => [
                'settings' => [
                    'class' => 'Smartass\Yii2Settings\Settings',
                    'cache' => null,
                ],
            ],
        ]);

        // Recreate table for new app instance
        \Yii::$app->db->createCommand(
            'CREATE TABLE {{%setting}} (
                "key" VARCHAR(255) NOT NULL PRIMARY KEY,
                "type" VARCHAR(255),
                "value" TEXT,
                "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
                "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        )->execute();

        $settings = \Yii::$app->settings;
        $settings->set('no_cache', 'works');
        $this->assertSame('works', $settings->get('no_cache'));

        // cleanup
        \Yii::$app->db->createCommand()->dropTable('{{%setting}}')->execute();
    }

    public function testWorksWithCustomDbConnection()
    {
        $this->destroyApplication();
        $this->mockApplication([
            'components' => [
                'db2' => [
                    'class' => \yii\db\Connection::class,
                    'dsn' => 'sqlite::memory:',
                ],
                'settings' => [
                    'class' => 'Smartass\Yii2Settings\Settings',
                    'db' => 'db2',
                    'cache' => null,
                ],
            ],
        ]);

        // Create table on db2 only (NOT on default db)
        \Yii::$app->db2->createCommand(
            'CREATE TABLE {{%setting}} (
                "key" VARCHAR(255) NOT NULL PRIMARY KEY,
                "type" VARCHAR(255),
                "value" TEXT,
                "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
                "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        )->execute();

        $settings = \Yii::$app->settings;
        $settings->set('custom_db', 'works');
        $this->assertSame('works', $settings->get('custom_db'));
        $this->assertSame(42, $settings->get('missing', 42));

        // Verify data is in db2
        $row = (new \yii\db\Query())
            ->from('{{%setting}}')
            ->where(['key' => 'custom_db'])
            ->one(\Yii::$app->db2);
        $this->assertSame('works', $row['value']);

        // Cleanup
        \Yii::$app->db2->createCommand()->dropTable('{{%setting}}')->execute();
    }

    // ─── Invalid config ───

    public function testEmptyCacheKeyThrowsException()
    {
        $this->expectException(\yii\base\InvalidConfigException::class);

        $this->destroyApplication();
        $this->mockApplication([
            'components' => [
                'settings' => [
                    'class' => 'Smartass\Yii2Settings\Settings',
                    'cacheKey' => '',
                ],
            ],
        ]);

        // Trigger initialization
        \Yii::$app->settings;
    }

    // ─── processConfig ───

    public function testProcessConfigWithDefault()
    {
        $settings = \Yii::$app->settings;

        // Use reflection to test the protected method
        $method = new \ReflectionMethod($settings, 'processConfig');
        $method->setAccessible(true);

        $result = $method->invoke($settings, [
            'host' => '%db.host|localhost%',
        ]);

        // Since 'db.host' doesn't exist, should use default 'localhost'
        $this->assertSame('localhost', $result['host']);
    }

    public function testProcessConfigWithExistingSetting()
    {
        $this->settings->set('app.name', 'MyApp');

        $method = new \ReflectionMethod($this->settings, 'processConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->settings, [
            'name' => '%app.name%',
        ]);

        $this->assertSame('MyApp', $result['name']);
    }

    public function testProcessConfigIgnoresNonPatternStrings()
    {
        $method = new \ReflectionMethod($this->settings, 'processConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->settings, [
            'plain' => 'just a string',
            'number' => 42,
        ]);

        $this->assertSame('just a string', $result['plain']);
        $this->assertSame(42, $result['number']);
    }

    public function testProcessConfigHandlesNestedArrays()
    {
        $this->settings->set('db.name', 'mydb');

        $method = new \ReflectionMethod($this->settings, 'processConfig');
        $method->setAccessible(true);

        $result = $method->invoke($this->settings, [
            'db' => [
                'name' => '%db.name%',
                'host' => '%db.host|127.0.0.1%',
            ],
        ]);

        $this->assertSame('mydb', $result['db']['name']);
        $this->assertSame('127.0.0.1', $result['db']['host']);
    }

    public function testProcessConfigReturnsNonArrayAsIs()
    {
        $method = new \ReflectionMethod($this->settings, 'processConfig');
        $method->setAccessible(true);

        $this->assertSame('string', $method->invoke($this->settings, 'string'));
        $this->assertNull($method->invoke($this->settings, null));
    }

    public function testProcessConfigSkipsInitializedComponents()
    {
        $this->destroyApplication();
        $this->mockApplication([
            'components' => [
                'settings' => [
                    'class' => 'Smartass\Yii2Settings\Settings',
                    'cache' => null,
                    'processConfig' => true,
                ],
            ],
        ]);

        // Recreate table
        \Yii::$app->db->createCommand(
            'CREATE TABLE {{%setting}} (
                "key" VARCHAR(255) NOT NULL PRIMARY KEY,
                "type" VARCHAR(255),
                "value" TEXT,
                "created_at" DATETIME DEFAULT CURRENT_TIMESTAMP,
                "updated_at" DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        )->execute();

        // Force-initialize db and settings (these are now "loaded" instances)
        $db = \Yii::$app->db;
        $settings = \Yii::$app->settings;

        // Call bootstrap() to register the EVENT_BEFORE_ACTION handler
        $settings->bootstrap(\Yii::$app);

        $this->assertTrue(\Yii::$app->has('db', true));

        // Remember the db instance identity before the event
        $dbBefore = \Yii::$app->db;

        // Trigger EVENT_BEFORE_ACTION — the processConfig handler runs here
        \Yii::$app->trigger(\yii\base\Application::EVENT_BEFORE_ACTION);

        // db was already instantiated, so it must NOT have been reset
        $this->assertSame($dbBefore, \Yii::$app->db);

        // Cleanup
        \Yii::$app->db->createCommand()->dropTable('{{%setting}}')->execute();
    }

    // ─── Multiple operations ───

    public function testSetMultipleAndFlush()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->settings->set("key_$i", "value_$i");
        }

        $all = $this->settings->settings;
        $this->assertCount(10, $all);

        $this->settings->flush();
        $this->assertEmpty($this->settings->settings);
    }

    // ─── Special characters in keys ───

    public function testDotNotationKeysWorkCorrectly()
    {
        $this->settings->set('app.mail.smtp-host', 'mail.example.com');

        // After removing ArrayHelper::getValue, dots in keys are treated as literal characters
        $this->assertSame('mail.example.com', $this->settings->get('app.mail.smtp-host'));
        $this->assertSame('mail.example.com', $this->settings->{'app.mail.smtp-host'});
    }

    // ─── Explicit type override ───

    public function testSetWithInvalidTypeUsesAutoDetection()
    {
        $this->settings->set('key', 42, 'invalid_type');
        $this->assertSame(42, $this->settings->get('key'));
    }

    public function testSetWithAllValidTypes()
    {
        $this->settings->set('k1', '42', Settings::TYPE_INTEGER);
        $this->assertSame(42, $this->settings->get('k1'));

        $this->settings->set('k2', '3.14', Settings::TYPE_FLOAT);
        $this->assertSame(3.14, $this->settings->get('k2'));

        $this->settings->set('k3', 1, Settings::TYPE_BOOLEAN);
        $this->assertSame(true, $this->settings->get('k3'));

        $this->settings->set('k4', 42, Settings::TYPE_STRING);
        $this->assertSame('42', $this->settings->get('k4'));

        $this->settings->set('k5', ['a' => 1], Settings::TYPE_ARRAY);
        $this->assertSame(['a' => 1], $this->settings->get('k5'));
    }
}
