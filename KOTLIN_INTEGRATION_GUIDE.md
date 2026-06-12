# Guide d'intégration API pour l'équipe Kotlin (Binôme 16)

## 🎯 Vue d'ensemble

L'API a été mise à jour pour correspondre EXACTEMENT aux exigences du client Android/Kotlin. Tous les endpoints retournent maintenant un wrapper `data` et supportent les abonnements Stripe récurrents avec notifications FCM.

---

## 🔐 Authentification (Sanctum)

### 1. Registration
```
POST /api/register
Content-Type: application/json

{
  "name": "John Donor",
  "email": "john@example.com",
  "password": "SecurePass123"
}

Response:
{
  "data": {
    "id": 1,
    "name": "John Donor",
    "email": "john@example.com",
    "token": "1|GmPvX2Axxxxx...",
    "user": { ... }
  }
}
```

### 2. Login
```
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "SecurePass123"
}

Response:
{
  "data": {
    "token": "2|AbC1234xxxxx...",
    "user": { ... }
  }
}
```

**Important:** Sauvegarder le token et l'utiliser pour toutes les requêtes authentifiées:
```
Authorization: Bearer {token}
```

---

## 💰 Flux de paiement Stripe (CRITIQUE)

### Étape 1: Créer un PaymentIntent
```
POST /api/stripe/payment-intent
Authorization: Bearer {token}
Content-Type: application/json

{
  "campaign_id": 1,
  "amount": 10000,     // En centimes (EUR)
  "is_recurring": false
}

Response:
{
  "data": {
    "clientSecret": "pi_xxx_secret_xxx",
    "paymentIntentId": "pi_xxx",
    "publishableKey": "pk_test_xxx",    // ← Utiliser dans Kotlin
    "customerId": "cus_xxx",             // ← Utilisateur Stripe
    "amount": 10000,
    "currency": "eur",
    "campaign_id": 1
  }
}
```

### Étape 2: Confirmer le paiement côté Kotlin
Utiliser le Stripe SDK Kotlin avec:
- `clientSecret` du PaymentIntent
- `publishableKey` pour initialiser Stripe
- Méthode de paiement de l'utilisateur

### Étape 3: Soumettre la donation
```
POST /api/donations
Authorization: Bearer {token}
Content-Type: application/json

{
  "campaign_id": 1,
  "amount": 10000,
  "stripe_payment_id": "pi_xxx",      // ← PaymentIntent ID
  "is_recurring": false
}

Response:
{
  "data": {
    "donation_id": 42,
    "receipt_url": "http://api.local/donations/42/receipt",
    "is_recurring": false,
    "donation": {
      "id": 42,
      "campaign_id": 1,
      "amount": 10000,
      "status": "succeeded",
      "receipt_url": "http://api.local/donations/42/receipt",
      "created_at": "2024-06-09T10:30:00Z"
    }
  }
}
```

---

## 🔄 Abonnements Récurrents

Pour activer les dons mensuels récurrents:

```json
{
  "campaign_id": 1,
  "amount": 5000,
  "stripe_payment_id": "pi_xxx",
  "is_recurring": true          // ← Clé importante
}
```

Le backend va:
1. ✓ Traiter le paiement initial
2. ✓ Créer un abonnement Stripe mensuel
3. ✓ Facturer automatiquement chaque mois
4. ✓ Enregistrer les paiements récurrents comme donations

---

## 📱 Notifications Push (FCM)

### Étape 1: Enregistrer le token FCM
```
POST /api/fcm-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "fcm_token": "exxxxxxxxxxxxxxxxxxx...:"
}

Response:
{
  "data": {
    "message": "FCM token updated",
    "user": { ... }
  }
}
```

### Étape 2: Recevoir les notifications
Lorsqu'une mise à jour de campagne est publiée (`POST /api/campaigns/{id}/updates`), tous les donateurs reçoivent une notification push avec:

- **Title:** Titre de la mise à jour
- **Body:** Contenu de la mise à jour
- **Data:**
  - `campaign_id`: ID de la campagne
  - `update_id`: ID de la mise à jour
  - `campaign_title`: Titre de la campagne

Exemple payload reçu:
```json
{
  "notification": {
    "title": "Mise à jour importante",
    "body": "La collecte a atteint 50%!",
    "sound": "default"
  },
  "data": {
    "campaign_id": "1",
    "update_id": "5",
    "campaign_title": "Secours d'urgence",
    "click_action": "FLUTTER_NOTIFICATION_CLICK"
  }
}
```

