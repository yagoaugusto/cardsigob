<?php
require_once __DIR__ . '/../src/auth/functions.php';

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
  if ($login === '' || $senha === '') {
    $erro = 'Informe e-mail e senha.';
    } else {
    if (auth_login($login, $senha)) {
      $dest = isset($_GET['redirect']) && $_GET['redirect'] !== '' ? $_GET['redirect'] : igob_url('index.php');
      header('Location: ' . $dest);
      exit;
    } else {
            $erro = 'Credenciais inválidas.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - IGOB Analytics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; display:flex; align-items:center; justify-content:center; background: radial-gradient(circle at 30% 30%, #1f2937, #0f172a); }
    .login-card { backdrop-filter: blur(12px); background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); }
  .gradient-text { background: linear-gradient(90deg,#38bdf8,#818cf8,#c084fc); -webkit-background-clip: text; background-clip: text; color:transparent; }
    .form-control, .form-control:focus { background: rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.2); color:#fff; }
    .btn-primary { background:linear-gradient(90deg,#2563eb,#7c3aed); border:none; }
    .btn-primary:hover { filter:brightness(1.1); }
    .logo-circle { width:58px; height:58px; background:linear-gradient(135deg,#6366f1,#0ea5e9); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:1.1rem; letter-spacing:.5px; }
    footer { position:fixed; bottom:8px; font-size:.75rem; opacity:.6; }
  </style>
</head>
<body>
  <main class="container" style="max-width:420px;">
    <div class="card shadow-lg p-4 rounded-4 login-card">
      <div class="text-center mb-4">
        <div class="logo-circle mx-auto mb-3">IG</div>
        <h1 class="h4 fw-semibold gradient-text m-0">IGOB Analytics</h1>
        <p class="text-secondary small mt-1">Acesse para continuar</p>
      </div>
      <?php if(isset($_GET['timeout'])): ?>
        <div class="alert alert-warning py-2">Sessão expirada. Faça login novamente.</div>
      <?php endif; ?>
      <?php if($erro): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>
      <form method="post" class="needs-validation" novalidate>
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control form-control-lg" required autofocus>
        </div>
        <div class="mb-2">
          <label class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control form-control-lg" required>
        </div>
        <div class="d-grid mt-4">
          <button class="btn btn-primary btn-lg rounded-3" type="submit">Entrar</button>
        </div>
      </form>
    </div>
  </main>
  <footer class="w-100 text-center text-white-50">&copy; <?= date('Y') ?> IGOB. Todos os direitos reservados.</footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
