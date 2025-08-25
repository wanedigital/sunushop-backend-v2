# MVP Backend - État Actuel des Implémentations

Ce document a pour but de fournir une vue d'ensemble de l'état actuel du backend de SunuShop et de définir les prochaines étapes pour atteindre un Produit Minimum Viable (MVP) opérationnel, en se concentrant sur le modèle multi-boutiques et le système d'abonnement vendeur.

---

## 1. Schéma de Base de Données (Basé sur les Migrations)

Le schéma de base de données est bien établi et fournit une base solide pour la plateforme multi-boutiques. Les entités principales sont :

*   **`profils`** : Gère les rôles des utilisateurs (Administrateur, Client, Vendeur).
*   **`users`** : Stocke les informations des utilisateurs (nom, prénom, adresse, contact, email, mot de passe, statut) et est lié à un profil.
*   **`categories`** : Définit les catégories de produits.
*   **`produits`** : Contient les détails des produits (libellé, description, prix, quantité, image, disponibilité) et est lié à une catégorie.
*   **`boutiques`** : Représente les boutiques des vendeurs (nom, adresse, logo, numéro commercial, statut) et est liée à un utilisateur vendeur.
*   **`produit_boutiques`** : Table pivot pour gérer la relation plusieurs-à-plusieurs entre les produits et les boutiques.
*   **`commandes`** : Enregistre les commandes (numéro, date, état, total) et gère à la fois les utilisateurs enregistrés et les clients invités (nom, email, adresse, etc.).
*   **`detail_commandes`** : Détaille les articles de chaque commande (prix unitaire, quantité) et les lie aux produits et commandes.
*   **`type_paiements`** : Définit les différents types de paiement.
*   **`paiments`** : Enregistre les transactions de paiement, liées aux commandes et aux types de paiement.

## 2. Fonctionnalités API Implémentées (Basé sur les Contrôleurs et Routes)

Le backend dispose déjà d'une couverture fonctionnelle significative pour un MVP :

### 2.1. Authentification et Gestion des Utilisateurs
*   **Inscription :** Permet l'inscription de nouveaux utilisateurs avec les profils 'Client' et 'Vendeur'.
*   **Connexion/Déconnexion :** Fonctionnalités standard de connexion et déconnexion sécurisées.
*   **Gestion de Profil :** Les utilisateurs peuvent consulter et mettre à jour leurs informations personnelles.
*   **Gestion des Mots de Passe :** Fonctionnalités de réinitialisation (via email) et de changement de mot de passe.
*   **Gestion Admin des Utilisateurs :** Les administrateurs peuvent lister, consulter, mettre à jour et désactiver des utilisateurs, ainsi que gérer leurs rôles.

### 2.2. Gestion des Boutiques
*   **Création de Boutique :** Les vendeurs peuvent créer leur boutique, avec upload de logo et association automatique à leur compte.
*   **Affichage des Boutiques :** Possibilité de lister toutes les boutiques et de consulter les détails d'une boutique spécifique (avec ses produits).
*   **Produits par Boutique :** Affichage des produits associés à une boutique donnée.
*   **Produits du Vendeur :** Les vendeurs peuvent lister et gérer leurs propres produits.
*   **Recherche et Filtrage :** Recherche de produits au sein d'une boutique spécifique.
*   **Catégories par Boutique :** Récupération des catégories de produits disponibles dans une boutique.

### 2.3. Gestion des Produits
*   **CRUD Complet :** Création, lecture, mise à jour et suppression de produits.
*   **Liaison Vendeur/Boutique :** Les produits sont automatiquement liés à la boutique du vendeur qui les crée.
*   **Gestion des Images :** Support pour l'upload et la mise à jour des images de produits.

### 2.4. Gestion des Catégories
*   **CRUD de Base :** Fonctionnalités de base pour la gestion des catégories de produits.

### 2.5. Gestion des Commandes
*   **Création de Commande Robuste :** Gère la création de commandes pour les utilisateurs connectés et les clients invités.
*   **Gestion du Stock :** Vérification de la disponibilité et décrémentation automatique du stock lors de la commande, avec gestion transactionnelle pour l'intégrité des données.
*   **Annulation de Commande :** Permet l'annulation de commandes avec réincrémentation automatique du stock.
*   **Consultation de Commande :** Les utilisateurs peuvent consulter leurs propres commandes ; les clients invités peuvent récupérer leur commande via numéro et email.
*   **Mise à Jour du Statut :** Les vendeurs peuvent mettre à jour le statut des commandes qui les concernent.
*   **Commandes par Boutique :** Les vendeurs peuvent lister les commandes liées à leur boutique.

### 2.6. Validation de Profil Vendeur
*   **Activation Sécurisée :** Un processus d'activation du profil 'Vendeur' via un lien signé envoyé par email est en place.

### 2.7. Statistiques
*   **Statistiques Administrateur :** Résumé global de la plateforme, croissance des utilisateurs, classements des boutiques et produits.
*   **Statistiques Vendeur :** Rapports sur les ventes et les meilleurs clients pour les vendeurs.

---


# Feuille de Route MVP Backend - SunuShop



