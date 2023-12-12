<?php

use Vdisain\Plugins\Interfaces\Support\Route\Route;

Route::get('vdisain-interfaces/mtac/product/import', [\Seeru\Mtac\Controllers\ProductController::class, 'update']);
Route::get('vdisain-interfaces/mtac/product/compare', [\Seeru\Mtac\Controllers\ProductCompareController::class, 'index']);
Route::get('vdisain-interfaces/mtac/product/(?P<id>[a-zA-Z0-9-]+)/import', [\Seeru\Mtac\Controllers\ProductController::class, 'store']);
