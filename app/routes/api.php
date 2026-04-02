<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\AdminUsersController;


Route::get('/', function () {
    return view('welcome');
});
Route::get('/health', function () {
    return 'OK';
});


Route::get('posts/randomPosts', [PostController::class, 'random']);         // Visualizza tutti i post


Route::get('posts', [PostController::class, 'index']);         // Visualizza tutti i post
Route::post('posts', [PostController::class, 'store']);        // Crea un nuovo post
Route::get('posts/{id}', [PostController::class, 'show']);     // Visualizza un post singolo
Route::put('posts/{id}', [PostController::class, 'update']);   // Aggiorna un post esistente
Route::delete('posts/{id}', [PostController::class, 'destroy']); // Elimina un post


Route::apiResource('customers', CustomerController::class);

//orders
Route::apiResource('orders', OrderController::class);

Route::post('auth/login', [AuthController::class, 'login']);
Route::post('login', [AuthController::class, 'login']); // legacy alias


use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('logout', [AuthController::class, 'logout']); // legacy alias
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('admin-users', AdminUsersController::class);

    Route::get('/me', function (Request $request) {
        $u = $request->user();

        return response()->json([
            'user' => [
                'id' => (string) $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ],
            'roles' => $u->roles ?? [],
            'permissions' => $u->permissions ?? [],
        ]);
    });
});
