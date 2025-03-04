<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::get('/tambon', function () {
    $path = url('/raw_database.json');
    $data = json_decode(file_get_contents($path), false);
    $provinces = array_map(function ($item) {
        return $item->province;
    }, $data);
    $provinces = array_unique($provinces);
    $provinces = array_values($provinces);

    $amphoes = [];
    $tambons = [];
    return view('tambon/index', compact('provinces', 'amphoes', 'tambons'));
});
