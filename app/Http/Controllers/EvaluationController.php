<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\Produit;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    // Soumettre une évaluation (client)
    public function store(Request $request)
    {
        $data = $request->validate(\App\Models\Evaluation::rules());

        $user = $request->user();

        // 1) Empêcher les doublons
        $deja = \App\Models\Evaluation::where('user_id', $user->id)
            ->where('produit_id', $data['produit_id'])
            ->exists();

        if ($deja) {
            return response()->json(['message' => 'Vous avez déjà évalué ce produit.'], 422);
        }

        // 2) Vérifier l’achat (note l’usage de id_user + detailCommandes)
        $hasPurchased = \App\Models\Commande::where('id_user', $user->id)
            ->whereHas('detailCommandes', function ($q) use ($data) {
                $q->where('produit_id', $data['produit_id']);
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json(['message' => 'Vous devez avoir acheté ce produit avant de l\'évaluer.'], 403);
        }

        // 3) Créer l’évaluation (statut en attente)
        $evaluation = \App\Models\Evaluation::create([
            'produit_id'  => $data['produit_id'],
            'user_id'     => $user->id,
            'note'        => $data['note'],
            'commentaire' => $data['commentaire'] ?? null,
            'statut'      => 'en_attente',
        ]);

        return response()->json([
            'message'    => 'Évaluation soumise. Elle sera visible après modération.',
            'evaluation' => $evaluation->load(['produit','user']),
        ], 201);
    }


    // Récupérer les évaluations d'un produit (publiques)
    public function index($produitId)
    {
        $evaluations = Evaluation::where('produit_id', $produitId)
            ->where('statut', 'approuve')
            ->with('user:id,nom')
            ->latest()
            ->get();

        $moyenne = Evaluation::where('produit_id', $produitId)
            ->where('statut', 'approuve')
            ->avg('note');

        return response()->json([
            'evaluations' => $evaluations,
            'moyenne' => round($moyenne, 2),
        ]);
    }

    // Modération (admin)
    public function moderate(Request $request, $id)
    {
        // Vérifier que l'utilisateur est admin
        if (!Auth::user()->hasRole('Administrateur')) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        $request->validate([
            'statut' => 'required|in:approuve,rejete',
        ]);
        $evaluation = Evaluation::findOrFail($id);
        $evaluation->statut = $request->statut;
        $evaluation->save();
        return response()->json($evaluation);
    }

    // Evaluations en attente (admin)
    public function evaluationsEnAttente()
    {
        if (!Auth::user()->hasRole('Administrateur')) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        $evaluations = Evaluation::with(['produit', 'user'])
            ->enAttente()
            ->latest()
            ->paginate(20);
        return response()->json($evaluations);
    }
}
