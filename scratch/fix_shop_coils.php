<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shop = \App\Models\Warehouse::where('type', 'shop')->first();
if ($shop) {
    \App\Models\Purchase::where('lot_id', 14)->whereNull('warehouse_id')->update(['warehouse_id' => $shop->id]);
    \App\Models\Coil::where('lot_id', 14)->whereNull('warehouse_id')->update(['warehouse_id' => $shop->id]);
    echo "Updated Lot 14 purchases & coils to warehouse ID: {$shop->id} ({$shop->name})\n";
} else {
    echo "No shop warehouse found.\n";
}
