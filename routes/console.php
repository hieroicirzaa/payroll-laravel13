<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('payroll:hello', function () {
    $this->info('Payroll system ready.');
});
