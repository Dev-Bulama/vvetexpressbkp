<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('VetExpress is deployed. Application routes are not yet configured.', 200);
});
