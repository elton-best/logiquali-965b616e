<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workflowType === 'approval_request' ? 'Demande d\'approbation' : 'Document publié' }}</title>
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
                {{ $workflowType === 'approval_request' ? '#ff9800' : '#4caf50' }}
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

        .document-card {
            background-color: #fff;
            border: 2px solid
                {{ $workflowType === 'approval_request' ? '#ff9800' : '#4caf50' }}
            ;
            border-radius: 5px;
            padding: 20px;
            margin: 20px 0;
        }

        .document-card h3 {
            margin-top: 0;
            color:
                {{ $workflowType === 'approval_request' ? '#e65100' : '#2e7d32' }}
            ;
        }

        .document-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        .document-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .document-info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #333;
            text-align: right;
        }

        .alert {
            background-color:
                {{ $workflowType === 'approval_request' ? '#fff3e0' : '#e8f5e9' }}
            ;
            border-left: 4px solid
                {{ $workflowType === 'approval_request' ? '#ff9800' : '#4caf50' }}
            ;
            padding: 15px;
            margin: 20px 0;
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
                {{ $workflowType === 'approval_request' ? '#ff9800' : '#4caf50' }}
            ;
            color: white;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
            font-size: 16px;
        }

        .button:hover {
            opacity: 0.9;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .actor-info {
            background-color: #e3f2fd;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="icon">{{ $workflowType === 'approval_request' ? '📋' : '📄' }}</div>
        <h1>{{ $workflowType === 'approval_request' ? 'Demande d\'approbation' : 'Nouveau document publié' }}</h1>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $userName ?? 'Utilisateur' }}</strong>,</p>

        @if($workflowType === 'approval_request')
            @if(isset($actorName))
                <div class="actor-info">
                    👤 <strong>{{ $actorName }}</strong> vous demande d'approuver un document.
                </div>
            @else
                <p>Une demande d'approbation de document nécessite votre attention.</p>
            @endif

            <div class="document-card">
                <h3>📄 Document à approuver</h3>

                <div class="document-info">
                    <div class="document-info-item">
                        <span class="info-label">Titre :</span>
                        <span class="info-value"><strong>{{ $documentTitle }}</strong></span>
                    </div>
                    <div class="document-info-item">
                        <span class="info-label">Code :</span>
                        <span class="info-value">{{ $documentCode }}</span>
                    </div>
                    <div class="document-info-item">
                        <span class="info-label">Version :</span>
                        <span class="info-value">{{ $documentVersion }}</span>
                    </div>
                    @if(isset($documentType))
                        <div class="document-info-item">
                            <span class="info-label">Type :</span>
                            <span class="info-value">{{ $documentType }}</span>
                        </div>
                    @endif
                </div>

                <div class="alert">
                    ⏱️ Merci de procéder à la validation dans les <strong>meilleurs délais</strong>.
                </div>

                <center>
                    <a href="{{ $approveUrl ?? $documentUrl ?? '#' }}" class="button">
                        ✅ Consulter et approuver
                    </a>
                </center>
            </div>
        @else
            @if(isset($actorName))
                <div class="actor-info">
                    👤 <strong>{{ $actorName }}</strong> a publié un nouveau document.
                </div>
            @else
                <p>Un nouveau document a été publié et est maintenant disponible.</p>
            @endif

            <div class="document-card">
                <h3>📄 Document publié</h3>

                <div class="document-info">
                    <div class="document-info-item">
                        <span class="info-label">Titre :</span>
                        <span class="info-value"><strong>{{ $documentTitle }}</strong></span>
                    </div>
                    <div class="document-info-item">
                        <span class="info-label">Code :</span>
                        <span class="info-value">{{ $documentCode }}</span>
                    </div>
                    <div class="document-info-item">
                        <span class="info-label">Version :</span>
                        <span class="info-value">{{ $documentVersion }}</span>
                    </div>
                    @if(isset($publishedDate))
                        <div class="document-info-item">
                            <span class="info-label">Date de publication :</span>
                            <span class="info-value">{{ $publishedDate }}</span>
                        </div>
                    @endif
                </div>

                <div class="alert">
                    ✅ Ce document est désormais accessible dans votre <strong>espace documentaire</strong>.
                </div>

                <center>
                    <a href="{{ $documentUrl ?? '#' }}" class="button">
                        👁️ Consulter le document
                    </a>
                </center>
            </div>
        @endif

        <p style="margin-top: 30px;">
            Si vous avez des questions concernant ce document, n'hésitez pas à contacter l'équipe qualité.
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