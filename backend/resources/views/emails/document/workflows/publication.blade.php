<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Document Publié</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .header-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.95;
        }

        .content {
            padding: 30px;
        }

        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #333;
        }

        .actor-info {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .actor-info strong {
            color: #2e7d32;
        }

        .document-card {
            background-color: #fafafa;
            border: 2px solid #4caf50;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .document-card h3 {
            color: #2e7d32;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .document-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 4px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #efefef;
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
            text-align: right;
            word-break: break-word;
        }

        .alert-box {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .alert-box strong {
            color: #2e7d32;
        }

        .cta-section {
            text-align: center;
            margin: 30px 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            transition: opacity 0.3s;
            border: none;
            cursor: pointer;
        }

        .cta-button:hover {
            opacity: 0.9;
        }

        .footer-text {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
            margin: 20px 0;
        }

        .divider {
            height: 1px;
            background-color: #efefef;
            margin: 20px 0;
        }

        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #efefef;
        }

        .footer p {
            margin: 5px 0;
        }

        .logo-text {
            font-weight: 600;
            color: #4caf50;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">✅</div>
            <h1>Document Publié</h1>
            <p>Un nouveau document est maintenant disponible</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                <strong>Bonjour {{ $userName }},</strong>
            </div>

            <div class="actor-info">
                👤 <strong>{{ $actorName }}</strong> a publié un nouveau document qui est maintenant disponible.
            </div>

            <!-- Document Card -->
            <div class="document-card">
                <h3>📄 Document Disponible</h3>
                <div class="document-info">
                    <div class="info-row">
                        <span class="info-label">Titre</span>
                        <span class="info-value"><strong>{{ $documentTitle }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Code</span>
                        <span class="info-value">{{ $documentCode }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Version</span>
                        <span class="info-value">{{ $documentVersion }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Publié le</span>
                        <span class="info-value">{{ $publishedAt }}</span>
                    </div>
                </div>
            </div>

            <!-- Alert -->
            <div class="alert-box">
                ✅ Ce document est désormais <strong>accessible dans votre espace documentaire</strong> et est en vigueur avec effet immédiat.
            </div>

            <!-- CTA Button -->
            <div class="cta-section">
                <a href="{{ $documentUrl }}" class="cta-button">👁️ Consulter le Document</a>
            </div>

            <!-- Help Text -->
            <div class="footer-text">
                <strong>À l'accès du document, vous pourrez :</strong>
                <ul style="margin-left: 20px; margin-top: 8px;">
                    <li>Consulter le PDF en prévisualisation</li>
                    <li>Télécharger ou imprimer le document</li>
                    <li>Accéder à l'historique de version</li>
                </ul>
            </div>

            <div class="divider"></div>

            <div class="footer-text">
                Pour toute question ou remarque concernant ce document, vous pouvez contacter l'équipe qualité.
            </div>

            <p style="margin-top: 15px;">
                Cordialement,<br>
                <span class="logo-text">L'équipe LOGIQUALI</span>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} LOGIQUALI - Système de Gestion Documentaire. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
