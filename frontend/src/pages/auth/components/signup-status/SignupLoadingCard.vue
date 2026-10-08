<template>
  <div class="signup-loading">
    <div class="glow" />
    <div class="card">
      <div class="logo">BestQHSE</div>
      <div class="loader-ring">
        <span />
        <span />
        <span />
      </div>
      <h1>Vérification et création de votre compte</h1>
      <p>Nous finalisons l’inscription de votre entreprise. Cela prend quelques secondes.</p>
      <div class="progress">
        <div class="progress-bar" />
      </div>
      <div class="micro">Sécurité, conformité, et performance — en cours d’activation.</div>
    </div>
  </div>
</template>

<style scoped>
.signup-loading {
  min-height: 100vh;
  display: grid;
  place-items: center;
  background:
    radial-gradient(800px 400px at 20% 10%, #e0f2fe 0%, transparent 60%),
    radial-gradient(900px 500px at 90% 20%, #ede9fe 0%, transparent 60%),
    linear-gradient(135deg, #f8fafc 0%, #eef2ff 50%, #f8fafc 100%);
  position: relative;
  overflow: hidden;
  padding: 2rem;
}

.glow {
  position: absolute;
  width: 360px;
  height: 360px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
  filter: blur(2px);
  top: 10%;
  right: 12%;
}

.card {
  width: min(520px, 92vw);
  background: rgba(255, 255, 255, 0.78);
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 24px;
  padding: 2.5rem 2.75rem;
  backdrop-filter: blur(16px);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.15);
  text-align: center;
}

.logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.25rem;
  letter-spacing: 0.02em;
  color: #0f172a;
  margin-bottom: 1.25rem;
}

.loader-ring {
  width: 88px;
  height: 88px;
  margin: 0 auto 1.5rem;
  position: relative;
}

.loader-ring span {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 3px solid transparent;
  border-top-color: #3b82f6;
  animation: spin 1.4s linear infinite;
}

.loader-ring span:nth-child(2) {
  border-top-color: #6366f1;
  animation-duration: 1.9s;
}

.loader-ring span:nth-child(3) {
  border-top-color: #0ea5e9;
  animation-duration: 2.4s;
}

.card h1 {
  font-size: 1.4rem;
  margin: 0 0 0.6rem;
  color: #0f172a;
}

.card p {
  margin: 0 0 1.5rem;
  color: #475569;
  line-height: 1.55;
}

.progress {
  height: 8px;
  border-radius: 999px;
  background: #e2e8f0;
  overflow: hidden;
  margin-bottom: 1rem;
}

.progress-bar {
  height: 100%;
  width: 100%;
  background: linear-gradient(90deg, #3b82f6, #6366f1, #0ea5e9);
  transform-origin: left;
  animation: loading 1.4s ease-in-out infinite;
}

.micro {
  font-size: 0.85rem;
  color: #64748b;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes loading {
  0% { transform: scaleX(0.2); opacity: 0.4; }
  50% { transform: scaleX(0.75); opacity: 0.9; }
  100% { transform: scaleX(0.2); opacity: 0.4; }
}
</style>
