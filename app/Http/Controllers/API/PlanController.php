<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Affiche la liste de tous les plans.
     */
    public function index()
    {
        $plans = Plan::all();
        return response()->json(['success' => true, 'data' => $plans]);
    }

    /**
     * Enregistre un nouveau plan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:plans',
            'prix' => 'required|numeric|min:0',
            'devise' => 'required|string|max:10',
            'duree_en_jours' => 'required|integer|min:1',
            'fonctionnalites' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation', 'errors' => $validator->errors()], 422);
        }

        $plan = Plan::create($request->all());

        return response()->json(['success' => true, 'message' => 'Plan créé avec succès', 'data' => $plan], 201);
    }

    /**
     * Affiche un plan spécifique.
     */
    public function show(Plan $plan)
    {
        return response()->json(['success' => true, 'data' => $plan]);
    }

    /**
     * Met à jour un plan existant.
     */
    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'string|max:255|unique:plans,nom,' . $plan->id,
            'prix' => 'numeric|min:0',
            'devise' => 'string|max:10',
            'duree_en_jours' => 'integer|min:1',
            'fonctionnalites' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Erreur de validation', 'errors' => $validator->errors()], 422);
        }

        $plan->update($request->all());

        return response()->json(['success' => true, 'message' => 'Plan mis à jour avec succès', 'data' => $plan]);
    }

    /**
     * Supprime un plan.
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json(['success' => true, 'message' => 'Plan supprimé avec succès']);
    }
}