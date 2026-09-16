<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre>";
Artisan::call('config:clear');  echo Artisan::output();
Artisan::call('cache:clear');   echo Artisan::output();
Artisan::call('view:clear');    echo Artisan::output();
Artisan::call('route:clear');   echo Artisan::output();
echo "\n✅ Cache geleert!";