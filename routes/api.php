<?php

use Vdisain\Plugins\Interfaces\Support\Route\Route;

Route::get('vdisain-interfaces/mtac/product/import', [\Seeru\Mtac\Controllers\ProductController::class, 'update']);
// Route::get('vdisain-interfaces/mtac/product/(?P<id>[a-zA-Z0-9-]+)', [\Seeru\Mtac\Controllers\ProductController::class, 'show']);
