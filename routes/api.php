<?php

use App\Http\Controllers\Api\AttendanceReceiveController;
use App\Http\Controllers\Api\EmployeeApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('attendance.apikey')->get('/employees', [EmployeeApiController::class, 'index']);
Route::middleware('attendance.apikey')->get('/employees/{pin}/profile', [EmployeeApiController::class, 'profile']);
Route::middleware('attendance.apikey')->post('/attendance/receive', [AttendanceReceiveController::class, 'receive']);
