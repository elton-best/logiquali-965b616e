<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur BestQHSE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #1976d2;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }

        .credentials {
            background-color: #fff;
            border: 2px solid #1976d2;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }

        .credentials strong {
            color: #1976d2;
        }

        .password {
            font-size: 18px;
            font-weight: bold;
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 10px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }

        .button {
            display: inline-block;
            background-color: #1976d2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🎉 Bienvenue sur BestQHSE</h1>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $user->first_name ?? $user->name }}</strong>,</p>

        <p>Votre compte collaborateur a été créé avec succès sur la plateforme BestQHSE par votre administrateur
            d'entreprise.</p>

        <div class="credentials">
            <h3>Vos identifiants de connexion :</h3>

            <p>
                <strong>Email :</strong> {{ $user->email }}<br>
                <strong>Nom d'utilisateur :</strong> {{ $user->username }}
            </p>

            <p><strong>Mot de passe temporaire :</strong></p>
            <div class="password">{{ $password }}</div>

            <p style="margin-top: 15px; color: #d32f2f;">
                ⚠️ <strong>Important :</strong> Pour votre sécurité, nous vous recommandons de changer ce mot de passe
                lors de votre première connexion.
            </p>
        </div>

        <p>Vous pouvez dès maintenant vous connecter à la plateforme en utilisant ces identifiants.</p>

        <center>
            <a href="{{ config('app.frontend_url') }}/auth/login" class="button">
                Se connecter à BestQHSE
            </a>
        </center>

        <p style="margin-top: 30px;">
            Si vous rencontrez des difficultés pour vous connecter, n'hésitez pas à contacter votre administrateur.
        </p>

        <p>Cordialement,<br>
            <strong>L'équipe BestQHSE</strong>
        </p>
    </div>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} BestQHSE - Tous droits réservés</p>
    </div>
</body>

</html>