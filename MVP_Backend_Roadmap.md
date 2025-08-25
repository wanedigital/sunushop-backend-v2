# Feuille de Route MVP Backend - SunuShop

Ce document a pour but de fournir une vue d'ensemble de l'état actuel du backend de SunuShop et de définir les prochaines étapes pour atteindre un Produit Minimum Viable (MVP) opérationnel, en se concentrant sur le modèle multi-boutiques et le système d'abonnement vendeur.

Catégorie : Sécurité & Autorisation (Priorité Haute)

1. Implémenter les Politiques d'Autorisation (Laravel Policies)
    * Explication : C'est la tâche la plus critique pour la sécurité de votre plateforme multi-vendeurs. Les Policies permettent de définir des règles d'autorisation fines (ex: "un vendeur
        ne peut modifier/supprimer que sa propre boutique ou ses propres produits").
    * Tâches Spécifiques :
        * `BoutiqueController` (`update`, `destroy`) : Assurez-vous que seul le propriétaire de la boutique (l'id_user associé à la boutique) ou un administrateur peut modifier ou
            supprimer une boutique.
        * `ProduitController` (`update`, `destroy`) : Assurez-vous que seul le vendeur propriétaire du produit peut le modifier ou le supprimer.
        * `CategorieController` (`store`, `show`, `update`, `destroy`) : Restreindre ces actions aux administrateurs uniquement.
        * `CommandeController` (`updateStatut`, `annuler`, `getCommandesByBoutique`) : Bien que des vérifications de rôle/propriété existent déjà, les Policies peuvent centraliser et
            formaliser ces règles, rendant le code plus propre et plus sûr.

2. Validation des Données Manquante ou Insuffisante
    * Explication : La validation est cruciale pour l'intégrité des données et la sécurité. Des données non validées peuvent entraîner des erreurs, des vulnilités (ex: injections SQL) ou
        des comportements inattendus.
    * Tâches Spécifiques :
        * `CategorieController` (`store`, `update`) : Ajouter des règles de validation pour le champ libelle (ex: required|string|max:255|unique:categories,libelle).

Catégorie : Robustesse & Logique Métier (Priorité Moyenne)

3. Affiner le Flux d'Approbation des Vendeurs et Boutiques
    * Explication : Le système d'activation de profil via e-mail est excellent, mais il ne gère pas encore complètement le statut de l'utilisateur et de la boutique.
    * Tâches Spécifiques :
        * `AuthController` (`register`) : Pour les nouveaux utilisateurs avec le profil 'Vendeur', définir leur status initial sur 'inactif' ou 'en attente' au lieu de 'actif'.
        * `BoutiqueController` (`store`) : Définir le status initial d'une nouvelle boutique sur 'en attente' ou 'inactif' par défaut.
        * `ProfilValidationController` (`valider`) : Après avoir mis à jour le profil_id de l'utilisateur vers 'Vendeur', cette méthode devrait également :
            * Mettre à jour le status de l'utilisateur à 'actif'.
            * Mettre à jour le status de la boutique associée à cet utilisateur à 'ouvret'.

4. Nettoyage et Optimisation du Code
    * Explication : Un code propre et optimisé est plus facile à maintenir et à faire évoluer.
    * Tâches Spécifiques :
        * `ProduitController` (`index`) : Supprimer le code en double et s'assurer que la pagination, la recherche et le chargement anticipé (with('categorie')) fonctionnent ensemble de
            manière cohérente.
        * `CommandeController` : Supprimer les méthodes ventesVendeurParPeriode et meilleursClientsVendeur qui sont des doublons de celles dans StatistiqueController.
        * `BoutiqueController` : Supprimer la méthode allboutique() qui est redondante avec index. Supprimer les blocs de code commentés inutilisés.
        * `User` Model : Ajouter le champ photo à la migration users_table si ce n'est pas déjà fait, pour correspondre au $fillable du modèle.
        * `ProduitBoutique` Model : Activer la relation boutique() si elle est toujours commentée.

5. Affiner l'Autorisation de la Méthode `CommandeController::show` pour les Invités
    * Explication : Pour les commandes d'invités, il est plus sûr de demander une vérification supplémentaire (ex: numéro de commande et email) pour éviter que n'importe qui puisse deviner
        un ID de commande.
    * Tâche : Modifier la méthode show pour les utilisateurs non connectés afin qu'elle exige également le numeroCommande et l'email_client pour la vérification, ou rediriger vers
        getCommandeInvite qui le fait déjà.

---

✨ Nouvelles Fonctionnalités pour le MVP (Backend)

Catégorie : Monétisation & Gestion Vendeur (Priorité Très Haute)

6. Système d'Abonnement Vendeur
    * Explication : Mise en place des plans d'abonnement pour les vendeurs.
    * Tâches Spécifiques :
        * Migrations :
            * Créer une table plans : id, name (ex: "Découverte", "Essentiel", "Premium"), price, currency, duration_in_days, features (JSON pour stocker les capacités du plan comme
                max_products, analytics_access), created_at, updated_at.
            * Créer une table subscriptions : id, user_id (FK vers users pour le vendeur), plan_id (FK vers plans), start_date, end_date, status (ex: 'active', 'inactive', 'cancelled',
                'trial'), payment_status (ex: 'paid', 'pending', 'failed'), created_at, updated_at.
        * Modèles : Créer les modèles Plan et Subscription avec leurs relations.
        * API Admin (`PlanController`) :
            * GET /api/admin/plans : Lister tous les plans.
            * POST /api/admin/plans : Créer un nouveau plan.
            * PUT /api/admin/plans/{id} : Mettre à jour un plan.
            * DELETE /api/admin/plans/{id} : Supprimer un plan.
        * API Admin (`SubscriptionController`) :
            * GET /api/admin/subscriptions : Lister tous les abonnements.
            * GET /api/admin/subscriptions/{id} : Voir un abonnement spécifique.
            * POST /api/admin/subscriptions/{user_id}/assign : Attribuer manuellement un plan à un vendeur.
            * PATCH /api/admin/subscriptions/{id}/status : Changer manuellement le statut d'un abonnement.
        * API Vendeur (`SubscriptionController`) :
            * GET /api/vendor/subscription/current : Voir les détails de l'abonnement actuel du vendeur.
            * GET /api/vendor/subscription/plans : Lister les plans disponibles (pour mise à niveau/rétrogradation).
            * POST /api/vendor/subscription/subscribe : Initier un abonnement à un plan (point d'intégration futur avec une passerelle de paiement).
            * POST /api/vendor/subscription/cancel : Annuler l'abonnement actuel.
        * Logique d'Intégration :
            * Inscription Vendeur : Lors de l'inscription d'un 'Vendeur', lui attribuer automatiquement le plan "Découverte" (gratuit).
            * Contrôle des Fonctionnalités : Dans ProduitController::store, vérifier le max_products du plan actuel du vendeur. Si la limite est atteinte, empêcher la création de nouveaux
                produits. Étendre ce contrôle à d'autres fonctionnalités (personnalisation de boutique, accès aux statistiques avancées) en fonction du plan.
            * Tâche Planifiée (Scheduler) : Mettre en place une tâche quotidienne/hebdomadaire pour vérifier les abonnements expirés et mettre à jour leur statut.

Catégorie : Expérience Utilisateur & Confiance (Priorité Haute)

7. Système d'Évaluation et de Commentaires (Reviews & Ratings)
    * Explication : Les avis clients sont cruciaux pour la confiance et la décision d'achat sur une plateforme e-commerce.
    * Tâches Spécifiques :
        * Migration : Créer une table evaluations (ou commentaires_produits). Champs possibles : produit_id, user_id (client), note (ex: 1-5 étoiles), commentaire, statut (ex: 'en
            attente', 'approuvé', 'rejeté' pour modération), created_at.
        * Modèle : Créer le modèle Evaluation.
        * API pour Clients (`EvaluationController`) :
            * POST /api/evaluations : Soumettre une évaluation pour un produit (uniquement si le client a acheté le produit - à vérifier via detail_commandes).
        * API pour Public/Clients :
            * GET /api/produits/{id}/evaluations : Récupérer toutes les évaluations d'un produit.
            * GET /api/produits/{id}/average-rating : Calculer la note moyenne d'un produit.
        * API pour Admin (Modération) :
            * GET /api/admin/evaluations/pending : Lister les évaluations en attente de modération.
            * PATCH /api/admin/evaluations/{id}/status : Approuver ou rejeter des commentaires.

8. Messagerie Basique Client-Vendeur
    * Explication : Permettre aux clients de poser des questions directement aux vendeurs sur un produit ou une commande.
    * Tâches Spécifiques :
        * Migration : Créer une table messages. Champs possibles : expediteur_id (FK vers users), destinataire_id (FK vers users), sujet, contenu, lu_at (timestamp), created_at.
        * Modèle : Créer le modèle Message.
        * API pour Clients (`MessagerieController`) :
            * POST /api/messages : Envoyer un message à un vendeur (lié à un produit ou une commande).
        * API pour Vendeurs :
            * GET /api/vendor/messages : Lister les messages reçus.
            * POST /api/vendor/messages/{id}/reply : Répondre à un message.
            * PATCH /api/vendor/messages/{id}/read : Marquer un message comme lu.

---

Ce plan vous offre une feuille de route complète pour le backend de votre MVP, intégrant les améliorations nécessaires et les nouvelles fonctionnalités clés pour un modèle d'abonnement.


