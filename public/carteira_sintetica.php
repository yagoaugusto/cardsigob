<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_require_login();

global $pdo;

// Usuário atual (para restringir filiais por vínculo)
$__user = function_exists('auth_current_user') ? auth_current_user() : null;

// Helper: sanitize array from GET for IN clause
function bindInClause(PDO $pdo, string $field, array $values, array &$params, string $prefix) {
    $keys = [];
    $i = 0;
    foreach ($values as $v) {
        $key = ":{$prefix}{$i}";
        $keys[] = $key;
        $params[$key] = $v;
        $i++;
    }
    if (!$keys) return null;
    return "$field IN (" . implode(',', $keys) . ")";
}

// Captura filtros
$filiaisSel = isset($_GET['filial']) ? array_values(array_filter((array)$_GET['filial'])) : [];
$tipos = isset($_GET['tipo']) ? array_values(array_filter((array)$_GET['tipo'])) : [];
$situacoes = isset($_GET['situacao']) ? array_values(array_filter((array)$_GET['situacao'])) : [];
$responsaveisSel = isset($_GET['responsavel']) ? array_values(array_filter((array)$_GET['responsavel'])) : [];
$data_ini = isset($_GET['data_ini']) && $_GET['data_ini'] !== '' ? $_GET['data_ini'] : null;
$data_fim = isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null;
// Busca textual
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
// Restringir filiais às vinculadas ao usuário (usuario_filial)
try {
  $uid = (int)($__user['id'] ?? 0);
} catch (Throwable $e) { $uid = 0; }
try {
  $ufUserCol = null; $ufFilialCol = null; $allowedIds = [];
  $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
  if ($db) {
    $ufUserCol = (function() use ($pdo){
      try { $st=$pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='usuario_filial' AND COLUMN_NAME IN ('usuario','id_usuario','usuario_id','user','user_id') LIMIT 1"); $st->execute(); return $st->fetchColumn() ?: null; } catch (Throwable $e){ return null; }
    })();
    $ufFilialCol = (function() use ($pdo){
      try { $st=$pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='usuario_filial' AND COLUMN_NAME IN ('filial','id_filial','filial_id','cd_filial') LIMIT 1"); $st->execute(); return $st->fetchColumn() ?: null; } catch (Throwable $e){ return null; }
    })();
  }
  if ($uid && $ufUserCol && $ufFilialCol) {
    $st = $pdo->prepare("SELECT DISTINCT f.id FROM filial f JOIN usuario_filial uf ON uf.$ufFilialCol = f.id WHERE uf.$ufUserCol = :uid");
    $st->execute([':uid'=>$uid]);
    $allowedIds = array_map('intval', array_column($st->fetchAll(PDO::FETCH_NUM), 0));
  } else {
    $st = $pdo->query('SELECT id FROM filial');
    $allowedIds = array_map('intval', array_column($st->fetchAll(PDO::FETCH_NUM), 0));
  }
  if ($filiaisSel) { $filiaisSel = array_values(array_intersect(array_map('intval',$filiaisSel), $allowedIds)); }
} catch (Throwable $e) { /* silencioso */ }

// Paginação
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 100; // tamanho da página padrão
// Regra: exigir ao menos uma filial selecionada (após interseção)
$mustSelectFilial = count($filiaisSel) === 0;

// Base WHERE
$where = ["1=1"]; // sempre verdadeiro
$params = [];

// Apenas obras ativas, se existir a coluna status
$where[] = "(o.status = 'ATIVO' OR o.status IS NULL)";

if ($filiaisSel) {
  // garantir inteiros
  $filiaisSel = array_map('intval', $filiaisSel);
  $clause = bindInClause($pdo, 'o.filial', $filiaisSel, $params, 'f');
  if ($clause) $where[] = $clause;
}
if ($responsaveisSel) {
  $responsaveisSel = array_map('intval', $responsaveisSel);
  $clause = bindInClause($pdo, 'o.responsavel', $responsaveisSel, $params, 'r');
  if ($clause) $where[] = $clause;
}
if ($data_ini) { $where[] = 'o.data_entrada >= :dini'; $params[':dini'] = $data_ini; }
if ($data_fim) { $where[] = 'o.data_entrada <= :dfim'; $params[':dfim'] = $data_fim; }
if ($search !== '') { $where[] = '(o.codigo LIKE :q OR o.descricao LIKE :q)'; $params[':q'] = '%'.$search.'%'; }

// IN clauses
if ($tipos) {
    $clause = bindInClause($pdo, 'o.tipo', $tipos, $params, 't');
    if ($clause) $where[] = $clause;
}
if ($situacoes) {
    $clause = bindInClause($pdo, 'o.situacao', $situacoes, $params, 's');
    if ($clause) $where[] = $clause;
}

$whereSql = implode(' AND ', $where);

// Total para paginação
if (!$mustSelectFilial) {
  $sqlCount = "SELECT COUNT(*) AS total FROM obra o WHERE $whereSql";
  $stmtCount = $pdo->prepare($sqlCount);
  $stmtCount->execute($params);
  $total = (int)$stmtCount->fetchColumn();
  $pages = max(1, (int)ceil($total / $per_page));
  if ($page > $pages) { $page = $pages; }
  $offset = ($page - 1) * $per_page;
} else {
  $total = 0; $pages = 1; $page = 1; $offset = 0;
}

// KPIs globais para todo o conjunto filtrado (não paginado)
$sqlKpis = "
SELECT 
  COUNT(*) AS qtd,
  SUM(COALESCE(o.valor_servico,0)) AS vlr_servico,
  SUM(COALESCE(o.poste_distribuicao,0) + COALESCE(o.poste_transmissao,0)) AS postes_orc,
  COALESCE(SUM(p.prog),0) AS vlr_programado,
  COALESCE(SUM(s.exec),0) AS vlr_executado
FROM (
  SELECT o.id, o.valor_servico, o.poste_distribuicao, o.poste_transmissao
  FROM obra o
  WHERE $whereSql
) o
LEFT JOIN (
  SELECT obra, SUM(financeiro) AS prog
  FROM programacao
  WHERE tipo <> 'PROGRAMAÇÃO CANCELADA'
  GROUP BY obra
) p ON p.obra = o.id
LEFT JOIN (
  SELECT obra, SUM(quantidade * valor) AS exec
  FROM programacao_servico
  WHERE status = 'EXECUTADO'
  GROUP BY obra
) s ON s.obra = o.id
";
if (!$mustSelectFilial) {
  $stmtK = $pdo->prepare($sqlKpis);
  $stmtK->execute($params);
  $k = $stmtK->fetch(PDO::FETCH_ASSOC) ?: ['qtd'=>0,'vlr_servico'=>0,'postes_orc'=>0,'vlr_programado'=>0,'vlr_executado'=>0];
} else {
  $k = ['qtd'=>0,'vlr_servico'=>0,'postes_orc'=>0,'vlr_programado'=>0,'vlr_executado'=>0];
}
$kpi_quantidade = (int)$k['qtd'];
$kpi_valor_servico = (float)$k['vlr_servico'];
$kpi_valor_programado = (float)$k['vlr_programado'];
$kpi_valor_executado = (float)$k['vlr_executado'];
$kpi_postes_orc = (float)$k['postes_orc'];

// Agrupamentos globais (não paginados)
function fetchAllAssoc(PDO $pdo, string $sql, array $params): array {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

$sqlGTipo = "
SELECT COALESCE(o.tipo,'—') AS grp,
       COUNT(*) AS quantidade,
       SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
       COALESCE(SUM(p.prog),0) AS valor_programado,
       COALESCE(SUM(s.exec),0) AS valor_executado
FROM obra o
LEFT JOIN (
  SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra
) p ON p.obra = o.id
LEFT JOIN (
  SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra
) s ON s.obra = o.id
WHERE $whereSql
GROUP BY COALESCE(o.tipo,'—')
ORDER BY grp";
$rows = $mustSelectFilial ? [] : fetchAllAssoc($pdo, $sqlGTipo, $params);
$grp_tipo = [];
foreach ($rows as $r) { $grp_tipo[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

$sqlGResp = "
SELECT COALESCE(u.nome,'—') AS grp,
       COUNT(*) AS quantidade,
       SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
       COALESCE(SUM(p.prog),0) AS valor_programado,
       COALESCE(SUM(s.exec),0) AS valor_executado
FROM obra o
LEFT JOIN usuario u ON u.id = o.responsavel
LEFT JOIN (
  SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra
) p ON p.obra = o.id
LEFT JOIN (
  SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra
) s ON s.obra = o.id
WHERE $whereSql
GROUP BY COALESCE(u.nome,'—')
ORDER BY grp";
$rows = $mustSelectFilial ? [] : fetchAllAssoc($pdo, $sqlGResp, $params);
$grp_resp = [];
foreach ($rows as $r) { $grp_resp[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

$sqlGSit = "
SELECT COALESCE(o.situacao,'—') AS grp,
       COUNT(*) AS quantidade,
       SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
       COALESCE(SUM(p.prog),0) AS valor_programado,
       COALESCE(SUM(s.exec),0) AS valor_executado
FROM obra o
LEFT JOIN (
  SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra
) p ON p.obra = o.id
LEFT JOIN (
  SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra
) s ON s.obra = o.id
WHERE $whereSql
GROUP BY COALESCE(o.situacao,'—')
ORDER BY grp";
$rows = $mustSelectFilial ? [] : fetchAllAssoc($pdo, $sqlGSit, $params);
$grp_sit = [];
foreach ($rows as $r) { $grp_sit[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

$sqlGFaixa = "
SELECT 
  CASE 
    WHEN COALESCE(o.valor_servico,0) <= 10000 THEN 'Até 10 mil'
    WHEN COALESCE(o.valor_servico,0) <= 40000 THEN 'Até 40 mil'
    WHEN COALESCE(o.valor_servico,0) <= 100000 THEN 'Até 100 mil'
    ELSE 'Acima de 100 mil'
  END AS grp,
  COUNT(*) AS quantidade,
  SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
  COALESCE(SUM(p.prog),0) AS valor_programado,
  COALESCE(SUM(s.exec),0) AS valor_executado
FROM obra o
LEFT JOIN (
  SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra
) p ON p.obra = o.id
LEFT JOIN (
  SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra
) s ON s.obra = o.id
WHERE $whereSql
GROUP BY grp
ORDER BY FIELD(grp,'Até 10 mil','Até 40 mil','Até 100 mil','Acima de 100 mil')";
$rows = $mustSelectFilial ? [] : fetchAllAssoc($pdo, $sqlGFaixa, $params);
$grp_faixa = [];
foreach ($rows as $r) { $grp_faixa[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

// Análises: Quantas obras cada responsável tem por tipo
$sqlRespTipo = "
SELECT COALESCE(u.nome,'—') AS responsavel,
       COALESCE(o.tipo,'—') AS tipo,
       COUNT(*) AS qtd
FROM obra o
LEFT JOIN usuario u ON u.id = o.responsavel
WHERE $whereSql
GROUP BY COALESCE(u.nome,'—'), COALESCE(o.tipo,'—')
ORDER BY responsavel, tipo";
$rowsRespTipo = $mustSelectFilial ? [] : fetchAllAssoc($pdo, $sqlRespTipo, $params);

// Análises: Quantas obras cada responsável tem por faixa de valor de serviço
$sqlRespFaixa = "
SELECT COALESCE(u.nome,'—') AS responsavel,
  CASE 
    WHEN COALESCE(o.valor_servico,0) <= 10000 THEN 'Até 10 mil'
    WHEN COALESCE(o.valor_servico,0) <= 40000 THEN 'Até 40 mil'
    WHEN COALESCE(o.valor_servico,0) <= 100000 THEN 'Até 100 mil'
    ELSE 'Acima de 100 mil'
  END AS faixa,
  COUNT(*) AS qtd
FROM obra o
LEFT JOIN usuario u ON u.id = o.responsavel
WHERE $whereSql
GROUP BY responsavel, faixa
ORDER BY responsavel, FIELD(faixa,'Até 10 mil','Até 40 mil','Até 100 mil','Acima de 100 mil')";
$rowsRespFaixa = $mustSelectFilial ? [] : fetchAllAssoc($pdo, $sqlRespFaixa, $params);

// Query principal com agregados por obra
$sqlLista = "
SELECT 
  o.id,
  o.codigo,
  o.descricao,
  o.filial,
  o.tipo,
  o.situacao,
  o.data_entrada,
  COALESCE(o.valor_servico,0) AS valor_servico,
  COALESCE(o.poste_distribuicao,0) + COALESCE(o.poste_transmissao,0) AS postes_orc,
  u.nome AS responsavel,
  COALESCE(p.prog,0) AS valor_programado,
  COALESCE(s.exec,0) AS valor_executado,
  COALESCE(pi.postes_instalados,0) AS postes_instalados
FROM obra o
LEFT JOIN (
  SELECT obra, SUM(financeiro) AS prog
  FROM programacao
  WHERE tipo <> 'PROGRAMAÇÃO CANCELADA'
  GROUP BY obra
) p ON p.obra = o.id
LEFT JOIN (
  SELECT obra, SUM(quantidade * valor) AS exec
  FROM programacao_servico
  WHERE status = 'EXECUTADO'
  GROUP BY obra
) s ON s.obra = o.id
LEFT JOIN (
  SELECT ps.obra, SUM(ps.quantidade) AS postes_instalados
  FROM programacao_servico ps
  JOIN servico sv ON sv.id = ps.servico AND sv.grupo = 'INSTALAR POSTE'
  WHERE ps.status IN ('CONFIRMADO','EXECUTADO')
  GROUP BY ps.obra
) pi ON pi.obra = o.id
LEFT JOIN usuario u ON u.id = o.responsavel
WHERE $whereSql
ORDER BY o.data_entrada DESC, o.id DESC
";

// Aplica paginação
$sqlLista .= " LIMIT $per_page OFFSET $offset";

if (!$mustSelectFilial) {
  $stmtLista = $pdo->prepare($sqlLista);
  $stmtLista->execute($params);
  $obras = $stmtLista->fetchAll();
} else {
  $obras = [];
}

// (KPIs e agrupamentos agora são calculados por SQL para todo o conjunto filtrado)

// Opções de filtro (lists)
function fetchPairs(PDO $pdo, string $sql, array $params = []): array {
    try {
        if ($params && preg_match('/:([a-zA-Z0-9_]+)/', $sql)) { $st=$pdo->prepare($sql); $st->execute($params); return $st->fetchAll(PDO::FETCH_NUM); }
        return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
    } catch (Throwable $e) { return []; }
}
// Lista de filiais para o select, já limitada por usuario_filial
try {
  if ($uid && isset($ufUserCol,$ufFilialCol) && $ufUserCol && $ufFilialCol) {
    $filiais = fetchPairs($pdo, "SELECT DISTINCT f.id, f.titulo FROM filial f JOIN usuario_filial uf ON uf.$ufFilialCol = f.id WHERE uf.$ufUserCol = :uid ORDER BY f.titulo", [':uid'=>$uid]);
  } else {
    $filiais = fetchPairs($pdo, 'SELECT id, titulo FROM filial ORDER BY titulo');
  }
} catch (Throwable $e) { $filiais = fetchPairs($pdo, 'SELECT id, titulo FROM filial ORDER BY titulo'); }
$tipos_opts = fetchPairs($pdo, 'SELECT DISTINCT tipo, tipo FROM obra WHERE tipo IS NOT NULL AND tipo <> "" ORDER BY tipo');
$situacoes_opts = fetchPairs($pdo, 'SELECT DISTINCT situacao, situacao FROM obra WHERE situacao IS NOT NULL AND situacao <> "" ORDER BY situacao');
$responsaveis = fetchPairs($pdo, 'SELECT DISTINCT u.id, u.nome FROM obra o LEFT JOIN usuario u ON u.id = o.responsavel WHERE u.id IS NOT NULL ORDER BY u.nome');

// Formata valores com sufixos compactos: M=mil, MM=milhão
function money_br_compact($v) {
  $v = (float)$v;
  $neg = $v < 0;
  $abs = abs($v);
  if ($abs >= 1000000) {
    $n = $abs / 1000000.0;
    $s = rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',') . 'MM';
  } elseif ($abs >= 1000) {
    $n = $abs / 1000.0;
    $s = rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',') . 'M';
  } else {
    $s = number_format($abs, 2, ',', '.');
  }
  return ($neg ? '- ' : '') . 'R$ ' . $s;
}

// Helper para construir URLs de paginação preservando filtros
function build_query(array $overrides = []) {
  $q = $_GET;
  foreach ($overrides as $k=>$v) { $q[$k] = $v; }
  return '?' . http_build_query($q);
}

?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carteira de Obras Sintética - IGOB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    body { min-height:100vh; background: radial-gradient(circle at 10% 20%, #121827, #0b1220); }
    .nav-glass { backdrop-filter: blur(14px); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); }
  .card-glass { position:relative; overflow:hidden; backdrop-filter: blur(10px); background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); }
  .card-glass.filter-card { overflow: visible; position: relative; z-index: 1200; }
  .card-glass.filter-card .dropdown-menu { z-index: 2000; position: absolute; }
  .kpi { min-height:150px; }
  .kpi .icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
  .kpi-title { font-size:1rem; font-weight:700; letter-spacing:.2px; color:#aab4cf !important; }
  .kpi-value { font-size:1.6rem; font-weight:800; }
    .badge-soft { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); }
    .table thead th { border-color: rgba(255,255,255,.08); }
    .table tbody td { border-color: rgba(255,255,255,.05); }
    .loading-overlay { position:fixed; inset:0; background:rgba(10,15,28,.75); backdrop-filter: blur(6px); display:none; align-items:center; justify-content:center; z-index:1050; }
    .loading-overlay .spinner-border { width:3rem; height:3rem; }
  </style>
  <?php function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } ?>
</head>
<body class="text-light">
  <nav class="navbar navbar-expand-lg nav-glass px-3 my-2 rounded-4 container-xxl">
  <a class="navbar-brand fw-semibold" href="<?= h(igob_url('index.php')) ?>">IGOB</a>
    <div class="ms-auto d-flex align-items-center gap-2">
  <a href="<?= h(igob_url('logout.php')) ?>" class="btn btn-sm btn-outline-light rounded-pill">Sair</a>
    </div>
  </nav>

  <div id="loading" class="loading-overlay"><div class="text-center"><div class="spinner-border text-light" role="status"></div><div class="mt-3">Carregando, por favor aguarde…</div></div></div>
  <main class="container-xxl py-4">
    <div class="card card-glass filter-card p-3 mb-4">
      <form id="filterForm" method="get" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Filial</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countF = count($filiaisSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countF ? ($countF.' selecionada(s)') : 'Selecione ao menos 1 filial' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:65vh; overflow:auto;">
              <?php foreach ($filiais as $f): $checked = in_array((string)$f[0], $filiaisSel, true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="filial[]" value="<?= (int)$f[0] ?>" id="filial<?= (int)$f[0] ?>" <?= $checked ?>>
                  <label class="form-check-label" for="filial<?= (int)$f[0] ?>"><?= h($f[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Tipo</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countT = count($tipos); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countT ? ($countT.' selecionado(s)') : 'Todos' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:65vh; overflow:auto;">
              <?php foreach ($tipos_opts as $t): $checked = in_array((string)$t[0], $tipos, true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="tipo[]" value="<?= h($t[0]) ?>" id="tipo<?= h($t[0]) ?>" <?= $checked ?>>
                  <label class="form-check-label" for="tipo<?= h($t[0]) ?>"><?= h($t[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Situação</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countS = count($situacoes); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countS ? ($countS.' selecionada(s)') : 'Todas' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:65vh; overflow:auto;">
              <?php foreach ($situacoes_opts as $s): $checked = in_array((string)$s[0], $situacoes, true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="situacao[]" value="<?= h($s[0]) ?>" id="sit<?= h($s[0]) ?>" <?= $checked ?>>
                  <label class="form-check-label" for="sit<?= h($s[0]) ?>"><?= h($s[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Responsável</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countR = count($responsaveisSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countR ? ($countR.' selecionado(s)') : 'Todos' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:65vh; overflow:auto;">
              <?php foreach ($responsaveis as $r): $checked = in_array((string)$r[0], $responsaveisSel, true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="responsavel[]" value="<?= (int)$r[0] ?>" id="resp<?= (int)$r[0] ?>" <?= $checked ?>>
                  <label class="form-check-label" for="resp<?= (int)$r[0] ?>"><?= h($r[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Data de entrada (início)</label>
          <input type="date" name="data_ini" value="<?= h($data_ini) ?>" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Data de entrada (fim)</label>
          <input type="date" name="data_fim" value="<?= h($data_fim) ?>" class="form-control" />
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
          <a href="?" class="btn btn-outline-light mt-3">Limpar</a>
        </div>
      </form>
    </div>

    <!-- Alert seleção obrigatória de filial -->
    <?php if ($mustSelectFilial): ?>
      <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Selecione ao menos uma filial para exibir o relatório.
      </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-lg-5 align-items-stretch">
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-primary-subtle text-primary d-flex align-items-center justify-content-center rounded"><i class="bi bi-collection"></i></div>
            <div>
              <div class="kpi-title text-secondary">Quantidade de obras</div>
              <div class="kpi-value"><?= number_format($kpi_quantidade, 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-info-subtle text-info d-flex align-items-center justify-content-center rounded"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="kpi-title text-secondary">Valor Serviço</div>
              <div class="kpi-value"><?= money_br_compact($kpi_valor_servico) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-warning-subtle text-warning d-flex align-items-center justify-content-center rounded"><i class="bi bi-clipboard-check"></i></div>
            <div>
              <div class="kpi-title text-secondary">Valor Programado</div>
              <div class="kpi-value"><?= money_br_compact($kpi_valor_programado) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-success-subtle text-success d-flex align-items-center justify-content-center rounded"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
              <div class="kpi-title text-secondary">Valor Executado</div>
              <div class="kpi-value"><?= money_br_compact($kpi_valor_executado) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center rounded"><i class="bi bi-signpost"></i></div>
            <div>
              <div class="kpi-title text-secondary">Postes (orçado)</div>
              <div class="kpi-value"><?= number_format($kpi_postes_orc, 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php
    // Preparação dos dados para os gráficos (somente quando houver filial selecionada)
    $chartRespTipo = null; $chartRespFaixa = null;
    if (!$mustSelectFilial) {
      // Responsável x Tipo (obras)
      $respSet = [];
      $tipoSet = [];
      foreach ($rowsRespTipo as $r) {
        $respSet[$r['responsavel']] = true;
        $tipoSet[$r['tipo']] = true;
      }
      $respLabels = array_keys($respSet);
      sort($respLabels, SORT_NATURAL|SORT_FLAG_CASE);
      $tipoLabels = array_keys($tipoSet);
      sort($tipoLabels, SORT_NATURAL|SORT_FLAG_CASE);
      // matriz valores
      $mat = [];
      foreach ($respLabels as $resp) { $mat[$resp] = array_fill_keys($tipoLabels, 0); }
      foreach ($rowsRespTipo as $r) { $mat[$r['responsavel']][$r['tipo']] = (int)$r['qtd']; }
      // paleta de cores
      $palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab'];
      $datasets = [];
      $ci = 0; $pc = count($palette);
      foreach ($tipoLabels as $tipo) {
        $data = [];
        foreach ($respLabels as $resp) { $data[] = (int)$mat[$resp][$tipo]; }
        $color = $palette[$ci % $pc]; $ci++;
        $datasets[] = [ 'label' => $tipo, 'data' => $data, 'backgroundColor' => $color ];
      }
      $chartRespTipo = [ 'labels' => $respLabels, 'datasets' => $datasets ];

      // Responsável x Faixa (obras)
      $faixaOrder = ['Até 10 mil','Até 40 mil','Até 100 mil','Acima de 100 mil'];
      $respSet2 = [];
      $faixaSet = [];
      foreach ($rowsRespFaixa as $r) { $respSet2[$r['responsavel']] = true; $faixaSet[$r['faixa']] = true; }
      $respLabels2 = array_keys($respSet2);
      sort($respLabels2, SORT_NATURAL|SORT_FLAG_CASE);
      // ordenar faixas conforme ordem alvo, mantendo apenas as existentes
      $faixaLabels = array_values(array_intersect($faixaOrder, array_keys($faixaSet)));
      // matriz valores
      $mat2 = [];
      foreach ($respLabels2 as $resp) { $mat2[$resp] = array_fill_keys($faixaLabels, 0); }
      foreach ($rowsRespFaixa as $r) { if (in_array($r['faixa'], $faixaLabels, true)) $mat2[$r['responsavel']][$r['faixa']] = (int)$r['qtd']; }
      // paleta específica para faixas
      $palette2 = ['#86b6f6','#7bd389','#ffd166','#ef476f'];
      $datasets2 = [];
      foreach ($faixaLabels as $i=>$faixa) {
        $data = [];
        foreach ($respLabels2 as $resp) { $data[] = (int)$mat2[$resp][$faixa]; }
        $color = $palette2[$i % count($palette2)];
        $datasets2[] = [ 'label' => $faixa, 'data' => $data, 'backgroundColor' => $color ];
      }
      $chartRespFaixa = [ 'labels' => $respLabels2, 'datasets' => $datasets2 ];
    }
    ?>

    <?php if (!$mustSelectFilial): ?>
    <!-- Gráficos: Responsável x Tipo e Responsável x Faixa -->
    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Responsável x Tipo (obras)</h6>
      <div class="position-relative" style="height: clamp(260px, 38vh, 440px);">
        <canvas id="chartRespTipo"></canvas>
      </div>
    </div>

    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Responsável x Faixa de Valor de Serviço (obras)</h6>
      <div class="position-relative" style="height: clamp(260px, 38vh, 440px);">
        <canvas id="chartRespFaixa"></canvas>
      </div>
    </div>
    <?php endif; ?>

  <!-- Agrupamentos -->
    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-3">Por Tipo</h6>
          <table class="table table-sm align-middle text-white-50">
            <thead><tr><th>Tipo</th><th class="text-end">Obras</th><th class="text-end">Vlr. Serviço</th><th class="text-end">Programado</th><th class="text-end">Executado</th></tr></thead>
            <tbody>
              <?php foreach ($grp_tipo as $k=>$v): ?>
                <tr>
                  <td><?= h($k) ?></td>
                  <td class="text-end"><?= number_format($v['quantidade'],0,',','.') ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_servico']) ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_programado']) ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_executado']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-3">Por Responsável</h6>
          <table class="table table-sm align-middle text-white-50">
            <thead><tr><th>Responsável</th><th class="text-end">Obras</th><th class="text-end">Vlr. Serviço</th><th class="text-end">Programado</th><th class="text-end">Executado</th></tr></thead>
            <tbody>
              <?php foreach ($grp_resp as $k=>$v): ?>
                <tr>
                  <td><?= h($k) ?></td>
                  <td class="text-end"><?= number_format($v['quantidade'],0,',','.') ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_servico']) ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_programado']) ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_executado']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-3">Por Faixa de Valor de Serviço</h6>
          <table class="table table-sm align-middle text-white-50">
            <thead><tr><th>Faixa</th><th class="text-end">Obras</th><th class="text-end">Vlr. Serviço</th><th class="text-end">Programado</th><th class="text-end">Executado</th></tr></thead>
            <tbody>
              <?php foreach ($grp_faixa as $k=>$v): ?>
                <tr>
                  <td><?= h($k) ?></td>
                  <td class="text-end"><?= number_format($v['quantidade'],0,',','.') ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_servico']) ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_programado']) ?></td>
                  <td class="text-end"><?= money_br_compact($v['valor_executado']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-3">Por Situação</h6>
          <table class="table table-sm align-middle text-white-50">
            <thead><tr><th>Situação</th><th class="text-end">Obras</th><th class="text-end">Vlr. Serviço</th><th class="text-end">Programado</th><th class="text-end">Executado</th></tr></thead>
            <tbody>
              <?php foreach ($grp_sit as $k=>$v): ?>
                <tr>
                  <td><?= h($k) ?></td>
                  <td class="text-end"><?= number_format($v['quantidade'],0,',','.') ?></td>
                    <td class="text-end"><?= money_br_compact($v['valor_servico']) ?></td>
                    <td class="text-end"><?= money_br_compact($v['valor_programado']) ?></td>
                    <td class="text-end"><?= money_br_compact($v['valor_executado']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Análises por Responsável: substituídas por gráficos acima -->

    <!-- Lista de obras -->
  <div class="card card-glass p-3 mb-5">
      <div class="d-flex align-items-center justify-content-between">
        <h6 class="mb-3 mb-sm-0">Obras</h6>
        <form method="get" class="d-flex gap-2 mb-0">
          <?php
          // preservar filtros ao usar busca rápida na lista
          $keep = $_GET; unset($keep['q'], $keep['page']);
          foreach ($keep as $k=>$v) {
            if (is_array($v)) { foreach ($v as $vv) echo '<input type="hidden" name="'.h($k).'[]" value="'.h($vv).'">'; }
            else { echo '<input type="hidden" name="'.h($k).'" value="'.h($v).'">'; }
          }
          ?>
          <input type="search" name="q" value="<?= h($search) ?>" class="form-control form-control-sm" placeholder="Buscar por código ou descrição..." />
          <button class="btn btn-sm btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
        </form>
        <span class="badge rounded-pill badge-soft ms-2">Total: <?= number_format($total,0,',','.') ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Código</th>
              <th>Descrição</th>
              <th>Tipo</th>
              <th>Situação</th>
              <th>Resp.</th>
              <th class="text-end">Vlr. Serviço</th>
              <th class="text-end">Programado</th>
              <th class="text-end">Executado</th>
              <th class="text-end">Postes (Orc)</th>
              <th class="text-end">Postes Inst.</th>
              <th>Entrada</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($obras as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= h($r['codigo']) ?></td>
                <td><?= h($r['descricao']) ?></td>
                <td><span class="badge badge-soft"><?= h($r['tipo']) ?></span></td>
                <td><?= h($r['situacao']) ?></td>
                <td><?= h($r['responsavel']) ?></td>
                <td class="text-end"><?= money_br_compact((float)$r['valor_servico']) ?></td>
                <td class="text-end"><?= money_br_compact((float)$r['valor_programado']) ?></td>
                <td class="text-end"><?= money_br_compact((float)$r['valor_executado']) ?></td>
                <td class="text-end"><?= number_format((float)$r['postes_orc'],0,',','.') ?></td>
                <td class="text-end"><?= number_format((float)$r['postes_instalados'],0,',','.') ?></td>
                <td><?= h($r['data_entrada']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="small text-secondary">Página <?= (int)$page ?> de <?= (int)$pages ?></div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
              <a class="page-link" href="<?= $page<=1?'#':h(build_query(['page'=>1])) ?>">«</a>
            </li>
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
              <a class="page-link" href="<?= $page<=1?'#':h(build_query(['page'=>$page-1])) ?>">‹</a>
            </li>
            <?php
              $start = max(1, $page-2);
              $end = min($pages, $page+2);
              for ($p=$start; $p<=$end; $p++):
            ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
              <a class="page-link" href="<?= h(build_query(['page'=>$p])) ?>"><?= (int)$p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
              <a class="page-link" href="<?= $page>=$pages?'#':h(build_query(['page'=>$page+1])) ?>">›</a>
            </li>
            <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
              <a class="page-link" href="<?= $page>=$pages?'#':h(build_query(['page'=>$pages])) ?>">»</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      var form = document.getElementById('filterForm');
      var overlay = document.getElementById('loading');
      if (form && overlay) {
        form.addEventListener('submit', function(ev){
          // exigir pelo menos uma filial selecionada
          var hasFilial = !!document.querySelector('input[name="filial[]"]:checked');
          if (!hasFilial) {
            ev.preventDefault();
            ev.stopPropagation();
            alert('Selecione ao menos uma filial.');
            return false;
          }
          overlay.style.display = 'flex';
        });
      }
    })();
    // Filtro dinâmico na tabela (client-side)
    (function(){
      var input = document.getElementById('searchTable');
      if (!input) return;
      var table = document.querySelector('table.table tbody');
      var badge = document.getElementById('visibleCount');
      function norm(s){ return (s||'').toString().toLowerCase(); }
      function apply(){
        var q = norm(input.value);
        var rows = table ? table.querySelectorAll('tr') : [];
        var visible = 0;
        for (var i=0;i<rows.length;i++){
          var r = rows[i];
          var text = norm(r.innerText);
          var show = !q || text.indexOf(q) !== -1;
          r.style.display = show ? '' : 'none';
          if (show) visible++;
        }
        if (badge) badge.textContent = 'Exibindo: ' + (visible.toLocaleString('pt-BR'));
      }
      input.addEventListener('input', apply);
      // aplica quando a página carrega com q preenchido
      apply();
    })();

    // Charts
    (function(){
      <?php if (!$mustSelectFilial): ?>
      try {
        const dataRespTipo = <?= json_encode($chartRespTipo, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
        const ctx1 = document.getElementById('chartRespTipo').getContext('2d');
        new Chart(ctx1, {
          type: 'bar',
          data: dataRespTipo,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top', labels: { color: '#cbd5e1' } },
              tooltip: { mode: 'index', intersect: false }
            },
            scales: {
              x: { stacked: false, ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' } },
              y: { stacked: false, ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true }
            }
          }
        });

        const dataRespFaixa = <?= json_encode($chartRespFaixa, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
        const ctx2 = document.getElementById('chartRespFaixa').getContext('2d');
        new Chart(ctx2, {
          type: 'bar',
          data: dataRespFaixa,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'top', labels: { color: '#cbd5e1' } },
              tooltip: { mode: 'index', intersect: false }
            },
            scales: {
              x: { stacked: false, ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' } },
              y: { stacked: false, ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true }
            }
          }
        });
      } catch (e) { console.error('Erro ao iniciar gráficos:', e); }
      <?php endif; ?>
    })();
  </script>
</body>
</html>
