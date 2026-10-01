<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application guards cover non-MySQL environments; production MySQL gets
     * the database-level protection as well.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->replaceForeignKeys('restrictOnDelete');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->replaceForeignKeys('cascadeOnDelete');
    }

    private function replaceForeignKeys(string $deleteAction): void
    {
        $definitions = [
            'material_usages' => [
                ['house_id', 'houses'],
                ['material_id', 'materials'],
                ['user_id', 'users'],
            ],
            'tool_usages' => [
                ['house_id', 'houses'],
                ['tool_id', 'tools'],
                ['user_id', 'users'],
            ],
            'tool_return_logs' => [
                ['tool_id', 'tools'],
                ['house_id', 'houses'],
                ['tool_usage_id', 'tool_usages'],
                ['reported_by', 'users'],
            ],
            'material_tool_requests' => [
                ['requester_id', 'users'],
                ['house_id', 'houses'],
                ['material_id', 'materials'],
                ['tool_id', 'tools'],
            ],
        ];

        foreach ($definitions as $tableName => $foreignKeys) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKeys) {
                foreach ($foreignKeys as [$column]) {
                    $table->dropForeign([$column]);
                }
            });

            Schema::table($tableName, function (Blueprint $table) use ($foreignKeys, $deleteAction) {
                foreach ($foreignKeys as [$column, $referencedTable]) {
                    $foreign = $table->foreign($column)->references('id')->on($referencedTable);
                    $foreign->{$deleteAction}();
                }
            });
        }
    }
};
