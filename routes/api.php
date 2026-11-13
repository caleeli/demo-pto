<?php

use ProcessMaker\Package\DemoPto\Http\Controllers\DemoPtoController;

Route::group(['middleware' => ['auth:api', 'bindings']], function () {
    Route::get('admin/demo-pto/fetch', [DemoPtoController::class, 'fetch'])
        ->name('package.skeleton.fetch');
    Route::apiResource('admin/demo-pto', DemoPtoController::class);
});