---

## . Explication approfondu

### 3. Fonctionnalités API Implémentées (Contrôleurs)

*   **Authentification & Gestion des Utilisateurs (`AuthController`, `UserController`) :**
    *   Inscription (Client, Vendeur), connexion, déconnexion.
    *   Récupération/réinitialisation de mot de passe.
    *   Mise à jour du profil utilisateur.
    *   Gestion complète des utilisateurs par l'administrateur (liste, détail, mise à jour, désactivation, gestion des rôles).
*   **Gestion des Boutiques (`BoutiqueController`) :**
    *   Création de boutique par les vendeurs (avec upload de logo).
    *   Affichage des détails d'une boutique et de ses produits.
    *   Listing des produits d'une boutique spécifique.
    *   Listing des produits du vendeur authentifié.
    *   Recherche et filtrage de produits par boutique.
    *   Listing des catégories par boutique.
*   **Gestion des Produits (`ProduitController`) :**
    *   CRUD complet pour les produits (création, affichage, mise à jour, suppression).
    *   Liaison automatique des produits à la boutique du vendeur lors de la création.
    *   Gestion de l'upload et de la mise à jour des images de produits.
*   **Gestion des Catégories (`CategorieController`) :**
    *   CRUD de base pour les catégories.
*   **Gestion des Commandes (`CommandeController`) :**
    *   Création de commandes robuste pour les utilisateurs connectés et les clients invités.
    *   Vérification et décrémentation du stock lors de la commande (avec gestion transactionnelle).
    *   Annulation de commande avec réincrémentation du stock.
    *   Affichage des commandes de l'utilisateur connecté.
    *   Récupération des commandes d'invités par numéro et email.
    *   Mise à jour du statut des commandes (par les vendeurs).
    *   Listing des commandes par boutique (pour les vendeurs).
*   **Validation de Profil Vendeur (`ProfilValidationController`) :**
    *   Activation du profil "Vendeur" via un lien signé envoyé par email.
*   **Statistiques (`StatistiqueController`) :**
    *   Statistiques complètes pour l'administrateur (résumé global, croissance utilisateurs, classement boutiques/produits).
    *   Statistiques de vente et meilleurs clients pour les vendeurs.

---

## 4. Feuille de Route MVP Backend (Tâches à Réaliser)

Cette section détaille les tâches restantes, classées par priorité, pour finaliser le MVP.

### 4.1. Sécurité & Autorisation (Priorité Haute)

1.  **Implémenter les Politiques d'Autorisation (Laravel Policies)**
    *   **Objectif :** Assurer que les utilisateurs n'accèdent et ne modifient que les ressources auxquelles ils sont autorisés.
    *   **Détails :**
        *   **`BoutiqueController` (`update`, `destroy`) :** Seul le propriétaire de la boutique ou un administrateur peut modifier/supprimer.
        *   **`ProduitController` (`update`, `destroy`) :** Seul le vendeur propriétaire du produit peut le modifier/supprimer.
        *   **`CategorieController` (`store`, `show`, `update`, `destroy`) :** Restreindre ces actions aux administrateurs uniquement.

### 4.2. Robustesse & Logique Métier (Priorité Moyenne)

2.  **Affiner le Flux d'Approbation des Vendeurs et Boutiques**
    *   **Objectif :** Compléter le processus d'activation des vendeurs et de leurs boutiques.
    *   **Détails :**
        *   **`AuthController` (`register`) :** Pour les nouveaux vendeurs, définir leur `status` initial sur 'inactif' ou 'en attente'.
        *   **`BoutiqueController` (`store`) :** Définir le `status` initial d'une nouvelle boutique sur 'en attente' ou 'inactif'.
        *   **`ProfilValidationController` (`valider`) :** Après validation du profil "Vendeur", cette méthode doit aussi :
            *   Mettre à jour le `status` de l'utilisateur à 'actif'.
            *   Mettre à jour le `status` de la boutique associée à 'ouvret'.

3.  **Nettoyage et Optimisation du Code**
    *   **Objectif :** Améliorer la maintenabilité et la clarté du code.
    *   **Détails :**
        *   **`ProduitController` (`index`) :** Supprimer le code en double et intégrer correctement pagination, recherche et eager loading.
        *   **`CommandeController` :** Supprimer les méthodes `ventesVendeurParPeriode` et `meilleursClientsVendeur` (doublons de `StatistiqueController`).
        *   **`BoutiqueController` :** Supprimer la méthode `allboutique()` (redondante) et les blocs de code commentés.
        *   **`User` Model :** Ajouter le champ `photo` à la migration `users_table` si ce n'est pas déjà fait, pour correspondre au `$fillable` du modèle.
        *   **`ProduitBoutique` Model :** Activer la relation `boutique()` si elle est toujours commentée.

4.  **Affiner l'Autorisation de la Méthode `CommandeController::show` pour les Invités**
    *   **Objectif :** Sécuriser la consultation des commandes par les invités.
    *   **Détails :** Modifier la méthode `show` pour les utilisateurs non connectés afin qu'elle exige également le `numeroCommande` et l'`email_client` pour la vérification, ou rediriger vers `getCommandeInvite`.

