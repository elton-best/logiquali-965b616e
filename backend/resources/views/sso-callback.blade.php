<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion SSO - LOGIQUALI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            color: white;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        p {
            font-size: 16px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Connexion en cours...</h1>
        <p>Vous allez être redirigé automatiquement</p>
    </div>

    <script>
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            const sessionId = urlParams.get('session_id');

            if (!token) {
                console.error('[SSO] No token in URL');
                window.location.href = '/admin/login';
                return;
            }

            // Stocker le token dans localStorage et cookie
            localStorage.setItem('token', token);
            document.cookie = `token=${token}; path=/; max-age=28800; SameSite=Lax`;

            // Initialiser le suivi Supervision
            if (sessionId) {
                localStorage.setItem('sup_session_id', sessionId);
                startSupervisionTracking(parseInt(sessionId));
            }

            // Rediriger vers le dashboard après un court délai
            setTimeout(function() {
                window.location.href = '/admin';
            }, 500);

            // --- Supervision Session Tracking ---
            function startSupervisionTracking(sid) {
                const SUPERVISION_URL = 'http://localhost:8000';
                const PROJECT_CODE = 'BESTQHSE';
                let heartbeatTimer;

                // Envoyer heartbeat immédiatement
                sendHeartbeat();

                // Puis toutes les 30s
                heartbeatTimer = setInterval(sendHeartbeat, 30000);

                // Handler fermeture page
                window.addEventListener('beforeunload', function() {
                    const url = `${SUPERVISION_URL}/api/v1/ingestion/work-sessions/${sid}/end`;
                    navigator.sendBeacon(url, JSON.stringify({ reason: 'page_close' }));
                });

                function sendHeartbeat() {
                    fetch(`${SUPERVISION_URL}/api/v1/ingestion/work-sessions/${sid}/heartbeat`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${PROJECT_CODE}:${Math.floor(Date.now()/1000)}:demo-sig`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ source: 'frontend' })
                    }).catch(function(e) {
                        console.error('[Supervision] Heartbeat failed:', e);
                    });
                }
            }
        })();
    </script>
</body>
</html>
