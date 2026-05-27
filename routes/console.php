<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('grid:hello', function () {
    $this->info('Grid Dashboard ok!');
});
