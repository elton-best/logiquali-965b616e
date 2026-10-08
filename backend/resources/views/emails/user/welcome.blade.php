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

        .warning {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 12px;
            margin: 15px 0;
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

        .button:hover {
            background-color: #1565c0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🎉 {{ $greeting ?? 'Bienvenue sur BestQHSE' }}</h1>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $userName ?? 'Utilisateur' }}</strong>,</p>

        <p>{{ $introMessage ?? 'Votre compte a été créé avec succès sur la plateforme BestQHSE.' }}</p>

        <div class="credentials">
            <h3>Vos identifiants de connexion :</h3>

            <p>
                <strong>Email :</strong> {{ $userEmail }}<br>
            </p>

            @if($accessMode === 'temporary_password' && isset($temporaryPassword))
                <p><strong>Mot de passe temporaire :</strong></p>
                <div class="password">{{ $temporaryPassword }}</div>

                <div class="warning">
                    ⚠️ <strong>Important :</strong> Pour votre sécurité, vous devrez changer ce mot de passe lors de votre
                    première connexion.
                </div>

                <center>
                    <a href="{{ $loginUrl ?? config('app.frontend_url') . '/auth/login' }}" class="button">
                        Se connecter à BestQHSE
                    </a>
                </center>
            @else
                <p>Pour définir votre mot de passe et activer votre compte, cliquez sur le bouton ci-dessous :</p>

                <center>
                    <a href="{{ $setPasswordUrl ?? '#' }}" class="button">
                        Définir mon mot de passe
                    </a>
                </center>

                <div class="warning">
                    ⚠️ Ce lien est valable pendant <strong>48 heures</strong>. Passé ce délai, vous devrez demander un
                    nouveau lien.
                </div>
            @endif
        </div>

        <p>
            @if($userType === 'collaborator')
                Vous avez maintenant accès à tous les outils de gestion qualité de votre entreprise.
            @else
                Vous pouvez désormais accéder à votre espace client et suivre vos réclamations.
            @endif
        </p>

        <p style="margin-top: 30px;">
            Si vous avez des questions, n'hésitez pas à contacter notre équipe.
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