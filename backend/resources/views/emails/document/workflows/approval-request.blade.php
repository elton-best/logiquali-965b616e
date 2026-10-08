<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande d'Approbation</title>
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
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
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
            background-color: #ffebee;
            border-left: 4px solid #d32f2f;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .actor-info strong {
            color: #b71c1c;
        }

        .document-card {
            background-color: #fafafa;
            border: 2px solid #d32f2f;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .document-card h3 {
            color: #b71c1c;
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
            background-color: #ffebee;
            border-left: 4px solid #d32f2f;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .alert-box strong {
            color: #b71c1c;
        }

        .cta-section {
            text-align: center;
            margin: 30px 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
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
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">✏️</div>
            <h1>Demande d'Approbation</h1>
            <p>Votre approbation est requise</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                <strong>Bonjour {{ $userName }},</strong>
            </div>

            <div class="actor-info">
                👤 <strong>{{ $actorName }}</strong> vous demande d'approuver le document ci-dessous.
            </div>

            <!-- Document Card -->
            <div class="document-card">
                <h3>📄 Document à Approuver</h3>
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
                        <span class="info-label">Type</span>
                        <span class="info-value">{{ $documentType }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Créé le</span>
                        <span class="info-value">{{ $createdAt }}</span>
                    </div>
                </div>
            </div>

            <!-- Alert -->
            <div class="alert-box">
                🔴 <strong>Action urgente</strong> - Merci de procéder à l'approbation dans les <strong>meilleurs délais</strong>.
            </div>

            <!-- CTA Button -->
            <div class="cta-section">
                <a href="{{ $documentUrl }}" class="cta-button">✅ Consulter et Approuver</a>
            </div>

            <!-- Help Text -->
            <div class="footer-text">
                <strong>À l'accès du document, vous pourrez :</strong>
                <ul style="margin-left: 20px; margin-top: 8px;">
                    <li>Consulter le PDF en prévisualisation</li>
                    <li>Approuver ou rejeter le document</li>
                    <li>Ajouter des commentaires ou raison de rejet</li>
                </ul>
            </div>

            <div class="divider"></div>

            <div class="footer-text">
                Si vous avez des questions concernant ce document ou cette demande, n'hésitez pas à contacter l'équipe qualité.
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