---

## 📊 Données des campagnes (Snake_case)

Toutes les réponses campagne utilisent le format `snake_case`:

```json
{
  "data": {
    "id": 1,
    "title": "Secours d'urgence",
    "description": "Aide aux sinistrés",
    "category": "Urgence",                    // ← String simple
    "image_url": "https://example.com/img.jpg",
    "montant_collecte": 250000,               // ← Montant collecté (centimes)
    "montant_objectif": 500000,               // ← Objectif (centimes)
    "progress_percentage": 50.0,              // ← Pourcentage
    "unique_donor_count": 127,                // ← Nombre de donateurs uniques
    "is_urgent": true,
    "created_at": "2024-06-01T10:00:00Z",
    "updated_at": "2024-06-09T15:30:00Z"
  }
}
```

**Clés à utiliser en Kotlin:**
- `montant_objectif` (not `goal_amount`)
- `montant_collecte` (not `collected_amount`)
- `image_url` (not `image`)
- `progress_percentage`
- `unique_donor_count`
- `is_urgent`

---

## 📋 Endpoints complets

### Publics (sans authentification)
```
GET  /api/campaigns                    # Lister les campagnes
GET  /api/campaigns/{id}               # Détails d'une campagne
GET  /api/campaigns/{id}/updates       # Updates d'une campagne
GET  /api/campaigns/{id}/updates/{id}  # Détails d'une update
GET  /donations/{id}/receipt           # Télécharger reçu PDF

POST /api/register                     # S'inscrire
POST /api/login                        # Se connecter
```

### Authentifiés (Bearer token)
```
POST /api/stripe/payment-intent        # Créer PaymentIntent
POST /api/donations                    # Soumettre donation
GET  /api/donations                    # Mes donations

POST /api/fcm-token                    # Enregistrer token FCM
POST /api/logout                       # Se déconnecter
```

### Admin (authentifiés + role admin)
```
POST /api/campaigns                    # Créer campagne
PUT  /api/campaigns/{id}               # Modifier campagne
DELETE /api/campaigns/{id}             # Supprimer campagne

POST /api/campaigns/{id}/updates       # Créer update
PUT  /api/campaigns/{id}/updates/{id}  # Modifier update
DELETE /api/campaigns/{id}/updates/{id}# Supprimer update

GET  /api/admin/financial-report       # Rapport financier
```

---

## 🛠️ Implémentation Kotlin

### 1. Dépendances à ajouter
```gradle
// Stripe
implementation 'com.stripe:stripe-android:20.x.x'

// Firebase
implementation 'com.google.firebase:firebase-messaging:23.x.x'

// Retrofit/HTTP
implementation 'com.squareup.retrofit2:retrofit:2.9.x'
implementation 'com.squareup.retrofit2:converter-gson:2.9.x'

// Gson
implementation 'com.google.code.gson:gson:2.10.x'
```

### 2. Model pour Campaign
```kotlin
data class CampaignResponse(
    @SerializedName("data")
    val campaign: Campaign
)

data class Campaign(
    val id: Int,
    val title: String,
    val description: String,
    val category: String,           // Simple String
    val image_url: String,
    val montant_collecte: Int,      // En centimes
    val montant_objectif: Int,      // En centimes
    val progress_percentage: Double,
    val unique_donor_count: Int,
    val is_urgent: Boolean,
    val created_at: String,
    val updated_at: String
)
```

### 3. Payment Intent Request
```kotlin
data class PaymentIntentRequest(
    val campaign_id: Int,
    val amount: Int,                // En centimes
    val is_recurring: Boolean = false
)

data class PaymentIntentResponse(
    val data: PaymentIntentData
)

data class PaymentIntentData(
    val clientSecret: String,
    val paymentIntentId: String,
    val publishableKey: String,
    val customerId: String,
    val amount: Int,
    val currency: String,
    val campaign_id: Int
)
```

### 4. Donation Request
```kotlin
data class DonationRequest(
    val campaign_id: Int,
    val amount: Int,
    val stripe_payment_id: String,
    val is_recurring: Boolean = false
)

data class DonationResponse(
    val data: DonationData
)

data class DonationData(
    val donation_id: Int,
    val receipt_url: String,
    val is_recurring: Boolean,
    val donation: Donation
)
```

