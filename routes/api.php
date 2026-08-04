<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceReceiveController;
use App\Http\Controllers\Api\EmployeeApiController;

Route::middleware('attendance.apikey')->get('/employees', [EmployeeApiController::class, 'index']);
Route::middleware('attendance.apikey')->post('/attendance/receive', [AttendanceReceiveController::class, 'receive']);
