# Compte démo entreprise Laravel

Le compte démo est créé dans la base PHP/Laravel, jamais dans le frontend.

## Provisionnement

Depuis le dossier `backend` :

```bash
cp .env.example .env
php artisan migrate
php artisan demo:provision
```

La commande est idempotente : elle crée ou met à jour l'entreprise, son site principal et son administrateur.

Les valeurs par défaut sont configurables dans `backend/.env` :

```dotenv
DEMO_COMPANY_EMAIL=demo.entreprise@logiquali.test
DEMO_COMPANY_USERNAME=demo.entreprise
DEMO_COMPANY_PASSWORD=DemoLogiQuali2026!
DEMO_ENTERPRISE_EMAIL=demo.entreprise@logiquali.test
```

La commande affiche les identifiants effectivement provisionnés. Ils ne doivent pas être utilisés en production.

## MFA en local

L'authentification Laravel impose la MFA. Pour un environnement local uniquement, le backend peut renvoyer le code MFA dans la réponse de connexion :

```dotenv
MFA_EXPOSE_OTP_FOR_E2E=true
```

Ne pas activer cette option en production. Le bouton **Accéder au compte démo entreprise** du frontend est disponible uniquement en mode Vite développement. Il appelle directement l'API Laravel et ne crée aucune donnée locale côté navigateur. En développement local, lorsque `MFA_EXPOSE_OTP_FOR_E2E=true`, le code MFA est vérifié automatiquement.