### 5. FCM Token Registration
```kotlin
data class FCMTokenRequest(
    val fcm_token: String
)

// Dans FirebaseMessagingService:
private fun sendTokenToBackend(token: String) {
    val request = FCMTokenRequest(token)
    // POST /api/fcm-token avec Authorization: Bearer {token}
}
```

---

## 📱 Architecture recommandée

```
App/
├── data/
│   ├── api/
│   │   ├── ApiService.kt            # Retrofit interface
│   │   └── AuthInterceptor.kt       # Ajoute Authorization header
│   ├── repository/
│   │   ├── CampaignRepository.kt
│   │   ├── DonationRepository.kt
│   │   └── AuthRepository.kt
│   └── local/
│       └── SharedPreferences (Token storage)
├── models/
│   ├── Campaign.kt
│   ├── Donation.kt
│   ├── PaymentIntent.kt
│   └── ...
├── ui/
│   ├── campaign/
│   ├── payment/
│   ├── donation/
│   └── notifications/
├── viewmodel/
│   ├── CampaignViewModel.kt
│   ├── DonationViewModel.kt
│   └── AuthViewModel.kt
└── services/
    └── FCMService.kt               # Notifications
```

---

## ✅ Checklist de développement

- [ ] Configurer Retrofit avec URL API
- [ ] Implémenter AuthInterceptor (ajoute token Bearer)
- [ ] Intégrer Stripe SDK
- [ ] Implémenter écran de liste des campagnes
- [ ] Implémenter écran de détails campagne
- [ ] Implémenter écran de paiement (avec PaymentIntent)
- [ ] Gérer les dons récurrents
- [ ] Implémenter FCM Token registration
- [ ] Tester le flux complet de donation
- [ ] Vérifier la structure snake_case des données
- [ ] Tester les reçus PDF
- [ ] Tester les notifications push

---

## 🔒 Sécurité importante

1. **Jamais** stocker le token en clair
2. **Toujours** utiliser le header `Authorization: Bearer {token}`
3. **Ne jamais** exposer STRIPE_SECRET côté client
4. Utiliser uniquement `publishableKey` en Kotlin
5. Valider les montants côté client ET serveur
6. Implémenter le timeout de session

---

## 🧪 Test avec Postman

1. Importez `ONG_API_Postman.json`
2. Configurez `base_url` = votre URL API
3. Testez d'abord: Register → Login
4. Copiez le token dans la variable `token`
5. Testez le flux complet: PaymentIntent → Donation

---

## 📝 Notes importantes

- Tous les montants sont en **centimes** (EUR)
- Pas de décimales dans les montants: 100 EUR = 10000 (centimes)
- Les réponses sont **toujours** enveloppées dans `{ "data": ... }`
- Les erreurs retournent aussi `{ "message": "..." }`
- Timestamps en format ISO 8601 (UTC)

---

## ⚠️ Points critiques à vérifier

1. **Payment Verification:** Le backend vérifie que le PaymentIntent est `succeeded` AVANT d'enregistrer la donation
2. **Customer Creation:** Stripe crée un Customer automatiquement pour chaque utilisateur
3. **Recurring:** Les abonnements mensuels sont créés automatiquement si `is_recurring: true`
4. **Receipts:** Les PDFs sont générés et envoyés par email automatiquement
5. **FCM:** Les notifications sont envoyées à TOUS les donateurs lors de la publication d'une update

---

## 🆘 Troubleshooting

### PaymentIntent échoue
- Vérifier que le token d'authentification est valide
- Vérifier que campaign_id existe
- Vérifier que STRIPE_KEYS sont configurées

### Donations non enregistrées
- Le backend vérifie PaymentIntent auprès de Stripe
- Si le status n'est pas `succeeded`, la donation échoue
- Vérifier les logs d'erreur: `payment has not been confirmed`

### FCM Notifications ne sont pas reçues
- S'assurer que le token FCM a été enregistré via `/api/fcm-token`
- Vérifier que FIREBASE_SERVER_KEY est configurée
- Vérifier dans les logs de Firebase Cloud Messaging

---

**Dernier update:** 9 juin 2026  
**Version API:** 2.0 (Binôme 16)  
**Status:** ✅ Prêt pour production
