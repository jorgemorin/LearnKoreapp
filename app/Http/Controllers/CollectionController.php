<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * GET /collection — Colección personal del usuario autenticado.
     * El componente Livewire MyCollection gestiona todos los datos.
     */
    public function index(Request $request)
    {
        return view('vocabulary.collection');
    }
}
