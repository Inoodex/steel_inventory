<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shop = \App\Models\Warehouse::where('type', 'shop')->first();
echo "Shop: " . ($shop ? $shop->id . ' - ' . $shop->name : 'None') . PHP_EOL;

echo "All warehouses:" . PHP_EOL;
foreach (\App\Models\Warehouse::all() as $w) {
    echo "  {$w->id}: {$w->name} (type: {$w->type})\n";
}

$coils = \App\Models\Coil::with(['lot', 'warehouse'])->get();
echo "Total coils: " . $coils->count() . PHP_EOL;
foreach ($coils as $c) {
    echo "Coil #{$c->id}: WH={$c->warehouse_id} (" . ($c->warehouse->name ?? 'N/A') . "), Lot={$c->lot_id} (" . ($c->lot->lot_number ?? 'Opening') . "), Rem={$c->remaining_weight}, Status={$c->status}" . PHP_EOL;
}

echo "Purchases for Lot 14:" . PHP_EOL;
foreach (\App\Models\Purchase::where('lot_id', 14)->get() as $p) {
    echo "  Purchase #{$p->id}: WH={$p->warehouse_id}, Qty={$p->quantity}, Status={$p->status}\n";
}
