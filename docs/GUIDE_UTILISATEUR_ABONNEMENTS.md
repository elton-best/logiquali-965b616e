# 📚 Guide Utilisateur - Gestion des Abonnements

## Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Première souscription](#première-souscription)
3. [Période d'essai](#période-dessai)
4. [Ajouter une norme](#ajouter-une-norme)
5. [Renouvellement](#renouvellement)
6. [Dashboard](#dashboard)
7. [Alertes et notifications](#alertes-et-notifications)
8. [FAQ](#faq)

---

## Vue d'ensemble

Le système d'abonnements BestQHSE vous permet de souscrire à une ou plusieurs normes ISO pour votre entreprise. Chaque norme active vous donne accès à des modules, sous-modules et sections spécifiques.

### Principes clés

✅ **Multi-normes**: Vous pouvez activer plusieurs normes simultanément  
✅ **Continuité**: Les renouvellements commencent automatiquement après l'expiration  
✅ **Flexibilité**: Ajoutez des normes à tout moment  
🚫 **Pas de changement**: Vous ne pouvez pas changer de norme, seulement en ajouter

---

## Première souscription

### Étapes

1. **Accéder aux offres**
   - Menu → Abonnements → Offres disponibles
   - Consultez les différentes offres et leurs normes associées

2. **Choisir une offre**
   - Sélectionnez l'offre correspondant à la norme souhaitée
   - Vérifiez les modules inclus
   - Cliquez sur "Souscrire"

3. **Sélectionner le site**
   - Choisissez le site concerné (siège social recommandé pour le premier abonnement)
   - Pour la période d'essai, seul le siège social est éligible

4. **Validation**
   - Vérifiez les informations
   - Confirmez la souscription
   - Accès immédiat aux modules !

---

## Période d'essai

### Éligibilité

La période d'essai est une fonctionnalité exclusive qui permet de tester la plateforme gratuitement.

**Conditions**:

- ✅ Disponible **uniquement pour le siège social**
- ✅ Utilisable **une seule fois par entreprise**
- ✅ Durée: **3 mois gratuits**
- 🚫 Non disponible pour les sites secondaires

### Activation

1. Lors de la souscription, cochez "Activer la période d'essai"
2. Sélectionnez obligatoirement le **siège social**
3. Validez

> ⚠️ **Important**: Une fois la période d'essai utilisée, elle ne peut plus être activée, même pour une autre norme.

---

## Ajouter une norme

### Principe

Vous pouvez cumuler plusieurs normes actives simultanément. Chaque nouvelle norme ajoute ses modules spécifiques à votre accès.

### Exemple concret

**Situation actuelle**:

- ISO 9001 active → Accès aux modules communs + module "Réalisation"

**Ajout ISO 14001**:

- ISO 9001 + ISO 14001 actives
- ✅ Tous les modules communs (Contexte, Leadership, etc.)
- ✅ Module Réalisation (ISO 9001)
- ✅ Modules spécifiques ISO 14001

### Procédure

1. Menu → Abonnements → Ajouter une norme
2. Sélectionnez une nouvelle offre (différente de vos normes actives)
3. Choisissez le site
4. Validez et payez

> 💡 **Astuce**: Les modules communs ne sont pas facturés en double. Vous payez uniquement pour les nouveaux modules spécifiques.

### Restrictions

🚫 **Impossible de**:

- Souscrire deux fois à la même norme (validation automatique)
- Changer de norme (vous devez garder la première et en ajouter)

---

## Renouvellement

### Fonctionnement

Les renouvellements assurent la continuité de vos abonnements existants.

**Caractéristiques**:

- ✅ Le nouvel abonnement commence **automatiquement après l'expiration** de l'actuel
- ✅ Pas d'interruption de service si renouvelé à temps
- ✅ Vous pouvez renouveler avant expiration (recommandé)

### Exemple de continuité

**Abonnement actuel**:

- Début: 01/01/2026
- Expiration: 31/03/2026

**Renouvellement le 15/03/2026** (16 jours avant expiration):

- Nouvel abonnement:
  - Début: **01/04/2026** (jour après expiration)
  - Expiration: 30/06/2026 (3 mois ajoutés)

### Renouvellement en retard

Si vous renouvelez après expiration:

**Arriérés calculés**:

- **Jusqu'à 15 jours**: Pas d'arriérés
- **Plus de 15 jours**: 1 mois minimum d'arriérés
- Formule: `ceil(jours_retard / 30) * prix_mensuel`

**Exemple**:

- Expiration: 31/03/2026
- Renouvellement: 20/04/2026 (20 jours de retard)
- Arriérés: 1 mois à payer + nouveau renouvellement

### Procédure

1. **Depuis le Dashboard**:
   - Cliquez sur "Renouveler" sur la carte de l'abonnement
   - Choisissez la durée (1, 3, 6, 12 mois)
   - Validez

2. **Depuis les Alertes**:
   - Cliquez sur "Renouveler" dans la notification
   - Suivez les étapes

---

## Dashboard

Le dashboard est votre centre de contrôle pour tous vos abonnements.

### Accès

Menu → Abonnements → Tableau de bord

### Vue d'ensemble

**Statistiques**:

- 📊 Nombre d'abonnements actifs
- 📜 Nombre de normes actives
- 📁 Modules accessibles
- ⚠️ Alertes urgentes

**Cartes d'abonnements actifs**:

- Norme(s) concernée(s)
- Jours restants (barre de progression)
- Nombre de modules
- Bouton renouvellement rapide

**Timeline**:

- Visualisation des expirations sur 6 mois
- Anticipation des renouvellements

**Actions rapides**:

- ➕ Ajouter une norme
- 🔄 Renouveler un abonnement
- ⭐ Activer période d'essai (si éligible)

---

## Alertes et notifications

### Système d'alertes automatiques

Vous recevez des alertes **par email et dans l'application** aux moments suivants:

| Échéance     | Type d'alerte | Action recommandée          |
| ------------ | ------------- | --------------------------- |
| **14 jours** | 📅 Rappel     | Planifier le renouvellement |
| **7 jours**  | ⚠️ Attention  | Renouveler rapidement       |
| **3 jours**  | 🚨 Urgent     | Renouveler immédiatement    |

### Centre de notifications

**Accès**: Icône 🔔 en haut à droite

**Fonctionnalités**:

- Badge rouge = notifications non lues
- Clic sur notification = accès direct à l'action
- "Marquer tout comme lu"
- Historique complet des notifications

### Blocage d'accès

⚠️ **IMPORTANT**: Si **une seule norme expire**, l'accès à **toute la plateforme** est bloqué.

**Pourquoi ?**

- Assure la cohérence de vos données
- Évite les accès partiels problématiques
- Encourage le renouvellement proactif

**Solution**:

- Renouveler immédiatement la norme expirée
- L'accès sera rétabli dès validation du paiement

---

## FAQ

### Général

**Q: Combien de normes puis-je avoir en même temps ?**  
R: Illimité. Vous pouvez activer autant de normes que nécessaire pour votre activité.

**Q: Les dates d'expiration de mes normes doivent-elles être identiques ?**  
R: Non, chaque abonnement a sa propre date d'expiration indépendante.

**Q: Puis-je annuler un abonnement ?**  
R: Vous pouvez ne pas renouveler, mais l'abonnement reste actif jusqu'à expiration.

### Période d'essai

**Q: La période d'essai s'applique-t-elle à toutes les normes ?**  
R: Non, elle est globale pour l'entreprise. Une fois utilisée, elle ne peut pas être réactivée.

**Q: Puis-je activer la période d'essai sur un site secondaire ?**  
R: Non, uniquement sur le siège social.

**Q: Que se passe-t-il après les 3 mois d'essai ?**  
R: Vous devez souscrire à une offre payante pour continuer. Vous recevrez des alertes avant expiration.

### Renouvellement

**Q: Puis-je renouveler plusieurs mois à l'avance ?**  
R: Oui, le nouvel abonnement commencera toujours après l'expiration de l'actuel.

**Q: Que se passe-t-il si j'oublie de renouveler ?**  
R: Après expiration:

- Accès bloqué immédiatement
- Possibilité de renouveler avec calcul d'arriérés après 15 jours
- Données conservées

**Q: Les arriérés sont-ils obligatoires ?**  
R: Oui, si vous renouvelez plus de 15 jours après expiration. C'est automatique.

### Multi-normes

**Q: Puis-je passer d'ISO 9001 à ISO 14001 ?**  
R: Non, vous devez garder ISO 9001 et ajouter ISO 14001. Vous aurez alors les deux.

**Q: Les modules communs sont-ils facturés deux fois ?**  
R: Non, vous payez uniquement pour les nouveaux modules spécifiques de la norme ajoutée.

**Q: Si une norme expire et pas l'autre, suis-je bloqué ?**  
R: Oui, toutes les normes doivent être actives pour accéder à la plateforme.

### Technique

**Q: Comment savoir quels modules sont accessibles ?**  
R: Consultez le Dashboard ou la sidebar. Seuls les modules de vos normes actives s'affichent.

**Q: Les données sont-elles supprimées après expiration ?**  
R: Non, elles sont conservées. L'accès est simplement bloqué jusqu'au renouvellement.

**Q: Puis-je changer le site d'un abonnement ?**  
R: Non, un abonnement est lié à un site spécifique.

---

## Support

**Besoin d'aide ?**

- 📧 Email: support@BestQHSE.com
- 📞 Téléphone: +33 1 23 45 67 89
- 💬 Chat: Disponible dans l'application

---

_Dernière mise à jour: Février 2026_
