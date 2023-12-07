<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('target-macros', [UserController::class, 'updateTargetMacros']);

    Route::get('/meals', [MealController::class, 'index']);
    Route::post('/meals', [MealController::class, 'store']);
    Route::put('/meals/{id}', [MealController::class, 'update']);
    Route::delete('/meals/{id}', [MealController::class, 'destroy']);

    Route::get('/today-list', [TodayController::class, 'index']);
    Route::post('/today-list/{meal_id}', [TodayController::class, 'store']);
    Route::put('/today-list/{id}', [TodayController::class, 'update']);
    Route::delete('/today-list/{id}', [TodayController::class, 'destroy']);
    // Drop entire TodayList for authenticated user
    Route::get('/today-list/drop', [TodayController::class, 'drop']);
});
