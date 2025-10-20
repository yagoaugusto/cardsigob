<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_require_login();

global $pdo;
$__user = auth_current_user();

// Helpers (copiados de carteira_programadas)
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function filterParams(array $params, string $sql): array {
  if (!$params) return [];
  if (!preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $m)) return [];
  $need = array_unique($m[1]);
  $out = [];
  foreach ($need as $name) { $key = ':' . $name; if (array_key_exists($key, $params)) { $out[$key] = $params[$key]; } }
  return $out;
}
function fetchAllAssoc(PDO $pdo, string $sql, array $params = []): array {
  $st = $pdo->prepare($sql);
  $st->execute(filterParams($params, $sql));
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function fetchPairs(PDO $pdo, string $sql, array $params = []): array {
  try {
    if ($params && preg_match('/:([a-zA-Z0-9_]+)/', $sql)) { $st = $pdo->prepare($sql); $st->execute(filterParams($params, $sql)); return $st->fetchAll(PDO::FETCH_NUM); }
    return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);
  } catch (Throwable $e) { return []; }
}
function money_br_compact($v) {
  $v = (float)$v; $neg = $v < 0; $abs = abs($v);
  if ($abs >= 1000000) { $n = $abs / 1000000.0; $s = rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',') . 'MM'; }
  elseif ($abs >= 1000) { $n = $abs / 1000.0; $s = rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',') . 'M'; }
  else { $s = number_format($abs, 2, ',', '.'); }
  return ($neg ? '- ' : '') . 'R$ ' . $s;
}
function current_db(PDO $pdo): ?string { try { return (string)$pdo->query('SELECT DATABASE()')->fetchColumn(); } catch (Throwable $e) { return null; } }
function detect_column(PDO $pdo, string $table, array $candidates): ?string {
  $db = current_db($pdo); if (!$db) return null;
  $in = implode(',', array_map(function($c){ return "'".str_replace("'","''",$c)."'"; }, $candidates));
  $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME IN ($in) ORDER BY FIELD(COLUMN_NAME,$in) LIMIT 1";
  $st = $pdo->prepare($sql); $st->execute([':db'=>$db, ':tbl'=>$table]);
  $col = $st->fetchColumn(); return $col ? (string)$col : null;
}

// Filtros
$filiaisSel = isset($_GET['filial']) ? array_values(array_filter((array)$_GET['filial'])) : [];
$tipos = isset($_GET['tipo']) ? array_values(array_filter((array)$_GET['tipo'])) : [];
$data_ini = isset($_GET['data_ini']) && $_GET['data_ini'] !== '' ? $_GET['data_ini'] : null;
$data_fim = isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null;

// Filiais permitidas ao usuário
try {
  $uid = (int)($__user['id'] ?? 0);
  $ufUserCol = detect_column($pdo, 'usuario_filial', ['usuario','id_usuario','usuario_id','user','user_id']);
  $ufFilialCol = detect_column($pdo, 'usuario_filial', ['filial','id_filial','filial_id','cd_filial']);
  if ($uid && $ufUserCol && $ufFilialCol) {
    $filiais = fetchPairs($pdo, "SELECT DISTINCT f.id, f.titulo FROM filial f JOIN usuario_filial uf ON uf.$ufFilialCol = f.id WHERE uf.$ufUserCol = :uid ORDER BY f.titulo", [':uid'=>$uid]);
  } else {
    $filiais = fetchPairs($pdo, 'SELECT id, titulo FROM filial ORDER BY titulo');
  }
} catch (Throwable $e) { $filiais = fetchPairs($pdo, 'SELECT id, titulo FROM filial ORDER BY titulo'); }
$allowedIds = array_map('intval', array_column($filiais, 0));
if ($filiaisSel) { $filiaisSel = array_values(array_intersect(array_map('intval',$filiaisSel), $allowedIds)); }

$mustSelectFilial = count($filiaisSel) === 0;
$mustSelectDates = !$data_ini || !$data_fim;
$mustRequire = $mustSelectFilial || $mustSelectDates;

