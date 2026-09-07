<?php

use App\Http\Controllers\Autenticacao\SessaoController;
use App\Http\Controllers\Sistema\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/status', StatusController::class);
Route::middleware('auth:sanctum')->get('/sessao', SessaoController::class);
