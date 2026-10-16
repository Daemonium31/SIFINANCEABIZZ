<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Pengaturan aplikasi berbentuk kunci-nilai, mis. nama perusahaan. */
class Pengaturan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'kunci'      => ['type' => 'VARCHAR', 'constraint' => '50'],
            'nilai'      => ['type' => 'TEXT', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('kunci', true);
        $this->forge->createTable('pengaturan', true);
    }

    public function down()
    {
        $this->forge->dropTable('pengaturan', true);
    }
}
