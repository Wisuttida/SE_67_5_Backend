<?php

namespace App\Http\Controllers;

use App\Models\ingredients;

use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = ingredients::all();
        return response()->json(['ingredients' => $ingredients]);
    }

}
