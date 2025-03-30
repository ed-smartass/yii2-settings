<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%setting}}`.
 */
class m210828_183945_create_setting_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%setting}}', [
            'key' => $this->string(),
            'type' => $this->string(),
            'value' => $this->text(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')
        ]);

        $this->addPrimaryKey(
            'pk-setting',
            '{{%setting}}',
            'key'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%setting}}');
    }
}
