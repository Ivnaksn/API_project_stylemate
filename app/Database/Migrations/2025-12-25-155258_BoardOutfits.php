<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BoardOutfits extends Migration
{
    public function up()
{
    $this->forge->addField([
        'id'=>['type'=>'INT','auto_increment'=>true],
        'board_id'=>['type'=>'INT'],
        'outfit_id'=>['type'=>'INT'],
    ]);
    $this->forge->addKey('id',true);
    $this->forge->createTable('board_outfits');
}
public function down()
{
    $this->forge->dropTable('board_outfits');
}
}
