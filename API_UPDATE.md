# Mise à jour API - Plateforme de dons (Binôme 16)

## 📋 Modifications implémentées

### 1. ✅ Structure des données (Campaign) - Snake_case
- ✓ JSON retourné en `snake_case`: `montant_objectif`, `montant_collecte`, `image_url`, `progress_percentage`, `unique_donor_count`, `is_urgent`
- ✓ Category retournée en tant que simple String
- ✓ CampaignResource configure correctement les noms de champs

### 2. ✅ Flux de paiement Stripe (CRITIQUE)
**Route POST `/api/stripe/payment-intent`**
- Crée un PaymentIntent Stripe
- Retourne: `clientSecret`, `publishableKey`, `customerId`, `amount`, `currency`, `campaign_id`
- Exemple réponse:
```json
{
  "data": {
    "clientSecret": "pi_..._secret_...",
    "paymentIntentId": "pi_...",
    "publishableKey": "pk_test_...",
    "customerId": "cus_...",
    "amount": 10000,
    "currency": "eur",
    "campaign_id": 1
  }
}
```

**Route POST `/api/donations`**
- Vérifie le PaymentIntent auprès de Stripe AVANT d'enregistrer
- Vérifie que le statut est `succeeded`
- Vérifie la correspondance du montant
- Crée un Customer Stripe si nécessaire
- Génère automatiquement un reçu PDF
- Envoie un email de remerciement

Payload requis:
```json
{
  "campaign_id": 1,
  "amount": 10000,
  "stripe_payment_id": "pi_...",
  "is_recurring": false
}
```

Réponse (wrapper data):
```json
{
  "data": {
    "donation_id": 1,
    "receipt_url": "http://api.local/donations/1/receipt",
    "is_recurring": false,
    "donation": { ... }
  }
}
```

**Route GET `/donations/{donation}/receipt`**
- Télécharge le reçu PDF de la donation

### 3. ✅ Abonnements (Recurring)
- Si `is_recurring: true` dans la donation:
  - Crée un abonnement Stripe avec facturation mensuelle
  - Enregistre l'abonnement en base (table `subscriptions`)
  - Les paiements récurrents sont gérés par Stripe

Configuration requise dans `.env`:
```
STRIPE_MONTHLY_PRICE_ID=price_monthly_donation
```

### 4. ✅ Reçus fiscaux PDF
- Générés automatiquement à chaque donation (dompdf)
- Attachés aux emails
- Accessible via `GET /donations/{donation}/receipt`
- Field `receipt_url` dans la réponse des donations
- Field `receipt_number` généré (REC-XXXXXXXX)

### 5. ✅ Notifications Push (FCM)
**Route POST `/api/fcm-token`** (Authentifiée Sanctum)
- Enregistre le token FCM de l'utilisateur
- Payload: `{ "fcm_token": "token_android..." }`

**Observer UpdateObserver**
- Déclenché à chaque création/mise à jour de campagne Update
- Envoie une notification push à TOUS les donateurs de la campagne
- Notification inclut: titre de la mise à jour, contenu, metadata (campaign_id, update_id)
- Utilise Firebase Cloud Messaging (FCM)

Configuration requise dans `.env`:
```
FIREBASE_SERVER_KEY=your_firebase_server_key_here
```

### 6. ✅ Authentification
- Sanctum correctement configuré
- Token Bearer dans le header: `Authorization: Bearer {token}`
- Routes protégées: donations, campaigns (create/update), updates (create/update), fcm-token

### 7. ✅ Enveloppe 'data' pour toutes les réponses API
**Avant:**
```json
{ "id": 1, "title": "Campaign" }
```

**Après:**
```json
{ "data": { "id": 1, "title": "Campaign" } }
```

Contrôleurs mis à jour:
- ✓ CampaignController (index, show, store, update)
- ✓ UpdateController (index, show, store, update)
- ✓ DonationController (index, store)
- ✓ AuthController (register, login, logout, updateFcmToken)
- ✓ StripePaymentController (createPaymentIntent)

---

## 🚀 Routes API complètes

### Authentification (publique)
```
POST   /api/register                    - Créer un compte
POST   /api/login                       - Se connecter
POST   /api/logout                      - Se déconnecter (auth)
```

### Campagnes (publique pour GET, auth pour CRUD)
```
GET    /api/campaigns                   - Lister toutes les campagnes
GET    /api/campaigns/{id}              - Détails d'une campagne
POST   /api/campaigns                   - Créer une campagne (admin)
PUT    /api/campaigns/{id}              - Mettre à jour une campagne (admin)
DELETE /api/campaigns/{id}              - Supprimer une campagne (admin)
```

