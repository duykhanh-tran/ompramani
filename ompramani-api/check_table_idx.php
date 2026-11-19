<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$idxs = DB::select("SELECT tablename, indexname, indexdef FROM pg_indexes WHERE tablename IN ('orders', 'order_refunds', 'payments')");
foreach ($idxs as $i) {
    echo "{$i->tablename}: {$i->indexname} => {$i->indexdef}\n";
}