### 4.3. Nouvelles Fonctionnalités pour le MVP

Ces fonctionnalités sont cruciales pour le modèle économique et l'expérience utilisateur.

5.  **Système d'Abonnement Vendeur**
    *   **Objectif :** Mettre en place la monétisation de la plateforme via des plans d'abonnement pour les vendeurs.
    *   **Détails :**
        *   **Migrations :**
            *   Table `plans` : `id`, `name`, `price`, `currency`, `duration_in_days`, `features` (JSON).
            *   Table `subscriptions` : `id`, `user_id`, `plan_id`, `start_date`, `end_date`, `status`, `payment_status`.
        *   **Modèles :** `Plan`, `Subscription`.
        *   **API Admin (`PlanController`) :** CRUD pour les plans d'abonnement.
        *   **API Admin (`SubscriptionController`) :** Gestion des abonnements (lister, voir, attribuer manuellement, changer statut).
        *   **API Vendeur (`SubscriptionController`) :** Voir abonnement actuel, lister plans disponibles, initier/annuler abonnement.
        *   **Logique d'Intégration :**
            *   **Inscription Vendeur :** Attribution automatique au plan "Découverte" (gratuit).
            *   **Contrôle des Fonctionnalités :** Vérifier le plan du vendeur pour limiter les fonctionnalités (ex: `max_products` dans `ProduitController::store`).
            *   **Tâche Planifiée :** Vérifier et mettre à jour les statuts des abonnements expirés.

6.  **Système d'Évaluation et de Commentaires (Reviews & Ratings)**
    *   **Objectif :** Instaurer la confiance et aider les clients dans leurs décisions d'achat.
    *   **Détails :**
        *   **Migration :** Table `evaluations` : `produit_id`, `user_id`, `note`, `commentaire`, `statut` (pour modération).
        *   **Modèle :** `Evaluation`.
        *   **API Clients (`EvaluationController`) :** Soumettre une évaluation (uniquement si produit acheté).
        *   **API Public/Clients :** Récupérer évaluations d'un produit, calculer note moyenne.
        *   **API Admin :** Modération des évaluations.

7.  **Messagerie Basique Client-Vendeur**
    *   **Objectif :** Faciliter la communication directe entre clients et vendeurs.
    *   **Détails :**
        *   **Migration :** Table `messages` : `expediteur_id`, `destinataire_id`, `sujet`, `contenu`, `lu_at`.
        *   **Modèle :** `Message`.
        *   **API Clients (`MessagerieController`) :** Envoyer un message à un vendeur.
        *   **API Vendeurs :** Lister messages reçus, répondre, marquer comme lu.

---

Ce document servira de référence principale pour le développement du backend. N'hésitez pas à le mettre à jour au fur et à mesure de l'avancement.

---

---

💡 Modèles d'Abonnement pour SunuShop (Exemples)

Voici quelques idées de plans d'abonnement que vous pourriez proposer à vos vendeurs, du plus basique au plus avancé. L'idée est d'offrir des fonctionnalités supplémentaires à chaque
niveau pour inciter les vendeurs à monter en gamme.

1. Plan "Découverte" (Gratuit)
* Cible : Nouveaux vendeurs, petits artisans, ceux qui veulent tester la plateforme.
* Fonctionnalités :
    * Nombre limité de produits (ex: 5 à 10 produits maximum).
    * Page de boutique standard.
    * Accès aux fonctionnalités de base de gestion des commandes.
    * Support client standard (via FAQ ou email).
* Objectif : Attirer un maximum de vendeurs et leur permettre de démarrer sans friction.

2. Plan "Essentiel" (Payant, ex: 19.99€/mois)
* Cible : Vendeurs en croissance, qui ont validé leur modèle et veulent plus de visibilité.
* Fonctionnalités :
    * Nombre de produits illimité.
    * Options de personnalisation avancée de la boutique (bannière, couleurs, etc.).
    * Accès à des statistiques de vente basiques (ex: ventes mensuelles, produits les plus vendus).
    * Support client prioritaire.
    * (Optionnel) Accès à des promotions limitées sur la plateforme.
* Objectif : Convertir les vendeurs "Découverte" en abonnés payants en leur offrant plus de capacités.

3. Plan "Premium" (Payant, ex: 49.99€/mois)
* Cible : Vendeurs établis, marques, ceux qui génèrent un volume important de ventes.
* Fonctionnalités :
    * Toutes les fonctionnalités du plan "Essentiel".
    * Statistiques de vente avancées (ex: analyse des clients, performances par produit/catégorie, rapports personnalisables).
    * Accès à des outils marketing (ex: coupons de réduction, mise en avant de produits).
    * Support client dédié (gestionnaire de compte).
    * (Optionnel) Intégration API pour la gestion des stocks ou des commandes.
* Objectif : Maximiser les revenus par vendeur et fidéliser les plus gros contributeurs.

---

Ce document sert de point de départ pour comprendre l'état actuel du projet. Pour les prochaines étapes et les améliorations, veuillez consulter le document `MVP_Backend_Roadmap.md`.
