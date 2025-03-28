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
        
        // Create test table
        \Yii::$app->db->createCommand()->createTable('{{%setting}}', [
            'id' => 'pk',
            'key' => 'string(128) NOT NULL',
            'value' => 'text NOT NULL',
            'type' => 'string(32) NOT NULL',
            'created_at' => 'integer',
            'updated_at' => 'integer',
        ])->execute();
        
        \Yii::$app->db->createCommand()->createIndex('idx-setting-key', '{{%setting}}', 'key', true)->execute();
        
        $this->settings = \Yii::$app->settings;
    }
    
    protected function tearDown(): void
    {
        \Yii::$app->db->createCommand()->dropTable('{{%setting}}')->execute();
        parent::tearDown();
    }
    
    public function testSet()
    {
        // Test string
        $this->settings->set('test_string', 'test value');
        $this->assertEquals('test value', $this->settings->get('test_string'));
        
        // Test integer
        $this->settings->set('test_integer', 123);
        $this->assertSame(123, $this->settings->get('test_integer'));
        $this->assertIsInt($this->settings->get('test_integer'));
        
        // Test float
        $this->settings->set('test_float', 123.45);
        $this->assertSame(123.45, $this->settings->get('test_float'));
        $this->assertIsFloat($this->settings->get('test_float'));
        
        // Test boolean
        $this->settings->set('test_boolean', true);
        $this->assertSame(true, $this->settings->get('test_boolean'));
        $this->assertIsBool($this->settings->get('test_boolean'));
        
        // Test array
        $array = ['key1' => 'value1', 'key2' => 'value2'];
        $this->settings->set('test_array', $array);
        $this->assertEquals($array, $this->settings->get('test_array'));
        $this->assertIsArray($this->settings->get('test_array'));
    }
    
    public function testGet()
    {
        // Test with default value
        $this->assertNull($this->settings->get('non_existent_key'));
        $this->assertEquals('default', $this->settings->get('non_existent_key', 'default'));
        
        // Test with saveDefault parameter
        $this->assertEquals('default_saved', $this->settings->get('save_default_key', 'default_saved', true));
        $this->assertEquals('default_saved', $this->settings->get('save_default_key'));
    }
    
    public function testDelete()
    {
        $this->settings->set('delete_test', 'value');
        $this->assertEquals('value', $this->settings->get('delete_test'));
        
        $this->settings->delete('delete_test');
        $this->assertNull($this->settings->get('delete_test'));
    }
    
    public function testFlush()
    {
        $this->settings->set('key1', 'value1');
        $this->settings->set('key2', 'value2');
        
        $this->assertEquals('value1', $this->settings->get('key1'));
        $this->assertEquals('value2', $this->settings->get('key2'));
        
        $this->settings->flush();
        
        $this->assertNull($this->settings->get('key1'));
        $this->assertNull($this->settings->get('key2'));
    }
    
    public function testMagicMethods()
    {
        $this->settings->set('magic_property', 'magic value');
        
        // Test magic getter
        $this->assertEquals('magic value', $this->settings->magic_property);
        
        // Test magic setter
        $this->settings->magic_property = 'new magic value';

        $this->assertEquals('new magic value', $this->settings->get('magic_property'));
    }
}
