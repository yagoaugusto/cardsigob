<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_require_login();

global $pdo;
// Usuário atual
$__user = auth_current_user();

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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
function fetchAllAssoc(PDO $pdo, string $sql, array $params): array {
  $st = $pdo->prepare($sql);
  $st->execute(filterParams($params, $sql));
  return $st->fetchAll(PDO::FETCH_ASSOC);
}
function filterParams(array $params, string $sql): array {
  if (!$params) return [];
  if (!preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $m)) return [];
  $need = array_unique($m[1]);
  $out = [];
  foreach ($need as $name) {
    $key = ':' . $name;
    if (array_key_exists($key, $params)) {
      $out[$key] = $params[$key];
    }
  }
  return $out;
}
function fetchPairs(PDO $pdo, string $sql, array $params = []): array {
  try {
    if ($params && preg_match('/:([a-zA-Z0-9_]+)/', $sql)) {
      $st = $pdo->prepare($sql);
      $st->execute(filterParams($params, $sql));
      return $st->fetchAll(PDO::FETCH_NUM);
    }
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
$situacoes = isset($_GET['situacao']) ? array_values(array_filter((array)$_GET['situacao'])) : [];
$responsaveisSel = isset($_GET['responsavel']) ? array_values(array_filter((array)$_GET['responsavel'])) : [];
$data_ini = isset($_GET['data_ini']) && $_GET['data_ini'] !== '' ? $_GET['data_ini'] : null;
$data_fim = isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : null;
$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 100;

// Carregar lista de filiais permitidas ao usuário
$filiais = [];
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
// Interseção de filiais selecionadas com as permitidas
$allowedIds = array_map('intval', array_column($filiais, 0));
if ($filiaisSel) { $filiaisSel = array_values(array_intersect(array_map('intval',$filiaisSel), $allowedIds)); }

// Regras obrigatórias: filial e datas
$mustSelectFilial = count($filiaisSel) === 0;
$mustSelectDates = !$data_ini || !$data_fim;
$mustRequire = $mustSelectFilial || $mustSelectDates;

// Detectar colunas em programacao
$progDateCol = detect_column($pdo, 'programacao', ['data_programacao','data','data_prevista','dt_programacao','dt','data_agenda','data_conclusao']);
// Coluna padrão para indicar programação de conclusão: 'S' (sim) / 'N' (não)
$progConclusaoCol = 'previsao_finalizacao';

// Base WHERE de obras
$whereBase = ["1=1", "(o.status = 'ATIVO' OR o.status IS NULL)"];
$paramsBase = [];
if ($filiaisSel) { $filiaisSel = array_map('intval', $filiaisSel); $clause = bindInClause($pdo, 'o.filial', $filiaisSel, $paramsBase, 'f'); if ($clause) $whereBase[] = $clause; }
if ($responsaveisSel) { $responsaveisSel = array_map('intval', $responsaveisSel); $clause = bindInClause($pdo, 'o.responsavel', $responsaveisSel, $paramsBase, 'r'); if ($clause) $whereBase[] = $clause; }
if ($tipos) { $clause = bindInClause($pdo, 'o.tipo', $tipos, $paramsBase, 't'); if ($clause) $whereBase[] = $clause; }
if ($situacoes) { $clause = bindInClause($pdo, 'o.situacao', $situacoes, $paramsBase, 's'); if ($clause) $whereBase[] = $clause; }
if ($search !== '') { $whereBase[] = '(o.codigo LIKE :q1 OR o.descricao LIKE :q2)'; $paramsBase[':q1'] = '%'.$search.'%'; $paramsBase[':q2'] = '%'.$search.'%'; }

// Subconjunto: obras com programacao no período (exclui canceladas)
$periodExists = null;
// Parâmetros de período (usados somente nas consultas que referenciam :dini/:dfim)
$paramsPeriod = [];
if (!$mustRequire && $progDateCol) {
  $periodExists = "EXISTS (SELECT 1 FROM programacao p WHERE p.obra = o.id AND p.$progDateCol BETWEEN :dini AND :dfim AND p.tipo <> 'PROGRAMAÇÃO CANCELADA')";
  $paramsPeriod[':dini'] = $data_ini; $paramsPeriod[':dfim'] = $data_fim;
}

$whereWithPeriod = $whereBase;
if ($periodExists) { $whereWithPeriod[] = $periodExists; }
$whereSql = implode(' AND ', $whereWithPeriod);
$whereSqlNoPeriod = implode(' AND ', $whereBase);
// Parâmetros que acompanham whereSql (que inclui o período quando aplicável)
$paramsWhere = array_merge($paramsBase, $paramsPeriod);

// Contagem total para paginação
if (!$mustRequire) {
  $sqlCount = "SELECT COUNT(*) FROM obra o WHERE $whereSql";
  $stC = $pdo->prepare($sqlCount); $stC->execute(filterParams($paramsWhere, $sqlCount)); $total = (int)$stC->fetchColumn();
  $pages = max(1, (int)ceil($total / $per_page)); if ($page > $pages) $page = $pages; $offset = ($page-1)*$per_page;
} else { $total = 0; $pages = 1; $page = 1; $offset = 0; }

// KPIs (sobre o conjunto filtrado de obras)
if (!$mustRequire) {
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
    SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra
  ) p ON p.obra = o.id
  LEFT JOIN (
    SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra
  ) s ON s.obra = o.id";
  $stK = $pdo->prepare($sqlKpis); $stK->execute(filterParams($paramsWhere, $sqlKpis)); $k = $stK->fetch(PDO::FETCH_ASSOC) ?: [];
} else { $k = []; }
$kpi_quantidade = (int)($k['qtd'] ?? 0);
$kpi_valor_servico = (float)($k['vlr_servico'] ?? 0);
$kpi_valor_programado = (float)($k['vlr_programado'] ?? 0);
$kpi_valor_executado = (float)($k['vlr_executado'] ?? 0);
$kpi_postes_orc = (float)($k['postes_orc'] ?? 0);

// KPI extra: obras com programação de conclusão vs obras concluídas (no período)
if (!$mustRequire && $progDateCol && $progConclusaoCol) {
  // Reescrito: cria um conjunto filtrado de obras (com período aplicado apenas uma vez)
  // e computa os dois indicadores com EXISTS usando datas distintas.
  $sqlKPConc = "
    SELECT 
      SUM(
        EXISTS (
          SELECT 1 FROM programacao p
          WHERE p.obra = fo.id
            AND p.$progDateCol BETWEEN :kpi_dini1 AND :kpi_dfim1
            AND p.tipo <> 'PROGRAMAÇÃO CANCELADA'
            AND UPPER(COALESCE(p.$progConclusaoCol,'')) = 'S'
        )
      ) AS obras_com_prog_conclusao,
      SUM(
        (EXISTS (
          SELECT 1 FROM programacao p
          WHERE p.obra = fo.id
            AND p.$progDateCol BETWEEN :kpi_dini2 AND :kpi_dfim2
            AND p.tipo <> 'PROGRAMAÇÃO CANCELADA'
            AND UPPER(COALESCE(p.$progConclusaoCol,'')) = 'S'
        )) AND (UPPER(COALESCE(fo.situacao,'')) LIKE 'CONCLU%')
      ) AS obras_concluidas
    FROM (
      SELECT o.id, o.situacao
      FROM obra o
      WHERE $whereSqlNoPeriod
        AND EXISTS (
          SELECT 1 FROM programacao p0
          WHERE p0.obra = o.id
            AND p0.$progDateCol BETWEEN :kpi_bdini AND :kpi_bdfim
            AND p0.tipo <> 'PROGRAMAÇÃO CANCELADA'
        )
    ) fo";
  $stKC = $pdo->prepare($sqlKPConc);
  $paramsKC = $paramsBase;
  // Datas para derivada (conjunto base) e para os dois cálculos
  $paramsKC[':kpi_bdini'] = $data_ini; 
  $paramsKC[':kpi_bdfim'] = $data_fim;
  $paramsKC[':kpi_dini1'] = $data_ini; 
  $paramsKC[':kpi_dfim1'] = $data_fim;
  $paramsKC[':kpi_dini2'] = $data_ini; 
  $paramsKC[':kpi_dfim2'] = $data_fim;
  $stKC->execute(filterParams($paramsKC, $sqlKPConc));
  $kc = $stKC->fetch(PDO::FETCH_ASSOC) ?: ['obras_com_prog_conclusao'=>0,'obras_concluidas'=>0];
  $kpi_obras_prog_conc = (int)$kc['obras_com_prog_conclusao'];
  $kpi_obras_concluidas = (int)$kc['obras_concluidas'];
} else {
  $kpi_obras_prog_conc = 0; $kpi_obras_concluidas = 0;
}

// Agrupamentos globais
if (!$mustRequire) {
  $rows = fetchAllAssoc($pdo, "
  SELECT COALESCE(o.tipo,'—') AS grp,
         COUNT(*) AS quantidade,
         SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
         COALESCE(SUM(p.prog),0) AS valor_programado,
         COALESCE(SUM(s.exec),0) AS valor_executado
  FROM obra o
  LEFT JOIN (SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra) p ON p.obra=o.id
  LEFT JOIN (SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra) s ON s.obra=o.id
  WHERE $whereSql
  GROUP BY COALESCE(o.tipo,'—')
  ORDER BY grp", $paramsWhere);
  $grp_tipo = [];
  foreach ($rows as $r) { $grp_tipo[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

  $rows = fetchAllAssoc($pdo, "
  SELECT COALESCE(u.nome,'—') AS grp,
         COUNT(*) AS quantidade,
         SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
         COALESCE(SUM(p.prog),0) AS valor_programado,
         COALESCE(SUM(s.exec),0) AS valor_executado
  FROM obra o
  LEFT JOIN usuario u ON u.id = o.responsavel
  LEFT JOIN (SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra) p ON p.obra=o.id
  LEFT JOIN (SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra) s ON s.obra=o.id
  WHERE $whereSql
  GROUP BY COALESCE(u.nome,'—')
  ORDER BY grp", $paramsWhere);
  $grp_resp = [];
  foreach ($rows as $r) { $grp_resp[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

  $rows = fetchAllAssoc($pdo, "
  SELECT COALESCE(o.situacao,'—') AS grp,
         COUNT(*) AS quantidade,
         SUM(COALESCE(o.valor_servico,0)) AS valor_servico,
         COALESCE(SUM(p.prog),0) AS valor_programado,
         COALESCE(SUM(s.exec),0) AS valor_executado
  FROM obra o
  LEFT JOIN (SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra) p ON p.obra=o.id
  LEFT JOIN (SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra) s ON s.obra=o.id
  WHERE $whereSql
  GROUP BY COALESCE(o.situacao,'—')
  ORDER BY grp", $paramsWhere);
  $grp_sit = [];
  foreach ($rows as $r) { $grp_sit[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }

  $rows = fetchAllAssoc($pdo, "
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
  LEFT JOIN (SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra) p ON p.obra=o.id
  LEFT JOIN (SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra) s ON s.obra=o.id
  WHERE $whereSql
  GROUP BY grp
  ORDER BY FIELD(grp,'Até 10 mil','Até 40 mil','Até 100 mil','Acima de 100 mil')", $paramsWhere);
  $grp_faixa = [];
  foreach ($rows as $r) { $grp_faixa[$r['grp']] = ['quantidade'=>(int)$r['quantidade'], 'valor_servico'=>(float)$r['valor_servico'], 'valor_programado'=>(float)$r['valor_programado'], 'valor_executado'=>(float)$r['valor_executado']]; }
} else {
  $grp_tipo = $grp_resp = $grp_sit = $grp_faixa = [];
}

// Análises por responsável (iguais às da sintética), sobre o conjunto filtrado
if (!$mustRequire) {
  $rowsRespTipo = fetchAllAssoc($pdo, "
  SELECT COALESCE(u.nome,'—') AS responsavel, COALESCE(o.tipo,'—') AS tipo, COUNT(*) AS qtd
  FROM obra o LEFT JOIN usuario u ON u.id=o.responsavel
  WHERE $whereSql
  GROUP BY COALESCE(u.nome,'—'), COALESCE(o.tipo,'—')
  ORDER BY responsavel, tipo", $paramsWhere);

  $rowsRespFaixa = fetchAllAssoc($pdo, "
  SELECT COALESCE(u.nome,'—') AS responsavel,
    CASE 
      WHEN COALESCE(o.valor_servico,0) <= 10000 THEN 'Até 10 mil'
      WHEN COALESCE(o.valor_servico,0) <= 40000 THEN 'Até 40 mil'
      WHEN COALESCE(o.valor_servico,0) <= 100000 THEN 'Até 100 mil'
      ELSE 'Acima de 100 mil'
    END AS faixa,
    COUNT(*) AS qtd
  FROM obra o LEFT JOIN usuario u ON u.id=o.responsavel
  WHERE $whereSql
  GROUP BY responsavel, faixa
  ORDER BY responsavel, FIELD(faixa,'Até 10 mil','Até 40 mil','Até 100 mil','Acima de 100 mil')", $paramsWhere);
} else { $rowsRespTipo = $rowsRespFaixa = []; }

// Conclusão no período por Tipo e por Responsável
if (!$mustRequire && $progDateCol && $progConclusaoCol) {
  // Evitar duplicação de :dini/:dfim: usar where sem período e manter o filtro de data apenas na tabela programacao
  $conc_tipo = fetchAllAssoc($pdo, "
    SELECT COALESCE(o.tipo,'—') AS grp,
           COUNT(DISTINCT o.id) AS obras,
           COUNT(DISTINCT CASE WHEN UPPER(COALESCE(p.$progConclusaoCol,''))='S' THEN o.id END) AS com_prog_conc,
           COUNT(DISTINCT CASE WHEN UPPER(COALESCE(p.$progConclusaoCol,''))='S' AND UPPER(COALESCE(o.situacao,'')) LIKE 'CONCLU%' THEN o.id END) AS concluidas
    FROM obra o
    JOIN programacao p ON p.obra = o.id
    WHERE $whereSqlNoPeriod
      AND p.$progDateCol BETWEEN :dini AND :dfim
      AND p.tipo <> 'PROGRAMAÇÃO CANCELADA'
    GROUP BY COALESCE(o.tipo,'—')
    ORDER BY grp", array_merge($paramsBase, [':dini'=>$data_ini, ':dfim'=>$data_fim]));

  $conc_resp = fetchAllAssoc($pdo, "
    SELECT COALESCE(u.nome,'—') AS grp,
           COUNT(DISTINCT o.id) AS obras,
           COUNT(DISTINCT CASE WHEN UPPER(COALESCE(p.$progConclusaoCol,''))='S' THEN o.id END) AS com_prog_conc,
           COUNT(DISTINCT CASE WHEN UPPER(COALESCE(p.$progConclusaoCol,''))='S' AND UPPER(COALESCE(o.situacao,'')) LIKE 'CONCLU%' THEN o.id END) AS concluidas
    FROM obra o
    LEFT JOIN usuario u ON u.id = o.responsavel
    JOIN programacao p ON p.obra = o.id
    WHERE $whereSqlNoPeriod
      AND p.$progDateCol BETWEEN :dini AND :dfim
      AND p.tipo <> 'PROGRAMAÇÃO CANCELADA'
    GROUP BY COALESCE(u.nome,'—')
    ORDER BY grp", array_merge($paramsBase, [':dini'=>$data_ini, ':dfim'=>$data_fim]));
} else { $conc_tipo = $conc_resp = []; }

// Lista com agregados e flag de conclusão no período
if (!$mustRequire) {
  $conclFlagExpr = ($progDateCol && $progConclusaoCol)
    ? "EXISTS (SELECT 1 FROM programacao p2 WHERE p2.obra=o.id AND p2.$progDateCol BETWEEN :dini2 AND :dfim2 AND UPPER(COALESCE(p2.$progConclusaoCol,''))='S') AS concluiu_periodo,"
    : "0 AS concluiu_periodo,";
  $sqlLista = "
  SELECT 
    o.id, o.codigo, o.descricao, o.filial, o.tipo, o.situacao, o.data_entrada,
    COALESCE(o.valor_servico,0) AS valor_servico,
    COALESCE(o.poste_distribuicao,0) + COALESCE(o.poste_transmissao,0) AS postes_orc,
    u.nome AS responsavel,
    $conclFlagExpr
    COALESCE(p.prog,0) AS valor_programado,
    COALESCE(s.exec,0) AS valor_executado,
    COALESCE(pi.postes_instalados,0) AS postes_instalados
  FROM obra o
  LEFT JOIN (
    SELECT obra, SUM(financeiro) AS prog FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY obra
  ) p ON p.obra=o.id
  LEFT JOIN (
    SELECT obra, SUM(quantidade*valor) AS exec FROM programacao_servico WHERE status='EXECUTADO' GROUP BY obra
  ) s ON s.obra=o.id
  LEFT JOIN (
    SELECT ps.obra, SUM(ps.quantidade) AS postes_instalados
    FROM programacao_servico ps
    JOIN servico sv ON sv.id=ps.servico AND sv.grupo='INSTALAR POSTE'
    WHERE ps.status IN ('CONFIRMADO','EXECUTADO')
    GROUP BY ps.obra
  ) pi ON pi.obra=o.id
  LEFT JOIN usuario u ON u.id=o.responsavel
  WHERE $whereSql
  ORDER BY o.data_entrada DESC, o.id DESC
  LIMIT $per_page OFFSET $offset";
  $stL = $pdo->prepare($sqlLista);
  $paramsList = $paramsWhere;
  if ($progDateCol && $progConclusaoCol) { $paramsList[':dini2'] = $data_ini; $paramsList[':dfim2'] = $data_fim; }
  $stL->execute(filterParams($paramsList, $sqlLista)); $obras = $stL->fetchAll();
} else { $obras = []; }

// Opções para filtros: $filiais já está carregado e restrito pelo vínculo do usuário
$tipos_opts = fetchPairs($pdo, 'SELECT DISTINCT tipo, tipo FROM obra WHERE tipo IS NOT NULL AND tipo <> "" ORDER BY tipo');
$situacoes_opts = fetchPairs($pdo, 'SELECT DISTINCT situacao, situacao FROM obra WHERE situacao IS NOT NULL AND situacao <> "" ORDER BY situacao');
$responsaveis = fetchPairs($pdo, 'SELECT DISTINCT u.id, u.nome FROM obra o LEFT JOIN usuario u ON u.id = o.responsavel WHERE u.id IS NOT NULL ORDER BY u.nome');

?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Carteira de Obras Programadas - IGOB</title>
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
        <div class="col-md-3">
          <label class="form-label">Data inicial (obrigatório)</label>
          <input type="date" name="data_ini" value="<?= h($data_ini) ?>" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Data final (obrigatório)</label>
          <input type="date" name="data_fim" value="<?= h($data_fim) ?>" class="form-control" />
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
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
          <a href="?" class="btn btn-outline-light mt-3">Limpar</a>
        </div>
      </form>
    </div>

    <?php if ($mustRequire): ?>
      <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Selecione ao menos uma filial e preencha o período (data inicial e final) para exibir o relatório.
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
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-primary-subtle text-primary d-flex align-items-center justify-content-center rounded"><i class="bi bi-calendar2-check"></i></div>
            <div>
              <div class="kpi-title text-secondary">Conclusão no período</div>
              <div class="kpi-value"><?= number_format($kpi_obras_prog_conc,0,',','.') ?> / <?= number_format($kpi_obras_concluidas,0,',','.') ?></div>
              <div class="text-secondary small">Prog. conclusão / Concluídas</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php
    // Preparar dados para gráficos (somente quando filtros obrigatórios estiverem ok)
    $chartRespTipo = null; $chartRespFaixa = null;
    if (!$mustRequire) {
      // Responsável x Tipo
      $respSet = []; $tipoSet = [];
      foreach ($rowsRespTipo as $r) { $respSet[$r['responsavel']] = true; $tipoSet[$r['tipo']] = true; }
      $respLabels = array_keys($respSet); sort($respLabels, SORT_NATURAL|SORT_FLAG_CASE);
      $tipoLabels = array_keys($tipoSet); sort($tipoLabels, SORT_NATURAL|SORT_FLAG_CASE);
      $mat = [];
      foreach ($respLabels as $resp) { $mat[$resp] = array_fill_keys($tipoLabels, 0); }
      foreach ($rowsRespTipo as $r) { $mat[$r['responsavel']][$r['tipo']] = (int)$r['qtd']; }
      $palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab'];
      $datasets = []; $ci = 0; $pc = count($palette);
      foreach ($tipoLabels as $tipo) {
        $row = []; foreach ($respLabels as $resp) { $row[] = (int)$mat[$resp][$tipo]; }
        $datasets[] = ['label'=>$tipo,'data'=>$row,'backgroundColor'=>$palette[$ci % $pc]]; $ci++;
      }
      $chartRespTipo = ['labels'=>$respLabels, 'datasets'=>$datasets];

      // Responsável x Faixa
      $faixaOrder = ['Até 10 mil','Até 40 mil','Até 100 mil','Acima de 100 mil'];
      $respSet2 = []; $faixaSet = [];
      foreach ($rowsRespFaixa as $r) { $respSet2[$r['responsavel']] = true; $faixaSet[$r['faixa']] = true; }
      $respLabels2 = array_keys($respSet2); sort($respLabels2, SORT_NATURAL|SORT_FLAG_CASE);
      $faixaLabels = array_values(array_intersect($faixaOrder, array_keys($faixaSet)));
      $mat2 = [];
      foreach ($respLabels2 as $resp) { $mat2[$resp] = array_fill_keys($faixaLabels, 0); }
      foreach ($rowsRespFaixa as $r) { if (in_array($r['faixa'], $faixaLabels, true)) $mat2[$r['responsavel']][$r['faixa']] = (int)$r['qtd']; }
      $palette2 = ['#86b6f6','#7bd389','#ffd166','#ef476f'];
      $datasets2 = [];
      foreach ($faixaLabels as $i=>$faixa) {
        $row = []; foreach ($respLabels2 as $resp) { $row[] = (int)$mat2[$resp][$faixa]; }
        $datasets2[] = ['label'=>$faixa,'data'=>$row,'backgroundColor'=>$palette2[$i % count($palette2)]];
      }
      $chartRespFaixa = ['labels'=>$respLabels2, 'datasets'=>$datasets2];
    }
    ?>

    <?php if (!$mustRequire): ?>
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

    <?php
    // Preparar dados para gráficos de conclusão (por Tipo e por Responsável)
    $chartConcTipo = null; $chartConcResp = null;
    // Por Tipo
    $lblTipo = []; $obrasT=[]; $progT=[]; $concT=[];
    foreach ($conc_tipo as $r) { $lblTipo[] = $r['grp']; $obrasT[]=(int)$r['obras']; $progT[]=(int)$r['com_prog_conc']; $concT[]=(int)$r['concluidas']; }
    $chartConcTipo = [
      'labels' => $lblTipo,
      'datasets' => [
        ['label'=>'Obras','data'=>$obrasT,'backgroundColor'=>'#86b6f6'],
        ['label'=>'c/ prog. conclusão','data'=>$progT,'backgroundColor'=>'#ffd166'],
        ['label'=>'Concluídas','data'=>$concT,'backgroundColor'=>'#59a14f']
      ]
    ];
    // Por Responsável
    $lblResp = []; $obrasR=[]; $progR=[]; $concR=[];
    foreach ($conc_resp as $r) { $lblResp[] = $r['grp']; $obrasR[]=(int)$r['obras']; $progR[]=(int)$r['com_prog_conc']; $concR[]=(int)$r['concluidas']; }
    $chartConcResp = [
      'labels' => $lblResp,
      'datasets' => [
        ['label'=>'Obras','data'=>$obrasR,'backgroundColor'=>'#86b6f6'],
        ['label'=>'c/ prog. conclusão','data'=>$progR,'backgroundColor'=>'#ffd166'],
        ['label'=>'Concluídas','data'=>$concR,'backgroundColor'=>'#59a14f']
      ]
    ];
    ?>

    <!-- Gráficos: Conclusão no período -->
    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Conclusão no período por Tipo</h6>
      <div class="position-relative" style="height: clamp(260px, 38vh, 440px);">
        <canvas id="chartConcTipo"></canvas>
      </div>
    </div>

    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Conclusão no período por Responsável</h6>
      <div class="position-relative" style="height: clamp(260px, 38vh, 440px);">
        <canvas id="chartConcResp"></canvas>
      </div>
    </div>
    <?php endif; ?>

    <!-- Agrupamentos e Conclusão: ordem solicitada -->
    <!-- Linha 1: Por Tipo / Conclusão no período por Tipo -->
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
          <h6 class="mb-3">Conclusão no período por Tipo</h6>
          <table class="table table-sm align-middle text-white-50">
            <thead><tr><th>Tipo</th><th class="text-end">Obras</th><th class="text-end">c/ prog. conclusão</th><th class="text-end">Concluídas</th></tr></thead>
            <tbody>
              <?php foreach ($conc_tipo as $r): ?>
                <tr>
                  <td><?= h($r['grp']) ?></td>
                  <td class="text-end"><?= number_format((int)$r['obras'],0,',','.') ?></td>
                  <td class="text-end"><?= number_format((int)$r['com_prog_conc'],0,',','.') ?></td>
                  <td class="text-end"><?= number_format((int)$r['concluidas'],0,',','.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Linha 2: Por Responsável / Conclusão no período por Responsável -->
    <div class="row g-3 mb-4">
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
          <h6 class="mb-3">Conclusão no período por Responsável</h6>
          <table class="table table-sm align-middle text-white-50">
            <thead><tr><th>Responsável</th><th class="text-end">Obras</th><th class="text-end">c/ prog. conclusão</th><th class="text-end">Concluídas</th></tr></thead>
            <tbody>
              <?php foreach ($conc_resp as $r): ?>
                <tr>
                  <td><?= h($r['grp']) ?></td>
                  <td class="text-end"><?= number_format((int)$r['obras'],0,',','.') ?></td>
                  <td class="text-end"><?= number_format((int)$r['com_prog_conc'],0,',','.') ?></td>
                  <td class="text-end"><?= number_format((int)$r['concluidas'],0,',','.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Linha 3: Por Situação / Por Faixa de Valor de Serviço -->
    <div class="row g-3 mb-4">
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
    </div>

    <!-- Lista de obras -->
    <div class="card card-glass p-3 mb-5">
      <div class="d-flex align-items-center justify-content-between">
        <h6 class="mb-3 mb-sm-0">Obras no período</h6>
        <form method="get" class="d-flex gap-2 mb-0">
          <?php $keep = $_GET; unset($keep['q'], $keep['page']); foreach ($keep as $k=>$v) { if (is_array($v)) { foreach ($v as $vv) echo '<input type="hidden" name="'.h($k).'[]" value="'.h($vv).'">'; } else { echo '<input type="hidden" name="'.h($k).'" value="'.h($v).'">'; } } ?>
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
              <th>Conclusão no período</th>
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
                <td><?= !empty($r['concluiu_periodo']) ? '<span class="badge bg-success-subtle text-success">Sim</span>' : '<span class="badge bg-secondary-subtle text-secondary">Não</span>' ?></td>
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
              <a class="page-link" href="<?= $page<=1?'#':h('?'.http_build_query(array_merge($_GET,['page'=>1]))) ?>">«</a>
            </li>
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
              <a class="page-link" href="<?= $page<=1?'#':h('?'.http_build_query(array_merge($_GET,['page'=>$page-1]))) ?>">‹</a>
            </li>
            <?php $start = max(1,$page-2); $end = min($pages,$page+2); for($p=$start;$p<=$end;$p++): ?>
              <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="<?= h('?'.http_build_query(array_merge($_GET,['page'=>$p]))) ?>"><?= (int)$p ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
              <a class="page-link" href="<?= $page>=$pages?'#':h('?'.http_build_query(array_merge($_GET,['page'=>$page+1]))) ?>">›</a>
            </li>
            <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
              <a class="page-link" href="<?= $page>=$pages?'#':h('?'.http_build_query(array_merge($_GET,['page'=>$pages]))) ?>">»</a>
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
          var hasFilial = !!document.querySelector('input[name="filial[]"]:checked');
          var di = document.querySelector('input[name="data_ini"]').value;
          var df = document.querySelector('input[name="data_fim"]').value;
          if (!hasFilial || !di || !df) {
            ev.preventDefault(); ev.stopPropagation();
            alert('Selecione ao menos uma filial e informe data inicial e final.');
            return false;
          }
          overlay.style.display = 'flex';
        });
      }
    })();

    // Charts
    (function(){
      <?php if (!$mustRequire): ?>
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

        // Gráficos de Conclusão
        const dataConcTipo = <?= json_encode($chartConcTipo, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
        const ctx3 = document.getElementById('chartConcTipo').getContext('2d');
        // Barras sobrepostas (nested): Concluídas dentro de c/ prog. conclusão dentro de Obras
        // Estratégia: não agrupar datasets (grouped:false) e reduzir barThickness progressivamente
        const dsObrasCT = Object.assign({}, dataConcTipo.datasets?.[0] || {}, {
          label: 'Obras',
          // Sem preenchimento; apenas borda pontilhada (plano de fundo)
          backgroundColor: 'rgba(0,0,0,0)',
          borderColor: '#6ea6ef',
          borderWidth: 2,
          borderDash: [6, 4],
          borderDashOffset: 0,
          grouped: false,
          barThickness: 30,
          categoryPercentage: 1.0,
          barPercentage: 1.0,
          order: 1,
        });
        const dsProgCT = Object.assign({}, dataConcTipo.datasets?.[1] || {}, {
          label: 'c/ prog. conclusão',
          // Sem preenchimento; apenas borda pontilhada (plano intermediário)
          backgroundColor: 'rgba(0,0,0,0)',
          borderColor: '#b38700',
          borderWidth: 2,
          borderDash: [6, 4],
          borderDashOffset: 0,
          grouped: false,
          barThickness: 22,
          categoryPercentage: 1.0,
          barPercentage: 1.0,
          order: 2,
        });
        const dsConcCT = Object.assign({}, dataConcTipo.datasets?.[2] || {}, {
          label: 'Concluídas',
          // Preenchimento forte e visível (plano frontal)
          backgroundColor: '#2ecc71',
          borderColor: '#1b5e20',
          borderWidth: 1,
          grouped: false,
          barThickness: 14,
          categoryPercentage: 1.0,
          barPercentage: 1.0,
          order: 3,
        });
        new Chart(ctx3, {
          type: 'bar',
          data: {
            labels: dataConcTipo.labels || [],
            datasets: [dsObrasCT, dsProgCT, dsConcCT]
          },
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

        const dataConcResp = <?= json_encode($chartConcResp, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
        const ctx4 = document.getElementById('chartConcResp').getContext('2d');
        // Barras aninhadas (nested) também para Responsável
        const dsObrasCR = Object.assign({}, dataConcResp.datasets?.[0] || {}, {
          label: 'Obras',
          backgroundColor: 'rgba(0,0,0,0)',
          borderColor: '#6ea6ef',
          borderWidth: 2,
          borderDash: [6, 4],
          borderDashOffset: 0,
          grouped: false,
          barThickness: 30,
          categoryPercentage: 1.0,
          barPercentage: 1.0,
          order: 1,
        });
        const dsProgCR = Object.assign({}, dataConcResp.datasets?.[1] || {}, {
          label: 'c/ prog. conclusão',
          backgroundColor: 'rgba(0,0,0,0)',
          borderColor: '#b38700',
          borderWidth: 2,
          borderDash: [6, 4],
          borderDashOffset: 0,
          grouped: false,
          barThickness: 22,
          categoryPercentage: 1.0,
          barPercentage: 1.0,
          order: 2,
        });
        const dsConcCR = Object.assign({}, dataConcResp.datasets?.[2] || {}, {
          label: 'Concluídas',
          backgroundColor: '#2ecc71',
          borderColor: '#1b5e20',
          borderWidth: 1,
          grouped: false,
          barThickness: 14,
          categoryPercentage: 1.0,
          barPercentage: 1.0,
          order: 3,
        });
        new Chart(ctx4, {
          type: 'bar',
          data: {
            labels: dataConcResp.labels || [],
            datasets: [dsObrasCR, dsProgCR, dsConcCR]
          },
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