// Detectar colunas relevantes
$obraFilialCol = detect_column($pdo, 'obra', ['filial']);
$obraTipoCol = detect_column($pdo, 'obra', ['tipo']);
$fechMedObraCol = detect_column($pdo, 'fechamento_medicao', ['obra']);
$fechMedValorCol = detect_column($pdo, 'fechamento_medicao', ['valor','valor_medido','vlr','valor_executado']);
$fechMedDataCol = detect_column($pdo, 'fechamento_medicao', ['data','dt','data_medicao']);
$fechMedUsuarioCol = detect_column($pdo, 'fechamento_medicao', ['usuario','user','user_id']);
// programacao_servico columns
$psObraCol = detect_column($pdo, 'programacao_servico', ['obra']);
$psDataCol = detect_column($pdo, 'programacao_servico', ['data','dt','data_execucao','data_lancamento']);
$psQtdCol = detect_column($pdo, 'programacao_servico', ['quantidade','qtd','qtde','qnt']);
$psValorCol = detect_column($pdo, 'programacao_servico', ['valor','vlr','preco']);
$psStatusCol = detect_column($pdo, 'programacao_servico', ['status','situacao']);

// Segurança: se faltar alguma coluna essencial, abortar análise
$canAnalyze = $fechMedObraCol && $fechMedValorCol && $fechMedDataCol;

// Montar WHERE base usando fechamento_medicao + join leve com obra para filtrar por filial e tipo
$params = [];
$where = ["1=1"];
if (!$mustRequire && $canAnalyze) {
  $where[] = "fm.$fechMedDataCol BETWEEN :dini AND :dfim"; $params[':dini'] = $data_ini; $params[':dfim'] = $data_fim;
}
if ($filiaisSel && $obraFilialCol) {
  $in = [];
  foreach ($filiaisSel as $i=>$v) { $k = ":f$i"; $in[] = $k; $params[$k] = (int)$v; }
  $where[] = "o.$obraFilialCol IN (".implode(',', $in).")";
}
if ($tipos && $obraTipoCol) {
  $in = [];
  foreach ($tipos as $i=>$v) { $k = ":t$i"; $in[] = $k; $params[$k] = (string)$v; }
  $where[] = "o.$obraTipoCol IN (".implode(',', $in).")";
}
$whereSql = implode(' AND ', $where);

// KPIs
$kpi_qtd_obras = 0; $kpi_valor_medido = 0.0;
$kpi_valor_executado_obras = 0.0;
$kpi_tempo_medio_dias = null; // média de dias entre finalização e medição
$series_diaria = []; // [data => valor]
$series_diaria_obras = []; // [data => qtd obras]
$por_usuario = []; // [usuario_nome => valor]
$por_usuario_obras = []; // [usuario_nome => qtd obras]
$matriz_usuario_dia = []; // [usuario_nome => [data => valor]]
$matriz_usuario_dia_qtd = []; // [usuario_nome => [data => qtd obras]]
$labels_dias = [];
$usuarios_labels = [];
// Lista de medições
$medicoes = [];

