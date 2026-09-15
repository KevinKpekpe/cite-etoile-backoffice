<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

test('mysql tables use InnoDB and the project Unicode encoding', function () {
    $connection = DB::connection('mysql');
    $connection->useDefaultSchemaGrammar();
    $blueprint = new Blueprint($connection, 'configuration_probe');
    $blueprint->create();
    $blueprint->id();

    $statements = $blueprint->toSql();

    expect($statements[0])->toContain(
        'engine = InnoDB',
        'default character set utf8mb4',
        "collate 'utf8mb4_unicode_ci'",
    );
});
