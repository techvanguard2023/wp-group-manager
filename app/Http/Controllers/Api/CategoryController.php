<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Lista todas as categorias cadastradas.
     */
    public function index()
    {
        return Category::orderBy('id')->get();
    }
}
