# ❓ FAQ - Abonnements BestQHSE

## 🔥 Questions les plus fréquentes

### 1. Pourquoi ne puis-je pas changer de norme ISO ?

**Réponse courte**: C'est une règle métier pour assurer la cohérence.

**Explication détaillée**:

- Les données que vous créez sont liées à une norme spécifique
- Changer de norme créerait des incohérences dans vos audits, processus, etc.
- **Solution**: Ajoutez la nouvelle norme en complément de l'existante
- Vous aurez accès aux deux normes simultanément

**Exemple**:

- ✅ Correct: ISO 9001 → Ajouter ISO 14001 = Les deux actives
- ❌ Impossible: ISO 9001 → Remplacer par ISO 14001

---

### 2. Mon abonnement expire dans 2 jours, que dois-je faire ?

**Action immédiate** 🚨:

1. Allez sur le **Dashboard des abonnements**
2. Cliquez sur **"Renouveler"** sur la carte concernée
3. Choisissez la durée (minimum 1 mois)
4. Validez le paiement

**Pourquoi c'est urgent**:

- Après expiration, **tout l'accès est bloqué** (même si d'autres normes sont actives)
- Risque d'arriérés après 15 jours

**Astuce**: Renouvelez maintenant, le nouvel abonnement commencera après l'expiration actuelle.

---

### 3. J'ai 2 normes actives, l'une expire demain, l'autre dans 3 mois. Que se passe-t-il ?

**Réponse**: Votre accès sera **complètement bloqué** demain.

**Pourquoi ?**

- Le système exige que **toutes les normes soient actives** simultanément
- Même si ISO 14001 est valide pendant 3 mois, l'expiration d'ISO 9001 bloque tout

**Solution**:

- Renouvelez ISO 9001 **avant expiration**
- Vos deux normes redeviendront actives
- Accès rétabli immédiatement

---

### 4. Puis-je utiliser la période d'essai sur plusieurs normes ?

**Non**, la période d'essai est **globale par entreprise**.

**Règles**:

- ✅ 3 mois gratuits
- ✅ Une seule fois, même si vous changez de norme
- ✅ Uniquement sur le siège social
- ❌ Pas de "reset" ni de nouvelle période d'essai

**Exemple**:

