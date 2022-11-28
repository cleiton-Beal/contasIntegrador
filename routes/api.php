<?php

use App\Http\Controllers\ContasController;
use App\Http\Controllers\TransacaoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return Auth::user();
});

Route::middleware('auth:sanctum')->group(function() {

    Route::Post('/CriarConta',[ContasController::class, 'CriarConta']);
    Route::Post('/BuscarContas',[ContasController::class, 'BuscarContasPorUser']);
    Route::post('/CadastrarP2P', [TransacaoController::class, 'CadastrarP2P']);
    Route::get('/BuscarExtrato/{account}', [TransacaoController::class, 'BuscarExtrato']);
    Route::post('/GerarBoleto', [TransacaoController::class, 'GerarBoleto']);
    Route::post('/pagarBoleto', [TransacaoController::class, 'CadastrarPagamentoBoleto' ]);
    Route::post('/BuscarP2P', [ContasController::class, 'BuscarContaP2P']);
    Route::post('/BuscarBoleto', [TransacaoController::class, 'BuscarBoleto']);
});
