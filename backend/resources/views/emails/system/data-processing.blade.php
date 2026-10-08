<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traitement de données terminé</title>
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
            background-color:
                {{ $success ? '#4caf50' : '#f44336' }}
            ;
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

        .info-box {
            background-color: #fff;
            border: 2px solid
                {{ $success ? '#4caf50' : '#f44336' }}
            ;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }

        .info-box h3 {
            margin-top: 0;
            color:
                {{ $success ? '#2e7d32' : '#c62828' }}
            ;
        }

        .stats {
            background-color:
                {{ $success ? '#e8f5e9' : '#ffebee' }}
            ;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .stats-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .stats-label {
            font-weight: bold;
        }

        .error-list {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin: 15px 0;
        }

        .error-list ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .error-list li {
            color: #c62828;
            margin: 5px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }

        .button {
            display: inline-block;
            background-color:
                {{ $success ? '#4caf50' : '#1976d2' }}
            ;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .button:hover {
            opacity: 0.9;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="icon">{{ $success ? '✅' : '❌' }}</div>
        <h1>{{ $processingType === 'export' ? 'Export' : 'Import' }} {{ $success ? 'terminé' : 'échoué' }}</h1>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $userName ?? 'Utilisateur' }}</strong>,</p>

        @if($success)
            <p>Votre {{ $processingType === 'export' ? 'export' : 'import' }} <strong>"{{ $fileName }}"</strong> s'est
                terminé avec succès.</p>

            <div class="info-box">
                <h3>📊 Résumé du traitement</h3>

                <div class="stats">
                    <div class="stats-item">
                        <span class="stats-label">Fichier :</span>
                        <span>{{ $fileName }}</span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Nombre d'éléments traités :</span>
                        <span><strong>{{ $recordCount }}</strong></span>
                    </div>
                    <div class="stats-item">
                        <span class="stats-label">Statut :</span>
                        <span style="color: #2e7d32; font-weight: bold;">✓ Succès</span>
                    </div>
                </div>

                @if($processingType === 'export')
                    <center>
                        <a href="{{ $fileUrl }}" class="button">
                            📥 Télécharger le fichier
                        </a>
                    </center>

                    <p style="margin-top: 15px; text-align: center; color: #666; font-size: 14px;">
                        ⏱️ Ce fichier sera disponible pendant <strong>7 jours</strong>
                    </p>
                @else
                    <p style="text-align: center; color: #2e7d32; margin-top: 15px;">
                        ✅ Les données ont été importées avec succès dans votre système.
                    </p>
                @endif
            </div>
        @else
            <p style="color: #c62828;">Votre {{ $processingType === 'export' ? 'export' : 'import' }}
                <strong>"{{ $fileName }}"</strong> a échoué.</p>

            <div class="info-box">
                <h3>❌ Détails de l'erreur</h3>

                @if(isset($errors) && count($errors) > 0)
                    <div class="error-list">
                        <p><strong>Erreurs rencontrées :</strong></p>
                        <ul>
                            @foreach(array_slice($errors, 0, 5) as $error)
                                <li>{{ $error }}</li>
                            @endforeach

                            @if(count($errors) > 5)
                                <li><em>... et {{ count($errors) - 5 }} autre(s) erreur(s)</em></li>
                            @endif
                        </ul>
                    </div>
                @else
                    <p style="color: #c62828;">Une erreur technique s'est produite lors du traitement.</p>
                @endif

                <p>Veuillez corriger les erreurs mentionnées ci-dessus et réessayer.</p>
            </div>
        @endif

        <p style="margin-top: 30px;">
            Si vous avez besoin d'aide, n'hésitez pas à contacter notre support technique.
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