### Mises à jour de campagnes (publique pour GET, auth pour CRUD)
```
GET    /api/campaigns/{id}/updates      - Lister les updates d'une campagne
GET    /api/campaigns/{id}/updates/{id} - Détails d'une update
POST   /api/campaigns/{id}/updates      - Créer une update (admin)
PUT    /api/campaigns/{id}/updates/{id} - Mettre à jour une update (admin)
DELETE /api/campaigns/{id}/updates/{id} - Supprimer une update (admin)
```

### Donations
```
POST   /api/stripe/payment-intent       - Créer un PaymentIntent (auth)
POST   /api/donations                   - Créer une donation
GET    /api/donations                   - Lister mes donations (auth)
GET    /donations/{id}/receipt          - Télécharger reçu PDF
```

### Utilisateur
```
POST   /api/fcm-token                   - Enregistrer token FCM (auth)
```

### Admin
```
GET    /api/admin/financial-report      - Rapport financier (admin)
```

---

## 📝 Configuration requise

### Variables d'environnement (.env)
```bash
# Stripe
STRIPE_KEY=pk_test_...
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_CURRENCY=eur
STRIPE_MONTHLY_PRICE_ID=price_monthly_donation

# Firebase/FCM
FIREBASE_SERVER_KEY=your_firebase_server_key_here
FIREBASE_API_KEY=your_firebase_api_key_here
```

### Dépendances Laravel
- ✓ `barryvdh/laravel-dompdf` (PDF)
- ✓ `laravel/sanctum` (Auth tokens)
- ✓ `stripe/stripe-php` (Stripe SDK)

### Configuration Stripe
1. Créer un Product "Monthly Recurring Donations"
2. Créer une Price (monthly) pour ce product
3. Récupérer le Price ID et ajouter à `STRIPE_MONTHLY_PRICE_ID`

### Configuration Firebase
1. Créer un projet Firebase
2. Générer une clé serveur (Server Key) depuis Firebase Console
3. Ajouter à `FIREBASE_SERVER_KEY`

---

## 🔍 Points critiques pour Kotlin

### 1. Headers requis
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### 2. Format des réponses
Toutes les réponses sont enveloppées:
```json
{
  "data": [ ... ]  // ou { ... } pour un seul objet
}
```

### 3. PaymentIntent Flow
1. `POST /api/stripe/payment-intent` → obtient clientSecret, publishableKey, customerId
2. Côté Kotlin: Utiliser Stripe SDK pour confirmer le paiement
3. `POST /api/donations` avec stripe_payment_id → backend vérifie et valide

### 4. Récurrence
```json
{
  "campaign_id": 1,
  "amount": 10000,
  "stripe_payment_id": "pi_...",
  "is_recurring": true
}
```
Backend crée automatiquement un abonnement Stripe mensuel.

### 5. Notifications FCM
1. `POST /api/fcm-token` pour enregistrer le token
2. Backend envoie automatiquement lors de: `POST /api/campaigns/{id}/updates`

---

## ✨ Résumé des changements

| Fichier | Modification |
|---------|-------------|
| `StripePaymentController.php` | ✓ Ajout Customer Stripe, publishableKey, customerId dans réponse |
| `DonationController.php` | ✓ Vérification PaymentIntent, création subscriptions, wrapper data |
| `CampaignController.php` | ✓ Wrapper data pour toutes les réponses |
| `UpdateController.php` | ✓ Wrapper data pour toutes les réponses |
| `AuthController.php` | ✓ Wrapper data pour toutes les réponses |
| `UpdateObserver.php` | ✓ CRÉÉ - notifications FCM lors de publication d'update |
| `AppServiceProvider.php` | ✓ Enregistrement UpdateObserver |
| `config/services.php` | ✓ Ajout config Stripe (public_key) et Firebase |
| `.env` | ✓ Variables pour Stripe et Firebase |

---

## 🧪 Testage recommandé

1. **Authentification**
   - Register/Login → récupérer token
   - Vérifier Sanctum middleware

2. **Flux complet de don**
   - `POST /api/stripe/payment-intent` → obtenir clientSecret
   - Confirm paiement côté Kotlin
   - `POST /api/donations` → valider le don
   - Vérifier reçu PDF généré

3. **Donations récurrentes**
   - `POST /api/donations` avec `is_recurring: true`
   - Vérifier création dans table subscriptions

4. **Notifications FCM**
   - `POST /api/fcm-token` pour enregistrer
   - `POST /api/campaigns/{id}/updates` → vérifier notification envoyée

5. **Données Campaign**
   - Vérifier snake_case: `montant_objectif`, `montant_collecte`, `image_url`
   - Vérifier `progress_percentage` et `unique_donor_count`
   - Vérifier category en String

---

## 📧 Support

Pour toute question sur l'implémentation, consultez les fichiers modifiés ou testez via Postman/Insomnia.
