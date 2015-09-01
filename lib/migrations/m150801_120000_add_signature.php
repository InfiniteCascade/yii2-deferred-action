<?php
namespace canis\deferred\migrations;

/**
 * m141202_120000_initial_deferred [[@doctodo class_description:canis\deferred\migrations\m141202_120000_initial_deferred]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class m150801_120000_add_signature extends \canis\db\Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->db->createCommand()->checkIntegrity(false)->execute();
        $this->addColumn('deferred_action', 'action_signature', 'string NOT NULL AFTER session_id');
        $this->db->createCommand()->checkIntegrity(true)->execute();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->db->createCommand()->checkIntegrity(false)->execute();

        $this->dropExistingTable('deferred_action');

        $this->db->createCommand()->checkIntegrity(true)->execute();

        return true;
    }
}