if (!$mustRequire && $canAnalyze) {
  // Quantidade de obras medidas e valor total medido no período
  $sqlKpi = "
    SELECT COUNT(DISTINCT fm.$fechMedObraCol) AS obras, COALESCE(SUM(fm.$fechMedValorCol),0) AS valor
    FROM fechamento_medicao fm
    JOIN obra o ON o.id = fm.$fechMedObraCol
    WHERE $whereSql";
  $st = $pdo->prepare($sqlKpi); $st->execute(filterParams($params, $sqlKpi));
  $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['obras'=>0,'valor'=>0];
  $kpi_qtd_obras = (int)$row['obras'];
  $kpi_valor_medido = (float)$row['valor'];

  // Série diária de valor medido
  $sqlDia = "
    SELECT fm.$fechMedDataCol AS dia, COALESCE(SUM(fm.$fechMedValorCol),0) AS valor, COUNT(DISTINCT fm.$fechMedObraCol) AS obras
    FROM fechamento_medicao fm
    JOIN obra o ON o.id = fm.$fechMedObraCol
    WHERE $whereSql
    GROUP BY fm.$fechMedDataCol
    ORDER BY fm.$fechMedDataCol";
  $rows = fetchAllAssoc($pdo, $sqlDia, $params);
  foreach ($rows as $r) { $series_diaria[$r['dia']] = (float)$r['valor']; $series_diaria_obras[$r['dia']] = (int)$r['obras']; $labels_dias[] = $r['dia']; }

  // Valor medido por usuário
  $usuarioNomeCol = detect_column($pdo, 'usuario', ['nome','name']);
  $sqlUser = "
    SELECT COALESCE(u.$usuarioNomeCol, CONCAT('Usuário #', fm.$fechMedUsuarioCol)) AS usuario, COALESCE(SUM(fm.$fechMedValorCol),0) AS valor, COUNT(DISTINCT fm.$fechMedObraCol) AS obras
    FROM fechamento_medicao fm
    LEFT JOIN usuario u ON u.id = fm.$fechMedUsuarioCol
    JOIN obra o ON o.id = fm.$fechMedObraCol
    WHERE $whereSql
    GROUP BY COALESCE(u.$usuarioNomeCol, fm.$fechMedUsuarioCol)
    ORDER BY valor DESC";
  $rows = fetchAllAssoc($pdo, $sqlUser, $params);
  foreach ($rows as $r) { $por_usuario[$r['usuario']] = (float)$r['valor']; $por_usuario_obras[$r['usuario']] = (int)$r['obras']; $usuarios_labels[] = $r['usuario']; }

  // Matriz usuário x dia
  $sqlMat = "
    SELECT COALESCE(u.$usuarioNomeCol, CONCAT('Usuário #', fm.$fechMedUsuarioCol)) AS usuario, fm.$fechMedDataCol AS dia, COALESCE(SUM(fm.$fechMedValorCol),0) AS valor, COUNT(DISTINCT fm.$fechMedObraCol) AS obras
    FROM fechamento_medicao fm
    LEFT JOIN usuario u ON u.id = fm.$fechMedUsuarioCol
    JOIN obra o ON o.id = fm.$fechMedObraCol
    WHERE $whereSql
    GROUP BY COALESCE(u.$usuarioNomeCol, fm.$fechMedUsuarioCol), fm.$fechMedDataCol
    ORDER BY usuario, dia";
  $rows = fetchAllAssoc($pdo, $sqlMat, $params);
  // Garantir ordem consistente de dias
  $labels_dias = array_values(array_unique($labels_dias));
  sort($labels_dias);
  foreach ($rows as $r) {
    $u = $r['usuario']; $d = $r['dia']; $v = (float)$r['valor'];
    $q = (int)$r['obras'];
    if (!isset($matriz_usuario_dia[$u])) { $matriz_usuario_dia[$u] = []; }
    if (!isset($matriz_usuario_dia_qtd[$u])) { $matriz_usuario_dia_qtd[$u] = []; }
    $matriz_usuario_dia[$u][$d] = $v;
    $matriz_usuario_dia_qtd[$u][$d] = $q;
  }
  // Completar zeros
  $usuarios_labels = array_values(array_unique($usuarios_labels));
  sort($usuarios_labels, SORT_NATURAL|SORT_FLAG_CASE);

  // KPI: Valor executado das obras medidas (considera todo o histórico de execução; o período filtra apenas as medições)
  if ($psObraCol && $psDataCol && $psQtdCol && $psValorCol) {
    $psWhereStatus = $psStatusCol ? (" UPPER(COALESCE(ps.$psStatusCol,''))='EXECUTADO' AND ") : '';
    $sqlKpiExec = "
      SELECT COALESCE(SUM(ps.$psQtdCol * ps.$psValorCol),0) AS valor
      FROM programacao_servico ps
      JOIN (
        SELECT DISTINCT fm.$fechMedObraCol AS obra
        FROM fechamento_medicao fm
        JOIN obra o ON o.id = fm.$fechMedObraCol
        WHERE $whereSql
      ) x ON x.obra = ps.$psObraCol
      WHERE " . $psWhereStatus . " 1=1";
    $stE = $pdo->prepare($sqlKpiExec);
    $stE->execute(filterParams($params, $sqlKpiExec));
    $kpi_valor_executado_obras = (float)$stE->fetchColumn();
  }

  // KPI: Tempo médio para medição (dias entre finalização e medição)
  if ($psObraCol && $psDataCol) {
    $statusCond = $psStatusCol ? (" UPPER(COALESCE(ps.".$psStatusCol.",''))='EXECUTADO' AND ") : '';
    // Para cada medição, pega a última data de finalização (MAX ps.data) menor ou igual à data da medição
    $sqlTempo = "
      SELECT AVG(DATEDIFF(
                 fm.$fechMedDataCol,
                 (
                   SELECT MAX(ps.$psDataCol)
                   FROM programacao_servico ps
                   WHERE ps.$psObraCol = fm.$fechMedObraCol
                     AND " . $statusCond . " ps.$psDataCol <= fm.$fechMedDataCol
                 )
               )) AS media_dias
      FROM fechamento_medicao fm
      JOIN obra o ON o.id = fm.$fechMedObraCol
      WHERE $whereSql
        AND (
          SELECT MAX(ps.$psDataCol)
          FROM programacao_servico ps
          WHERE ps.$psObraCol = fm.$fechMedObraCol
            AND " . $statusCond . " ps.$psDataCol <= fm.$fechMedDataCol
        ) IS NOT NULL";
    $stT = $pdo->prepare($sqlTempo);
    $stT->execute(filterParams($params, $sqlTempo));
    $avgDias = $stT->fetchColumn();
    if ($avgDias !== false && $avgDias !== null) { $kpi_tempo_medio_dias = (float)$avgDias; }
  }

  // Lista de medições
  $usuarioNomeCol = $usuarioNomeCol ?: detect_column($pdo, 'usuario', ['nome','name']);
  if ($psObraCol && $psDataCol && $psQtdCol && $psValorCol) {
    $psWhereStatus = $psStatusCol ? (" UPPER(COALESCE(ps.$psStatusCol,''))='EXECUTADO' AND ") : '';
    $sqlList = "
      SELECT 
        fm.$fechMedDataCol AS data_medicao,
        fm.$fechMedValorCol AS valor_medido,
        fm.$fechMedObraCol AS obra_id,
        COALESCE(u.$usuarioNomeCol, CONCAT('Usuário #', fm.$fechMedUsuarioCol)) AS usuario,
        o.codigo, o.descricao,
        psagg.data_finalizacao,
        psagg.valor_executado
      FROM fechamento_medicao fm
      JOIN obra o ON o.id = fm.$fechMedObraCol
      LEFT JOIN usuario u ON u.id = fm.$fechMedUsuarioCol
      LEFT JOIN (
        SELECT ps.$psObraCol AS obra,
               MAX(ps.$psDataCol) AS data_finalizacao,
               COALESCE(SUM(ps.$psQtdCol * ps.$psValorCol),0) AS valor_executado
        FROM programacao_servico ps
        WHERE " . $psWhereStatus . " 1=1
        GROUP BY ps.$psObraCol
      ) psagg ON psagg.obra = fm.$fechMedObraCol
      WHERE $whereSql
      ORDER BY fm.$fechMedDataCol DESC, o.codigo ASC";
  } else {
    // Fallback: sem colunas de programacao_servico detectadas, retornar lista sem finalização/execução
    $sqlList = "
      SELECT 
        fm.$fechMedDataCol AS data_medicao,
        fm.$fechMedValorCol AS valor_medido,
        fm.$fechMedObraCol AS obra_id,
        COALESCE(u.$usuarioNomeCol, CONCAT('Usuário #', fm.$fechMedUsuarioCol)) AS usuario,
        o.codigo, o.descricao,
        NULL AS data_finalizacao,
        0 AS valor_executado
      FROM fechamento_medicao fm
      JOIN obra o ON o.id = fm.$fechMedObraCol
      LEFT JOIN usuario u ON u.id = fm.$fechMedUsuarioCol
      WHERE $whereSql
      ORDER BY fm.$fechMedDataCol DESC, o.codigo ASC";
  }
  $stL = $pdo->prepare($sqlList);
  $paramsList = $params; $paramsList[':ps_dini'] = $data_ini; $paramsList[':ps_dfim'] = $data_fim;
  $stL->execute(filterParams($paramsList, $sqlList));
  $medicoes = $stL->fetchAll(PDO::FETCH_ASSOC);
}