1. Période d'essai sur ISO 9001 → OK
2. Après 3 mois, ajout ISO 14001 → **Payant** (pas de 2ème période d'essai)

---

### 5. Que signifie "arriérés" et comment sont-ils calculés ?

**Définition**: Montant dû si vous renouvelez après expiration.

**Calcul**:

- **Jusqu'à 15 jours de retard**: Pas d'arriérés
- **Après 15 jours**: `ceil(jours_retard / 30)` mois minimum

**Exemples**:

| Jours de retard | Mois d'arriérés | Calcul          |
| --------------- | --------------- | --------------- |
| 5 jours         | 0               | Gratuit         |
| 10 jours        | 0               | Gratuit         |
| 16 jours        | 1               | ceil(16/30) = 1 |
| 25 jours        | 1               | ceil(25/30) = 1 |
| 35 jours        | 2               | ceil(35/30) = 2 |
| 65 jours        | 3               | ceil(65/30) = 3 |

**Prix**: `arriérés_mois × prix_mensuel_offre`

---

### 6. Puis-je renouveler un abonnement avant son expiration ?

**Oui, c'est même recommandé !**

**Avantages**:

- ✅ Pas de risque d'interruption
- ✅ Pas d'arriérés
- ✅ Tranquillité d'esprit

**Fonctionnement**:

- Le nouvel abonnement **commence après l'expiration** de l'actuel
- Vous ne "perdez" pas les jours restants

**Exemple**:

- Abonnement actuel: expire le 31/03/2026
- Renouvellement le 15/03/2026 pour 3 mois
- **Résultat**:
  - 15-31 mars: Ancien abonnement actif
  - 01 avril: Début du nouvel abonnement (3 mois)
  - Nouvelle expiration: 30/06/2026

---

### 7. Comment savoir quels modules sont inclus dans une offre ?

**Méthode 1: Avant souscription**

1. Menu → Abonnements → Offres disponibles
2. Cliquez sur une offre
3. Section "Modules inclus" affiche tout

**Méthode 2: Dashboard**

1. Menu → Abonnements → Tableau de bord
2. Chaque carte d'abonnement affiche le nombre de modules

**Modules par norme**:

| Norme     | Modules communs | Modules spécifiques | Total |
| --------- | --------------- | ------------------- | ----- |
| ISO 9001  | 6               | 1 (Réalisation)     | 7     |
| ISO 14001 | 6               | 0                   | 6     |
| ISO 45001 | 6               | 0                   | 6     |
| ISO 27001 | 6               | 0                   | 6     |
| ISO 22000 | 6               | 0                   | 6     |
| ISO 50001 | 6               | 0                   | 6     |

**Modules communs** (inclus dans toutes les normes):

- Contexte de l'organisme
- Leadership
- Planification
- Support
- Évaluation des performances
- Amélioration

---

### 8. Les alertes par email sont-elles automatiques ?

**Oui**, vous recevez des emails automatiques.

**Calendrier d'alertes**:

- **14 jours avant**: Email + notification in-app
- **7 jours avant**: Email + notification in-app
- **3 jours avant**: Email URGENT + notification in-app

**Personnalisation**:

- Menu → Paramètres → Notifications
- Vous pouvez désactiver les emails (mais pas les notifications in-app)

**Recommandation**: Gardez les emails activés pour ne rien manquer.

---

### 9. Que se passe-t-il si je ne paie pas après expiration ?

**Timeline**:

**Jour 0 (expiration)**:

- ✅ Accès bloqué immédiatement
- ✅ Données conservées
- ✅ Possibilité de renouveler

**Jours 1-15**:

- ✅ Renouvellement possible sans arriérés
- ⚠️ Accès toujours bloqué

**Jour 16+**:

- ✅ Renouvellement possible avec arriérés
- ⚠️ Accès toujours bloqué
- 💰 Calcul d'arriérés automatique

**Après 90 jours**:

- ⚠️ Risque de suppression du compte (contactez le support)

---

### 10. Puis-je transférer un abonnement d'un site à un autre ?

**Non**, les abonnements sont **liés au site** de souscription.

**Raison**:

- Les données créées sont associées au site
- Un transfert créerait des incohérences

**Alternative**:

1. Souscrire un nouvel abonnement pour le nouveau site
2. Laisser expirer l'ancien (ou le maintenir)

---

### 11. Comment fonctionne le multi-normes concrètement ?

**Scénario complet**:

**Étape 1**: Souscription ISO 9001

- ✅ 6 modules communs
- ✅ 1 module Réalisation
- **Total**: 7 modules visibles dans la sidebar

**Étape 2**: Ajout ISO 14001

- ✅ Les 6 modules communs (déjà là)
- ✅ 0 module supplémentaire (ISO 14001 n'a que des communs)
- **Total**: Toujours 7 modules (pas de changement visuel)

**Étape 3**: Ajout ISO 45001

- ✅ Les 6 modules communs (toujours là)
- **Total**: Toujours 7 modules

**À noter**:

- Les modules communs ne sont **pas dupliqués** visuellement
- Les sous-modules et sections **s'agrègent** de toutes les normes actives

---

### 12. Que signifie "is_trial" et "is_headquarters" dans mes données ?

**is_trial**:

- `true` = Abonnement en période d'essai gratuite
- `false` = Abonnement payant normal

**is_headquarters** (ou `is_headquarter`):

- `true` = Site est le siège social
- `false` = Site secondaire

**Pourquoi c'est important ?**

- La période d'essai **exige** `is_headquarters = true`
- Un site secondaire **ne peut pas** activer le trial

---

### 13. Les notifications in-app sont-elles différentes des emails ?

**Oui**, ce sont deux canaux complémentaires.

| Canal      | Fréquence            | Avantages                               |
| ---------- | -------------------- | --------------------------------------- |
| **Email**  | Alertes 14/7/3 jours | Accessible hors connexion, archivable   |
| **In-app** | Temps réel           | Badge rouge, historique, action directe |

**Recommandation**: Utilisez les deux pour ne rien manquer.

**Centre de notifications** (in-app):

- Icône 🔔 en haut à droite
- Badge rouge = non lu
- Clic = action immédiate (renouvellement)

---

### 14. Comment annuler mon abonnement ?

**Il n'y a pas d'"annulation" au sens traditionnel**.

**Options**:

1. **Ne pas renouveler**: Laissez simplement expirer l'abonnement
   - Accès bloqué après expiration
   - Données conservées 90 jours
2. **Contacter le support**: Pour suppression complète du compte
   - Email: support@BestQHSE.com
   - Suppression irréversible des données

**Remboursement**: Consultez les CGV ou contactez le support.

---

### 15. J'ai renouvelé mais l'accès est toujours bloqué, pourquoi ?

**Causes possibles**:

1. **Paiement en attente**
   - Vérifiez votre email de confirmation
   - Vérifiez le Dashboard → Statut doit être "actif"

2. **Une autre norme a expiré**
   - Vérifiez que **toutes** vos normes sont actives
   - Renouvelez également les autres

3. **Cache navigateur**
   - Déconnexion/Reconnexion
   - Videz le cache (Ctrl+F5)

4. **Problème technique**
   - Contactez le support immédiatement
   - Fournissez votre référence d'abonnement

---

## 💡 Conseils pratiques

### Bonne pratique #1: Anticipez les renouvellements

Renouvelez **au moins 7 jours avant** l'expiration pour éviter tout stress.

### Bonne pratique #2: Centralisez vos dates

Si vous avez plusieurs normes, essayez de synchroniser les dates d'expiration pour un renouvellement groupé.

### Bonne pratique #3: Activez les notifications

Gardez les emails et notifications in-app activés pour les alertes critiques.

### Bonne pratique #4: Utilisez le Dashboard

Consultez régulièrement le Dashboard pour une vue d'ensemble de tous vos abonnements.

### Bonne pratique #5: Documentez vos souscriptions

Gardez une trace écrite (fichier Excel) de vos abonnements et dates pour votre comptabilité.

---

## 📞 Besoin d'aide supplémentaire ?

**Support technique**:

- 📧 Email: support@BestQHSE.com
- 📞 Téléphone: +33 1 23 45 67 89
- 💬 Chat: Disponible dans l'application (9h-18h)

**Documentation**:

- [Guide complet des abonnements](./GUIDE_UTILISATEUR_ABONNEMENTS.md)
- [Guide installation](../GUIDE_INSTALLATION.md)
- [README principal](../README.md)

---

_FAQ mise à jour: Février 2026_
