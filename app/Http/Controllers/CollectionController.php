<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    /**
     * GET /collection — Colección personal del usuario autenticado.
     */
    public function index(Request $request)
    {
        $progress = UserProgress::with(['item.tags', 'item.entities'])
            ->forUser($request->user()->id)
            ->where('item_type', 'compound')
            ->orderBy('next_review_date')
            ->paginate(15);

        return view('vocabulary.collection', [
            'progress' => $progress,
            'total'    => UserProgress::forUser($request->user()->id)
                              ->where('item_type', 'compound')
                              ->count(),
        ]);
    }
}