// Opções de filtros
$tipos_opts = fetchPairs($pdo, 'SELECT DISTINCT tipo, tipo FROM obra WHERE tipo IS NOT NULL AND tipo <> "" ORDER BY tipo');

?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Medições - IGOB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
  <style>
    body { min-height:100vh; background: radial-gradient(circle at 10% 20%, #121827, #0b1220); }
    .nav-glass { backdrop-filter: blur(14px); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); }
    .card-glass { position:relative; overflow:hidden; backdrop-filter: blur(10px); background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); }
    .kpi .icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .kpi-title { font-size:1rem; font-weight:700; letter-spacing:.2px; color:#aab4cf !important; }
    .kpi-value { font-size:1.6rem; font-weight:800; }
    .badge-soft { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); }
    /* Matriz: fixar a 1ª coluna e destacar células com valor */
    .table-sticky-first thead th:first-child,
    .table-sticky-first tbody td.user-col,
    .table-sticky-first tfoot th:first-child {
      position: sticky; left: 0; z-index: 2; background: rgba(12,17,31,.9);
      backdrop-filter: blur(4px);
    }
    .table-sticky-first thead th:first-child { z-index: 3; }
    .table-sticky-first td.cell-has {
      background: rgba(88, 214, 141, 0.08);
      box-shadow: inset 0 0 0 1px rgba(88,214,141,.15);
    }
    .table-sticky-first td.cell-has .small { color: #9fe6b8 !important; }
  </style>
</head>
<body class="text-light">
  <nav class="navbar navbar-expand-lg nav-glass px-3 my-2 rounded-4 container-xxl">
    <a class="navbar-brand fw-semibold" href="<?= h(igob_url('index.php')) ?>">IGOB</a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="<?= h(igob_url('logout.php')) ?>" class="btn btn-sm btn-outline-light rounded-pill">Sair</a>
    </div>
  </nav>
  <main class="container-xxl py-4">
    <div class="card card-glass p-3 mb-4">
      <form id="filterForm" method="get" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Filial (obrigatório)</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countF = count($filiaisSel); $filiaisSelInt = array_map('intval',$filiaisSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countF ? ($countF.' selecionada(s)') : 'Selecione ao menos 1 filial' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:65vh; overflow:auto;">
              <?php foreach ($filiais as $f): $checked = in_array((int)$f[0], $filiaisSelInt, true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="filial[]" value="<?= (int)$f[0] ?>" id="filial<?= (int)$f[0] ?>" <?= $checked ?>>
                  <label class="form-check-label" for="filial<?= (int)$f[0] ?>"><?= h($f[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label">Data inicial (obrigatório)</label>
          <input type="date" name="data_ini" value="<?= h($data_ini) ?>" class="form-control" />
        </div>
        <div class="col-md-2">
          <label class="form-label">Data final (obrigatório)</label>
          <input type="date" name="data_fim" value="<?= h($data_fim) ?>" class="form-control" />
        </div>
        <div class="col-md-4">
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
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
          <a href="?" class="btn btn-outline-light">Limpar</a>
        </div>
      </form>
    </div>

    <?php if ($mustRequire): ?>
      <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Selecione ao menos uma filial e informe o período para exibir os números.
      </div>
    <?php endif; ?>

    <!-- KPIs -->
  <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-lg-5 align-items-stretch">
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-primary-subtle text-primary rounded d-flex align-items-center justify-content-center"><i class="bi bi-collection"></i></div>
            <div>
              <div class="kpi-title text-secondary">Obras medidas</div>
              <div class="kpi-value"><?= number_format($kpi_qtd_obras, 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-success-subtle text-success rounded d-flex align-items-center justify-content-center"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="kpi-title text-secondary">Valor medido</div>
              <div class="kpi-value"><?= money_br_compact($kpi_valor_medido) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-info-subtle text-info rounded d-flex align-items-center justify-content-center"><i class="bi bi-hourglass-split"></i></div>
            <div>
              <div class="kpi-title text-secondary">Tempo médio para medição (dias)</div>
              <div class="kpi-value"><?= $kpi_tempo_medio_dias !== null ? number_format($kpi_tempo_medio_dias, 1, ',', '.') : '—' ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-warning-subtle text-warning rounded d-flex align-items-center justify-content-center"><i class="bi bi-graph-up-arrow"></i></div>
            <div>
              <div class="kpi-title text-secondary">Valor executado (obras medidas)</div>
              <div class="kpi-value"><?= money_br_compact($kpi_valor_executado_obras) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if(!$mustRequire && $canAnalyze): ?>
    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Valor medido por dia</h6>
      <div class="position-relative" style="height: clamp(260px, 38vh, 440px);">
        <canvas id="chartDia"></canvas>
      </div>
    </div>

    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Valor medido por usuário</h6>
      <div class="position-relative" style="height: clamp(260px, 38vh, 440px);">
        <canvas id="chartUsuario"></canvas>
      </div>
    </div>

    <div class="card card-glass p-3 mb-5">
      <h6 class="mb-3">Matriz usuário x dia (valor medido)</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle text-white-50 table-sticky-first">
          <thead>
            <tr>
              <th>Usuário</th>
              <?php foreach ($labels_dias as $d): ?>
                <th class="text-end"><?= $d ? date('d/m', strtotime($d)) : '' ?></th>
              <?php endforeach; ?>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios_labels as $u): $rowTotal = 0; ?>
              <tr>
                <td class="user-col fw-semibold"><?= h($u) ?></td>
                <?php foreach ($labels_dias as $d): $v = (float)($matriz_usuario_dia[$u][$d] ?? 0); $qtd = (int)($matriz_usuario_dia_qtd[$u][$d] ?? 0); $rowTotal += $v; $has = $v > 0 || $qtd > 0; ?>
                  <td class="text-end <?= $has ? 'cell-has' : '' ?>">
                    <div><?= number_format($v, 2, ',', '.') ?></div>
                    <div class="small text-secondary"><?= $qtd ?> ob</div>
                  </td>
                <?php endforeach; ?>
                <td class="text-end fw-semibold"><?= number_format($rowTotal, 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="table-dark">
              <th>Total</th>
              <?php $colTotal = 0; foreach ($labels_dias as $d): $sum = 0; foreach ($usuarios_labels as $u) { $sum += (float)($matriz_usuario_dia[$u][$d] ?? 0); } $colTotal += $sum; ?>
                <th class="text-end"><?= number_format($sum, 2, ',', '.') ?></th>
              <?php endforeach; ?>
              <th class="text-end"><?= number_format($colTotal, 2, ',', '.') ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="card card-glass p-3 mb-5">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="mb-0">Medições no período</h6>
      </div>
      <div class="table-responsive mt-3">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Data medição</th>
              <th>Obra</th>
              <th>Código</th>
              <th>Descrição</th>
              <th>Usuário</th>
              <th class="text-end">Valor medido</th>
              <th>Data finalização</th>
              <th class="text-end">Valor executado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($medicoes as $m): ?>
              <tr>
                <td><?= $m['data_medicao'] ? date('d/m', strtotime($m['data_medicao'])) : '' ?></td>
                <td><?= (int)$m['obra_id'] ?></td>
                <td><?= h($m['codigo']) ?></td>
                <td><?= h($m['descricao']) ?></td>
                <td><?= h($m['usuario']) ?></td>
                <td class="text-end"><?= number_format((float)$m['valor_medido'], 2, ',', '.') ?></td>
                <td><?= $m['data_finalizacao'] ? date('d/m', strtotime($m['data_finalizacao'])) : '' ?></td>
                <td class="text-end"><?= number_format((float)($m['valor_executado'] ?? 0), 2, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= h(igob_url('assets/js/filter_memory.js')) ?>"></script>
  <script>
    // Charts data
    <?php if(!$mustRequire && $canAnalyze): ?>
    const labelsDias = <?= json_encode(array_map(function($d){ $ts=strtotime($d); return $ts?date('d/m',$ts):$d; }, array_values($labels_dias)), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const dataDia = <?= json_encode(array_values($series_diaria), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const dataDiaObras = <?= json_encode(array_values($series_diaria_obras), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const labelsUsuarios = <?= json_encode(array_values($usuarios_labels), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const dataUsuarios = <?= json_encode(array_values($por_usuario), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    const dataUsuariosObras = <?= json_encode(array_values($por_usuario_obras), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function(){
      if(window.IGOBFilterMemory){
        IGOBFilterMemory.init({
          userId: <?= (int)($uid ?? ($__user['id'] ?? 0)) ?>,
          page: 'medicoes',
          formSelector: '#filterForm',
          autoApply: true,
          autoSubmit: true
        });
      }

      <?php if(!$mustRequire && $canAnalyze): ?>
      try {
        if (window.ChartDataLabels) { Chart.register(window.ChartDataLabels); }
        // Linha por dia (linha deve aparecer à frente das barras)
        const ctxDia = document.getElementById('chartDia').getContext('2d');
        new Chart(ctxDia, {
          type: 'bar',
          data: { 
            labels: labelsDias, 
            datasets: [
              // Primeiro desenha as barras…
              { type: 'bar', label: 'Obras medidas', data: dataDiaObras, yAxisID: 'y1', backgroundColor: '#ffd166', borderColor: '#b38700', order: 1,
                datalabels: { anchor: 'end', align: 'end', color: '#e2e8f0', formatter: (v)=> v? v.toLocaleString('pt-BR'): '', clamp: true } },
              // …depois a linha, garantindo que fique por cima
              { type: 'line', label: 'Valor medido', data: dataDia, yAxisID: 'y', borderColor: '#86b6f6', backgroundColor: 'rgba(134,182,246,.2)', tension: .25, fill: true, order: 999, borderWidth: 3,
                pointRadius: 3, pointHoverRadius: 4,
                datalabels: { anchor: 'end', align: 'top', color: '#e2e8f0', formatter: (v)=> v? v.toLocaleString('pt-BR'): '', clamp: true } }
            ]
          },
          options: { 
            responsive: true, maintainAspectRatio: false, 
            plugins: { 
              legend: { labels: { color: '#cbd5e1' } }, 
              datalabels: { 
                display: true,
                backgroundColor: 'rgba(12,17,31,0.85)',
                borderRadius: 6,
                padding: { top: 2, bottom: 2, left: 6, right: 6 }
              } 
            },
            scales: { 
              x: { ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' } }, 
              y: { position: 'left', ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true },
              y1: { position: 'right', ticks: { color: '#aab4cf' }, grid: { drawOnChartArea: false }, beginAtZero: true }
            }
          }
        });

        // Barras por usuário (linha por cima das barras)
        const ctxUsu = document.getElementById('chartUsuario').getContext('2d');
        new Chart(ctxUsu, {
          type: 'bar',
          data: { 
            labels: labelsUsuarios, 
            datasets: [
              { type: 'bar', label: 'Valor medido', data: dataUsuarios, yAxisID: 'y', backgroundColor: '#59a14f', order: 1,
                datalabels: { anchor: 'end', align: 'end', color: '#e2e8f0', formatter: (v)=> v? v.toLocaleString('pt-BR'): '', clamp: true } },
              { type: 'line', label: 'Obras medidas', data: dataUsuariosObras, yAxisID: 'y1', borderColor: '#f28e2b', backgroundColor: 'rgba(242,142,43,.25)', tension: .3, fill: true, order: 999, borderWidth: 3,
                pointRadius: 3, pointHoverRadius: 4,
                datalabels: { anchor: 'end', align: 'top', color: '#e2e8f0', formatter: (v)=> v? v.toLocaleString('pt-BR'): '', clamp: true } }
            ] 
          },
          options: { 
            responsive: true, maintainAspectRatio: false, 
            plugins: { 
              legend: { labels: { color: '#cbd5e1' } }, 
              datalabels: { 
                display: true,
                backgroundColor: 'rgba(12,17,31,0.85)',
                borderRadius: 6,
                padding: { top: 2, bottom: 2, left: 6, right: 6 }
              } 
            },
            scales: { 
              x: { ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' } }, 
              y: { position: 'left', ticks: { color: '#aab4cf' }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true },
              y1: { position: 'right', ticks: { color: '#aab4cf' }, grid: { drawOnChartArea: false }, beginAtZero: true }
            }
          }
        });
      } catch(e) { console.error('Erro ao montar gráficos', e); }
      <?php endif; ?>
    });
  </script>
</body>
</html>
