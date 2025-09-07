<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class MessagerieController extends Controller
{
    // Client : envoyer un message à un vendeur
    public function envoyer(Request $request)
    {
        $request->validate([
            'destinataire_id' => 'required|exists:users,id',
            'sujet' => 'nullable|string|max:255',
            'contenu' => 'required|string',
            'commande_id' => 'nullable|exists:commandes,id',
            'produit_id' => 'nullable|exists:produits,id',
        ]);

        // Vérifier que le destinataire est un vendeur
        $destinataire = \App\Models\User::find($request->destinataire_id);
        if (!$destinataire || !$destinataire->isVendor()) {
            return response()->json(['message' => 'Le destinataire doit être un vendeur'], 403);
        }

        $message = Message::create([
            'expediteur_id' => Auth::id(),
            'destinataire_id' => $request->destinataire_id,
            'sujet' => $request->sujet,
            'contenu' => $request->contenu,
            'commande_id' => $request->commande_id,
            'produit_id' => $request->produit_id,
        ]);

        // TODO: Notifier le destinataire (notification Laravel)
        // $destinataire->notify(new NouveauMessage($message));

        return response()->json(['message' => 'Message envoyé', 'data' => $message], 201);
    }

    // Lister messages reçus (pagination)
    public function recus(Request $request)
    {
        $user = Auth::user();
        $messages = Message::where('destinataire_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($messages);
    }

    // Lister messages envoyés (pagination)
    public function envoyes(Request $request)
    {
        $user = Auth::user();
        $messages = Message::where('expediteur_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return response()->json($messages);
    }

    // Récupérer les conversations (groupées par interlocuteur)
    public function conversations()
    {
        $user = Auth::user();
        $conversations = Message::where(function($query) use ($user) {
                $query->where('expediteur_id', $user->id)
                      ->orWhere('destinataire_id', $user->id);
            })
            ->with(['expediteur', 'destinataire'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($message) use ($user) {
                return $message->expediteur_id == $user->id
                    ? $message->destinataire_id
                    : $message->expediteur_id;
            });
        return response()->json($conversations);
    }

    // Marquer un message comme lu (sécurisé)
    public function marquerCommeLu($id)
    {
        $message = Message::where('destinataire_id', Auth::id())->findOrFail($id);
        $message->lu_at = now();
        $message->save();
        return response()->json(['message' => 'Message marqué comme lu']);
    }

    // Marquer toute une conversation comme lue
    public function marquerConversationCommeLue($userId)
    {
        $user = Auth::user();
        Message::where('expediteur_id', $userId)
            ->where('destinataire_id', $user->id)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);
        return response()->json(['message' => 'Conversation marquée comme lue']);
    }

    // Répondre à un message (sécurisé)
    public function repondre(Request $request, $id)
{
    $request->validate([
        'contenu' => 'required|string',
    ]);

    $user = $request->user();

    // Cherche le message (sans filtrer d'emblée sur destinataire pour distinguer 404 vs 403)
    $messageOriginal = Message::find($id);

    if (! $messageOriginal) {
        return response()->json(['message' => 'Message introuvable.'], 404);
    }

    // Sécurité : vérifier que l'utilisateur authentifié est bien le destinataire
    if ($messageOriginal->destinataire_id !== $user->id) {
        return response()->json(['message' => 'Vous n\'êtes pas le destinataire de ce message.'], 403);
    }

    $reponse = Message::create([
        'expediteur_id' => $user->id,
        'destinataire_id' => $messageOriginal->expediteur_id,
        'sujet' => 'RE: ' . ($messageOriginal->sujet ?? ''),
        'contenu' => $request->contenu,
        'commande_id' => $messageOriginal->commande_id,
        'produit_id' => $messageOriginal->produit_id,
    ]);

    // Optionnel : notifier l'expéditeur original ici

    return response()->json(['message' => 'Réponse envoyée', 'data' => $reponse], 201);
}

}
