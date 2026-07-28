<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeApiController;

Route::middleware('attendance.apikey')->get('/employees', [EmployeeApiController::class, 'index']);
