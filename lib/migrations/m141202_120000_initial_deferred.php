<?php
namespace infinite\deferred\migrations;

class m141202_120000_initial_deferred extends \infinite\db\Migration
{
    public function up()
    {
        $this->db->createCommand()->checkIntegrity(false)->execute();
        // data_interface
        $this->dropExistingTable('deferred_action');
        $this->createTable('deferred_action', [
            'id' => 'bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL',
            'session_id' => 'string(40) NOT NULL',
            'priority' => 'int(3) unsigned NOT NULL DEFAULT 1',
            'action' => 'longblob NOT NULL',
            'peak_memory' => 'integer unsigned DEFAULT NULL',
            'status' => 'enum(\'queued\', \'starting\', \'running\', \'error\', \'ready\') DEFAULT \'queued\'',
            'started' => 'datetime DEFAULT NULL',
            'ended' => 'datetime DEFAULT NULL',
            'expires' => 'datetime DEFAULT NULL',
            'created' => 'datetime DEFAULT NULL',
            'modified' => 'datetime DEFAULT NULL'
        ]);
        // $this->addPrimaryKey('dataInterfacePk', 'data_interface', 'id');
        $this->addForeignKey('deferredActionUser', 'deferred_action', 'user_id', 'user', 'id', 'CASCADE', 'CASCADE');
        $this->db->createCommand()->checkIntegrity(true)->execute();

        return true;
    }

    public function down()
    {
        $this->db->createCommand()->checkIntegrity(false)->execute();

        $this->dropExistingTable('deferred_action');

        $this->db->createCommand()->checkIntegrity(true)->execute();

        return true;
    }
}
