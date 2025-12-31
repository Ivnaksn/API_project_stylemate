<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAIAnalysesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'analysis_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'dominant_color' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'detected_style' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'weather_suitable' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'material' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'recommended_match' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('analysis_id', true);
        $this->forge->addForeignKey('item_id', 'wardrobe_items', 'item_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ai_analyses');
    }

    public function down()
    {
        $this->forge->dropTable('ai_analyses');
    }
}