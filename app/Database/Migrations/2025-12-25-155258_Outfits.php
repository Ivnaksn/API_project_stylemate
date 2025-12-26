<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Outfits extends Migration
{
    public function up()
{
    $this->forge->addField([
        'id'=>['type'=>'INT','auto_increment'=>true],
        'title'=>['type'=>'VARCHAR','constraint'=>100],
        'image_url'=>['type'=>'TEXT'],
        'created_at'=>['type'=>'DATETIME','null'=>true],
    ]);
    $this->forge->addKey('id',true);
    $this->forge->createTable('outfits');
}
public function down()
{
    $this->forge->dropTable('outfits');
}

}
