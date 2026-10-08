<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }
        .content {
            padding: 30px 20px;
        }
        .actor-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .actor-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .actor-box .name {
            font-size: 16px;
            color: #333;
            margin-top: 5px;
            font-weight: 500;
        }
        .document-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .document-card h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .document-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .doc-field {
            display: flex;
            flex-direction: column;
        }
        .doc-field label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .doc-field value {
            font-size: 14px;
            color: #333;
            font-weight: 500;
            word-break: break-word;
        }
        .message-box {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #2e7d32;
        }
        .message-box strong {
            display: block;
            margin-bottom: 5px;
            font-size: 15px;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            transition: opacity 0.3s;
        }
        .cta-button:hover {
            opacity: 0.9;
        }
        .timestamp {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            background: #4caf50;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="icon">✓</div>
            <h1>Vérification Complétée</h1>
            <p>Le document a passé l'étape de vérification avec succès</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Actor Info -->
            <div class="actor-box">
                <div class="label">Vérificateur</div>
                <div class="name">{{ $verifierName ?? 'Vérificateur' }}</div>
                <div class="timestamp">{{ now()->format('d/m/Y H:i') }}</div>
            </div>

            <!-- Success Message -->
            <div class="message-box">
                <strong>✓ Vérification Réussie</strong>
                Le document a été validé et est passé à l'étape suivante (approbation).
            </div>

            <!-- Document Card -->
            <div class="document-card">
                <h3>📄 Informations du Document</h3>
                <div class="document-info">
                    <div class="doc-field">
                        <label>Code</label>
                        <value>{{ $document->code ?? 'N/A' }}</value>
                    </div>
                    <div class="doc-field">
                        <label>Version</label>
                        <value>{{ $document->version ?? '1.0' }}</value>
                    </div>
                    <div class="doc-field">
                        <label>Type</label>
                        <value>{{ $document->type ?? 'N/A' }}</value>
                    </div>
                    <div class="doc-field">
                        <label>Statut</label>
                        <value>
                            <span class="status-badge">En attente d'approbation</span>
                        </value>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div style="background: #fef3e2; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; border-radius: 4px; font-size: 14px;">
                <strong style="display: block; color: #e65100; margin-bottom: 5px;">⏭ Prochaine étape</strong>
                <p style="margin: 0; color: #f57c00;">
                    Un approbateur doit désormais valider ce document avant sa publication finale.
                </p>
            </div>

            <!-- CTA -->
            <div class="cta-section">
                <a href="{{ $actionUrl ?? '#' }}" class="cta-button">
                    Consulter le Document
                </a>
            </div>

            <!-- Footer message -->
            <p style="font-size: 13px; color: #666; text-align: center; margin: 20px 0;">
                Vous recevez ce message car vous avez vérifié un document sur LOGIQUALI.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 10px 0;">
                <strong>LOGIQUALI</strong> - Gestion des Normes et Documents
            </p>
            <p style="margin: 0;">
                Pour toute question, contactez <a href="mailto:support@logiquali.com">support@logiquali.com</a>
            </p>
        </div>
    </div>
</body>
</html>
