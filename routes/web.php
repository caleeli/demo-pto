<?php

use ProcessMaker\Package\DemoPto\Http\Controllers\DemoPtoController;

Route::group(['middleware' => ['auth']], function () {
    Route::get('admin/demo-pto', [DemoPtoController::class, 'index'])->name('package.skeleton.index');
    Route::get('demo-pto', [DemoPtoController::class, 'index'])->name('package.skeleton.tab.index');
});
