<?php

namespace App\Observers;

use App\Models\Evaluation;

class EvaluationObserver
{
    public function saved(Evaluation $evaluation)
    {
        $produit = $evaluation->produit;
        if ($produit) {
            $produit->note_moyenne = $produit->evaluations()->approuve()->avg('note') ?? 0;
            $produit->nombre_evaluations = $produit->evaluations()->approuve()->count();
            $produit->save();
        }
    }
}
