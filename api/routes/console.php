<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('api:test', function () {
    $this->comment("This is a test command.");
    // use/test features of your application here
})->purpose('Run a test command');
