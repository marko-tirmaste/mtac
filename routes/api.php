<?php

use Seeru\Mtac\Controllers\ProductController;
use Vdisain\Plugins\Interfaces\Support\Route\Route;

Route::get('vdisain-interfaces/mtac/cron', [\Seeru\Mtac\Controllers\CronController::class, 'update']);

Route::get('vdisain-interfaces/mtac/migrate', [\Seeru\Mtac\Controllers\MigrationController::class, 'migrate']);

Route::get('vdisain-interfaces/mtac/product', [ProductController::class, 'index']);
Route::get('vdisain-interfaces/mtac/product/import', [ProductController::class, 'update']);
Route::get('vdisain-interfaces/mtac/product/sync', [ProductController::class, 'update']);
Route::get('vdisain-interfaces/mtac/product/compare', [\Seeru\Mtac\Controllers\ProductCompareController::class, 'index']);
Route::get('vdisain-interfaces/mtac/product/(?P<id>[a-zA-Z0-9-]+)', [ProductController::class, 'show']);
Route::get('vdisain-interfaces/mtac/products/(?P<id>[a-zA-Z0-9-]+)', [ProductController::class, 'show']);
Route::get('vdisain-interfaces/mtac/product/(?P<id>[a-zA-Z0-9-]+)/import', [ProductController::class, 'store']);
Route::get('vdisain-interfaces/mtac/product/(?P<id>[0-9]+)/update', [ProductController::class, 'updateProduct']);
