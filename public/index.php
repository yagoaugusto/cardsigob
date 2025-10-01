<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_require_login();
$user = auth_current_user();
// Helper simples de escape HTML (evita erro se não houver definição global)
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bem-vindo - IGOB Analytics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { min-height:100vh; background: radial-gradient(circle at 10% 20%, #172033, #0a0f1c); }
    .nav-glass { backdrop-filter: blur(14px); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); }
    .card-glass { position:relative; overflow:hidden; backdrop-filter: blur(10px); background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); }
    .card-glass:before { content:""; position:absolute; inset:0; background:radial-gradient(circle at 85% 15%,rgba(255,255,255,0.18),transparent 55%); opacity:.6; pointer-events:none; }
    .gradient-text { background: linear-gradient(90deg,#38bdf8,#818cf8,#c084fc); -webkit-background-clip:text; background-clip:text; color:transparent; }
    .quick-card { transition:.35s cubic-bezier(.4,.2,.2,1); cursor:pointer; border-radius:1.2rem !important; }
    .quick-card:hover { transform:translateY(-4px) scale(1.015); border-color:rgba(255,255,255,0.28); box-shadow:0 8px 26px -6px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.08); }
    .icon-wrap { width:54px; height:54px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.55rem; margin-bottom:10px; position:relative; z-index:1; }
    .icon-gradient-1 { background:linear-gradient(135deg,#2563eb,#7c3aed); color:#fff; }
    .icon-gradient-2 { background:linear-gradient(135deg,#0ea5e9,#6366f1); color:#fff; }
    .icon-gradient-3 { background:linear-gradient(135deg,#6366f1,#a855f7); color:#fff; }
    .icon-gradient-4 { background:linear-gradient(135deg,#0891b2,#4f46e5); color:#fff; }
    .icon-gradient-5 { background:linear-gradient(135deg,#7c3aed,#db2777); color:#fff; }
    .icon-gradient-6 { background:linear-gradient(135deg,#0ea5e9,#10b981); color:#fff; }
    .icon-gradient-7 { background:linear-gradient(135deg,#475569,#6366f1); color:#fff; }
    h5.fw-semibold { font-size:1.02rem; letter-spacing:.3px; }
    .small.mb-0 { line-height:1.15rem; }
    @media (max-width: 575px){ .icon-wrap { width:50px; height:50px; font-size:1.35rem; } }
  </style>
</head>
<body class="text-light">
<nav class="navbar navbar-expand-lg nav-glass px-3 my-2 rounded-4 container-xxl">
  <a class="navbar-brand fw-semibold gradient-text" href="#">IGOB</a>
  <div class="ms-auto d-flex align-items-center gap-3">
  <span class="small text-white-50">Olá, <?= htmlspecialchars($user['nome'] ?: ($user['email'] ?? 'Usuário')) ?></span>
    <a href="<?= h(igob_url('logout.php')) ?>" class="btn btn-sm btn-outline-light rounded-pill">Sair</a>
  </div>
</nav>
<section class="container-xxl py-5">
  <div class="row g-4 align-items-stretch">
    <div class="col-12">
      <h1 class="display-6 fw-semibold mb-1 gradient-text">Bem-vindo</h1>
      <p class="text-secondary mb-4">Escolha uma das ações rápidas abaixo para começar.</p>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card">
        <div class="icon-wrap icon-gradient-1"><i class="bi bi-collection"></i></div>
        <h5 class="fw-semibold mb-2">Carteira de obras Sintética</h5>
        <p class="text-secondary small mb-0">Visão resumida da carteira.</p>
  <a class="stretched-link" href="<?= h(igob_url('carteira_sintetica.php')) ?>"></a>
      </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card">
        <div class="icon-wrap icon-gradient-2"><i class="bi bi-calendar-check"></i></div>
        <h5 class="fw-semibold mb-2">Carteira de obras Programada</h5>
        <p class="text-secondary small mb-0">Obras com programação vigente.</p>
  <a class="stretched-link" href="<?= h(igob_url('carteira_programadas.php')) ?>"></a>
      </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card" onclick="alert('Em desenvolvimento: Programação de obras');">
        <div class="icon-wrap icon-gradient-3"><i class="bi bi-diagram-3"></i></div>
        <h5 class="fw-semibold mb-2">Programação de obras</h5>
        <p class="text-secondary small mb-0">Detalhamento de programações.</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card" onclick="alert('Em desenvolvimento: Produtividade');">
        <div class="icon-wrap icon-gradient-4"><i class="bi bi-speedometer2"></i></div>
        <h5 class="fw-semibold mb-2">Produtividade</h5>
        <p class="text-secondary small mb-0">Indicadores de produção.</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card">
        <div class="icon-wrap icon-gradient-5"><i class="bi bi-bar-chart-line"></i></div>
        <h5 class="fw-semibold mb-2">Meta x Programado x Executado</h5>
        <p class="text-secondary small mb-0">Acompanhamento comparativo.</p>
  <a class="stretched-link" href="<?= h(igob_url('meta_programado_executado.php')) ?>"></a>
      </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card" onclick="alert('Em desenvolvimento: Viabilidade');">
        <div class="icon-wrap icon-gradient-6"><i class="bi bi-clipboard-data"></i></div>
        <h5 class="fw-semibold mb-2">Viabilidade</h5>
        <p class="text-secondary small mb-0">Análise de viabilidade.</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6">
      <div class="p-4 rounded-4 card-glass h-100 quick-card" onclick="alert('Em desenvolvimento: Fechamento');">
        <div class="icon-wrap icon-gradient-7"><i class="bi bi-clipboard-check"></i></div>
        <h5 class="fw-semibold mb-2">Fechamento</h5>
        <p class="text-secondary small mb-0">Consolidação de períodos.</p>
      </div>
    </div>
  </div>
</section>
<footer class="text-center small text-white-50 pb-3">&copy; <?= date('Y') ?> IGOB.</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
