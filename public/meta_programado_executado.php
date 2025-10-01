<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_require_login();

global $pdo;
// Usuário atual
$__user = auth_current_user();

// Helpers (copiados/adaptados das outras telas)
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function bindInClause(PDO $pdo, string $field, array $values, array &$params, string $prefix) {
  $keys = []; $i = 0;
  foreach ($values as $v) { $key = ":{$prefix}{$i}"; $keys[] = $key; $params[$key] = $v; $i++; }
  if (!$keys) return null; return "$field IN (" . implode(',', $keys) . ")";
}
function fetchAllAssoc(PDO $pdo, string $sql, array $params): array {
  $st = $pdo->prepare($sql); $st->execute(filterParams($params, $sql));
  return $st->fetchAll(PDO::FETCH_ASSOC);
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
function filterParams(array $params, string $sql): array {
  if (!$params) return [];
  if (!preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $m)) return [];
  $need = array_unique($m[1]); $out = [];
  foreach ($need as $name) { $k = ':' . $name; if (array_key_exists($k,$params)) $out[$k] = $params[$k]; }
  return $out;
}
// Condição reutilizável: considerar apenas equipes ATIVAS (se a coluna existir)
$eqActive = function(string $field) use ($pdo) {
  try {
    $has = detect_column($pdo, 'equipe', ['status']) !== null;
  } catch (Throwable $e) { $has = false; }
  return $has ? (" AND $field IN (SELECT id FROM equipe WHERE status='ATIVO')") : '';
};
// Inputs e colunas detectadas
// Filtros vindos do GET
$filiaisSel = isset($_GET['filial']) && is_array($_GET['filial'])
  ? array_values(array_filter(array_map('intval', $_GET['filial']), function($v){ return $v > 0; }))
  : [];
$data_ini = isset($_GET['data_ini']) ? trim((string)$_GET['data_ini']) : '';
$data_fim = isset($_GET['data_fim']) ? trim((string)$_GET['data_fim']) : '';
if ($data_ini && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_ini)) { $data_ini = ''; }
if ($data_fim && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_fim)) { $data_fim = ''; }
// Limitar filiais às permitidas por usuario_filial
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
if ($filiaisSel) { $filiaisSel = array_values(array_intersect($filiaisSel, $allowedIds)); }
// Reavaliar obrigatoriedade após interseção
$mustRequire = (count($filiaisSel) === 0) || ($data_ini === '') || ($data_fim === '');

// Descoberta de colunas
$equipeFilialCol = detect_column($pdo, 'equipe', ['filial','id_filial','cd_filial','filial_id']);
$equipeProcCol   = detect_column($pdo, 'equipe', ['processo','id_processo','processo_id']);
$equipeAtvCol    = detect_column($pdo, 'equipe', ['atividade','id_atividade','atividade_id']);
$equipeSupCol    = detect_column($pdo, 'equipe', ['supervisor','supervisor_id','cpf_supervisor']);
$pDateCol = detect_column($pdo, 'programacao', ['data','data_programada','dt_programada','data_prog']);
if (!$pDateCol) { $pDateCol = 'data'; }
$psDateCol = detect_column($pdo, 'programacao_servico', ['data_execucao','dt_execucao','data','data_fim','data_conclusao']);
$psEquipeCol = detect_column($pdo, 'programacao_servico', ['equipe','id_equipe','equipe_id']);

// Novos filtros: processo, atividade e supervisor
$procSel = isset($_GET['processo']) && is_array($_GET['processo']) ? array_values(array_filter(array_map('intval', $_GET['processo']), function($v){return $v>0;})) : [];
$ativSel = isset($_GET['atividade']) && is_array($_GET['atividade']) ? array_values(array_filter(array_map('intval', $_GET['atividade']), function($v){return $v>0;})) : [];
$supSel  = isset($_GET['supervisor']) && is_array($_GET['supervisor']) ? array_values(array_filter(array_map('strval', $_GET['supervisor']), function($v){return trim($v) !== '';})) : [];

// Opções para os novos filtros (somente após escolha de filial e considerando equipes ATIVAS)
$optProcessos = $optAtividades = $optSupervisores = [];
if ($equipeFilialCol && $filiaisSel) {
  $fcF = [];
  $filialEqOpt = bindInClause($pdo, 'eq.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcF, 'ff');
  // Processos
  try {
    if ($equipeProcCol) {
      $procTitleCol = detect_column($pdo, 'processo', ['titulo','nome','descricao']);
      $hasProcesso = detect_column($pdo, 'processo', ['id']) !== null;
      $joinPr = $hasProcesso ? "LEFT JOIN processo pr ON pr.id = eq.$equipeProcCol" : '';
      $titlePr = $hasProcesso ? ("COALESCE(pr.".($procTitleCol?:'id').", CONCAT('Processo ', eq.$equipeProcCol))") : "CONCAT('Processo ', eq.$equipeProcCol)";
      $sql = "SELECT DISTINCT eq.$equipeProcCol AS id, $titlePr AS titulo
              FROM equipe eq
              $joinPr
              WHERE eq.status='ATIVO' AND $filialEqOpt AND eq.$equipeProcCol IS NOT NULL
              ORDER BY 2";
      $optProcessos = fetchPairs($pdo, $sql, $fcF);
    } else { $optProcessos = []; }
  } catch (Throwable $e) { $optProcessos = []; }
  // Atividades
  try {
    if ($equipeAtvCol) {
      $atvTitleCol = detect_column($pdo, 'atividade', ['titulo','nome','descricao']);
      $hasAtividade = detect_column($pdo, 'atividade', ['id']) !== null;
      $joinAtv = $hasAtividade ? "LEFT JOIN atividade atv ON atv.id = eq.$equipeAtvCol" : '';
      $titleAtv = $hasAtividade ? ("COALESCE(atv.".($atvTitleCol?:'id').", CONCAT('Atividade ', eq.$equipeAtvCol))") : "CONCAT('Atividade ', eq.$equipeAtvCol)";
      $sql = "SELECT DISTINCT eq.$equipeAtvCol AS id, $titleAtv AS titulo
              FROM equipe eq
              $joinAtv
              WHERE eq.status='ATIVO' AND $filialEqOpt AND eq.$equipeAtvCol IS NOT NULL
              ORDER BY 2";
      $optAtividades = fetchPairs($pdo, $sql, $fcF);
    } else { $optAtividades = []; }
  } catch (Throwable $e) { $optAtividades = []; }
  // Supervisores
  try {
    if ($equipeSupCol) {
      $supNameCol = detect_column($pdo, 'folha', ['nome']);
      $hasFolha = detect_column($pdo, 'folha', ['cpf']) !== null;
      $joinFolha = $hasFolha ? "LEFT JOIN folha f ON f.cpf = eq.$equipeSupCol" : '';
      $titleSup = $hasFolha ? ("COALESCE(f.".($supNameCol?:'cpf').", eq.$equipeSupCol)") : "eq.$equipeSupCol";
      $sql = "SELECT DISTINCT eq.$equipeSupCol AS id, $titleSup AS titulo
              FROM equipe eq
              $joinFolha
              WHERE eq.status='ATIVO' AND $filialEqOpt AND eq.$equipeSupCol IS NOT NULL
              ORDER BY 2";
      $optSupervisores = fetchPairs($pdo, $sql, $fcF);
    } else { $optSupervisores = []; }
  } catch (Throwable $e) { $optSupervisores = []; }
}

// Subselect de equipes ativas + filtros extras para reaproveitar nos WHERE (respeita filiais)
$eqsetExtraSql = null; $eqsetExtraParams = [];
if (($procSel && $equipeProcCol) || ($ativSel && $equipeAtvCol) || ($supSel && $equipeSupCol)) {
  $conds = ["e.status='ATIVO'"]; $fcS = [];
  if ($equipeFilialCol && $filiaisSel) { $c = bindInClause($pdo, 'e.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcS, 'xsfil'); if ($c) $conds[] = $c; }
  if ($equipeProcCol && $procSel) { $c = bindInClause($pdo, 'e.'.$equipeProcCol, array_map('intval',$procSel), $fcS, 'xspr'); if ($c) $conds[] = $c; }
  if ($equipeAtvCol && $ativSel) { $c = bindInClause($pdo, 'e.'.$equipeAtvCol, array_map('intval',$ativSel), $fcS, 'xsat'); if ($c) $conds[] = $c; }
  if ($equipeSupCol && $supSel) { $c = bindInClause($pdo, 'e.'.$equipeSupCol, array_map('strval',$supSel), $fcS, 'xssu'); if ($c) $conds[] = $c; }
  $eqsetExtraSql = 'SELECT id FROM equipe e WHERE ' . implode(' AND ', $conds);
  $eqsetExtraParams = $fcS;
}

// Helper para colar condição de eqset extra quando houver
$eqsetCond = function(string $field) use ($eqsetExtraSql) { return $eqsetExtraSql ? (" AND $field IN ($eqsetExtraSql)") : ''; };

// Agrupamentos por atividade e por supervisor (via view v_equipes_ativas)
$grp_atividade = $grp_supervisor = [];
if (false) {
  $paramsA = array_merge($paramsBase, [
    ':eq_dini'=>$data_ini, ':eq_dfim'=>$data_fim,
    ':a1_dini'=>$data_ini, ':a1_dfim'=>$data_fim,
    ':a2_dini'=>$data_ini, ':a2_dfim'=>$data_fim,
    ':a3_dini'=>$data_ini, ':a3_dfim'=>$data_fim,
  ]);
  // Filial clause específico (para não conflitar com eqset)
  $fcA = [];
  $filialClause_a = $filiaisSel ? bindInClause($pdo, 'ee.' . $escalaFilialCol, array_map('intval',$filiaisSel), $fcA, 'fa') : null;
  $common = "FROM ($eqsetSql) e
    LEFT JOIN v_equipes_ativas v ON v.id = e.equipe
    LEFT JOIN (
      SELECT ee.equipe, SUM(ee.meta) AS meta
      FROM equipe_escala ee
      WHERE ee.data BETWEEN :a1_dini AND :a1_dfim " . ($filialClause_a?" AND $filialClause_a":"") . "
      GROUP BY ee.equipe
    ) m ON m.equipe = e.equipe
    LEFT JOIN (
      SELECT p.equipe, SUM(p.financeiro) AS prog
      FROM programacao p
      WHERE p.$pDateCol BETWEEN :a2_dini AND :a2_dfim AND p.tipo <> 'PROGRAMAÇÃO CANCELADA'
      GROUP BY p.equipe
    ) pg ON pg.equipe = e.equipe
    LEFT JOIN (
      SELECT p.equipe, SUM(ps.quantidade*ps.valor) AS exec
      FROM programacao_servico ps
      JOIN programacao p ON p.id = ps.programacao
      WHERE " . ($psDateCol?"ps.$psDateCol":"p.$pDateCol") . " BETWEEN :a3_dini AND :a3_dfim AND ps.status='EXECUTADO'
      GROUP BY p.equipe
    ) ex ON ex.equipe = e.equipe";

  $sqlAtiv = "SELECT COALESCE(v.atividade,'—') AS grp, COALESCE(SUM(m.meta),0) AS meta, COALESCE(SUM(pg.prog),0) AS programado, COALESCE(SUM(ex.exec),0) AS executado $common GROUP BY COALESCE(v.atividade,'—') ORDER BY grp";
  $grp_atividade = fetchAllAssoc($pdo, $sqlAtiv, array_merge($paramsA, $fcA));

  $sqlSup = "SELECT COALESCE(v.supervisor,'—') AS grp, COALESCE(SUM(m.meta),0) AS meta, COALESCE(SUM(pg.prog),0) AS programado, COALESCE(SUM(ex.exec),0) AS executado $common GROUP BY COALESCE(v.supervisor,'—') ORDER BY grp";
  $grp_supervisor = fetchAllAssoc($pdo, $sqlSup, array_merge($paramsA, $fcA));
}

// Série diária (desativada nesta versão)
$labels = $daily_meta = $daily_prog = $daily_exec = [];
if (false) {
  $paramsD = array_merge($paramsBase, [
    // placeholders próprios do daily (janela externa e sub-blocos)
    ':dm_dini'=>$data_ini, ':dm_dfim'=>$data_fim,
    ':dp_dini'=>$data_ini, ':dp_dfim'=>$data_fim,
    ':de_dini'=>$data_ini, ':de_dfim'=>$data_fim,
    ':dout_dini'=>$data_ini, ':dout_dfim'=>$data_fim,
    // eqset exclusivos por sub-bloco para evitar duplicidade de nome
    ':eqpg_dini'=>$data_ini, ':eqpg_dfim'=>$data_fim,
    ':eqex_dini'=>$data_ini, ':eqex_dfim'=>$data_fim,
  ]);
  // Filial clause específico no daily meta
  $fcD = [];
  $filialClause_dm = $filiaisSel ? bindInClause($pdo, 'ee.' . $escalaFilialCol, array_map('intval',$filiaisSel), $fcD, 'fdm') : null;

  // eqset exclusivos por sub-bloco (programado e executado) para não repetir :eq_* e :f* no mesmo statement
  $fcDPG = [];
  $eqFilialClause_pg = $filiaisSel ? bindInClause($pdo, 'ee.' . $escalaFilialCol, array_map('intval',$filiaisSel), $fcDPG, 'fdpg') : null;
  $eqsetSql_pg = "SELECT DISTINCT ee.equipe FROM equipe_escala ee WHERE ee.data BETWEEN :eqpg_dini AND :eqpg_dfim" . ($eqFilialClause_pg ? " AND $eqFilialClause_pg" : "");

  $fcDEX = [];
  $eqFilialClause_ex = $filiaisSel ? bindInClause($pdo, 'ee.' . $escalaFilialCol, array_map('intval',$filiaisSel), $fcDEX, 'fdex') : null;
  $eqsetSql_ex = "SELECT DISTINCT ee.equipe FROM equipe_escala ee WHERE ee.data BETWEEN :eqex_dini AND :eqex_dfim" . ($eqFilialClause_ex ? " AND $eqFilialClause_ex" : "");

  // Monta série diária conforme disponibilidade de coluna de filial na equipe
  if ($equipeFilialCol) {
    // build com JOIN equipe
    $fcDailyPG = [];
    $filialClause_daily_pg = $filiaisSel ? bindInClause($pdo, 'eqP2.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcDailyPG, 'fdpgx') : null;
    $fcDailyEX = [];
    $filialClause_daily_ex = $filiaisSel ? bindInClause($pdo, 'eqE2.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcDailyEX, 'fdexx') : null;
    $sqlDaily = "
    SELECT c.data,
           COALESCE(m.meta,0) AS meta,
           COALESCE(pg.prog,0) AS programado,
           COALESCE(ex.exec,0) AS executado
    FROM calendario c
    LEFT JOIN (
      SELECT DATE(ee.data) AS data, SUM(ee.meta) AS meta
      FROM equipe_escala ee
      WHERE DATE(ee.data) BETWEEN :dm_dini AND :dm_dfim " . ($filialClause_dm?" AND $filialClause_dm":"") . "
      GROUP BY DATE(ee.data)
    ) m ON m.data = c.data
    LEFT JOIN (
      SELECT DATE(p.$pDateCol) AS data, SUM(p.financeiro) AS prog
      FROM programacao p
      JOIN equipe eqP2 ON eqP2.id = p.equipe
      WHERE DATE(p.$pDateCol) BETWEEN :dp_dini AND :dp_dfim AND p.tipo <> 'PROGRAMAÇÃO CANCELADA' " . ($filialClause_daily_pg?" AND $filialClause_daily_pg":"") . "
      GROUP BY DATE(p.$pDateCol)
    ) pg ON pg.data = c.data
    LEFT JOIN (
      SELECT " . ($psDateCol?"DATE(ps.$psDateCol)":"DATE(p.$pDateCol)") . " AS data, SUM(ps.quantidade*ps.valor) AS exec
      FROM programacao_servico ps
      JOIN programacao p ON p.id = ps.programacao
      JOIN equipe eqE2 ON eqE2.id = p.equipe
      WHERE " . ($psDateCol?"DATE(ps.$psDateCol)":"DATE(p.$pDateCol)") . " BETWEEN :de_dini AND :de_dfim AND ps.status = 'EXECUTADO' " . ($filialClause_daily_ex?" AND $filialClause_daily_ex":"") . "
      GROUP BY " . ($psDateCol?"DATE(ps.$psDateCol)":"DATE(p.$pDateCol)") . "
    ) ex ON ex.data = c.data
    WHERE c.data BETWEEN :dout_dini AND :dout_dfim
    ORDER BY c.data";
    $rowsD = fetchAllAssoc($pdo, $sqlDaily, array_merge($paramsD, $fcD, $fcDailyPG, $fcDailyEX));
  } else {
    $sqlDaily = "
    SELECT c.data,
           COALESCE(m.meta,0) AS meta,
           COALESCE(pg.prog,0) AS programado,
           COALESCE(ex.exec,0) AS executado
    FROM calendario c
    LEFT JOIN (
      SELECT DATE(ee.data) AS data, SUM(ee.meta) AS meta
      FROM equipe_escala ee
      WHERE DATE(ee.data) BETWEEN :dm_dini AND :dm_dfim " . ($filialClause_dm?" AND $filialClause_dm":"") . "
      GROUP BY DATE(ee.data)
    ) m ON m.data = c.data
    LEFT JOIN (
      SELECT DATE(p.$pDateCol) AS data, SUM(p.financeiro) AS prog
      FROM programacao p
      WHERE DATE(p.$pDateCol) BETWEEN :dp_dini AND :dp_dfim AND p.tipo <> 'PROGRAMAÇÃO CANCELADA' AND p.equipe IN ($eqsetSql_pg)
      GROUP BY DATE(p.$pDateCol)
    ) pg ON pg.data = c.data
    LEFT JOIN (
      SELECT " . ($psDateCol?"DATE(ps.$psDateCol)":"DATE(p.$pDateCol)") . " AS data, SUM(ps.quantidade*ps.valor) AS exec
      FROM programacao_servico ps
      JOIN programacao p ON p.id = ps.programacao
      WHERE " . ($psDateCol?"DATE(ps.$psDateCol)":"DATE(p.$pDateCol)") . " BETWEEN :de_dini AND :de_dfim AND ps.status = 'EXECUTADO' AND p.equipe IN ($eqsetSql_ex)
      GROUP BY " . ($psDateCol?"DATE(ps.$psDateCol)":"DATE(p.$pDateCol)") . "
    ) ex ON ex.data = c.data
    WHERE c.data BETWEEN :dout_dini AND :dout_dfim
    ORDER BY c.data";
    $rowsD = fetchAllAssoc($pdo, $sqlDaily, array_merge($paramsD, $fcD, $fcDPG, $fcDEX));
  }
  foreach ($rowsD as $r) { $labels[] = $r['data']; $daily_meta[] = (float)$r['meta']; $daily_prog[] = (float)$r['programado']; $daily_exec[] = (float)$r['executado']; }
}

// Opções de filtro (filiais) já limitadas ao usuário em $filiais

// Nova lógica: KPI Programado Total e listagem de programações do período (filtrando por filial)
$prog_total = 0.0;
$prog_rows = [];
if (!$mustRequire) {
  $filiaisSel = array_map('intval', $filiaisSel);
  $paramsProgBase = [ ':dini' => $data_ini, ':dfim' => $data_fim ];
  $fc = [];
  // Preferência por equipe.filial; fallback para programacao.<filial>
  $filialClauseEq = ($equipeFilialCol && $filiaisSel)
    ? bindInClause($pdo, 'eq.'.$equipeFilialCol, $filiaisSel, $fc, 'fp')
    : null;
  $progFilialCol = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
  $filialClauseP  = (!$filialClauseEq && $progFilialCol && $filiaisSel)
    ? bindInClause($pdo, 'p.'.$progFilialCol, $filiaisSel, $fc, 'fpp')
    : null;

  // KPI total
  if ($filialClauseEq) {
    $sqlTot = "SELECT COALESCE(SUM(p.financeiro),0) AS total
               FROM programacao p
               JOIN equipe eq ON eq.id = p.equipe
               WHERE DATE(p.data) BETWEEN :dini AND :dfim AND $filialClauseEq" . $eqActive('p.equipe') . $eqsetCond('p.equipe');
    $rowTot = fetchAllAssoc($pdo, $sqlTot, array_merge($paramsProgBase, $fc, $eqsetExtraParams));
  } else if ($filialClauseP) {
    $sqlTot = "SELECT COALESCE(SUM(p.financeiro),0) AS total
               FROM programacao p
               WHERE DATE(p.data) BETWEEN :dini AND :dfim AND $filialClauseP" . $eqActive('p.equipe') . $eqsetCond('p.equipe');
    $rowTot = fetchAllAssoc($pdo, $sqlTot, array_merge($paramsProgBase, $fc, $eqsetExtraParams));
  } else {
    $rowTot = fetchAllAssoc($pdo, "SELECT COALESCE(SUM(p.financeiro),0) AS total FROM programacao p WHERE DATE(p.data) BETWEEN :dini AND :dfim" . $eqActive('p.equipe') . $eqsetCond('p.equipe'), array_merge($paramsProgBase, $eqsetExtraParams));
  }
  $prog_total = (float)($rowTot[0]['total'] ?? 0);

  // Listagem
  if ($filialClauseEq) {
    $sqlList = "SELECT p.id, DATE(p.data) AS data, p.financeiro, p.tipo, p.equipe,
                       COALESCE(eq.titulo, CONCAT('Equipe ', p.equipe)) AS equipe_nome
                FROM programacao p
                JOIN equipe eq ON eq.id = p.equipe
                WHERE DATE(p.data) BETWEEN :dini AND :dfim AND $filialClauseEq" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
                ORDER BY p.data, p.id";
    $prog_rows = fetchAllAssoc($pdo, $sqlList, array_merge($paramsProgBase, $fc, $eqsetExtraParams));
  } else if ($filialClauseP) {
    $sqlList = "SELECT p.id, DATE(p.data) AS data, p.financeiro, p.tipo, p.equipe,
                       COALESCE(eq.titulo, CONCAT('Equipe ', p.equipe)) AS equipe_nome
                FROM programacao p
                LEFT JOIN equipe eq ON eq.id = p.equipe
                WHERE DATE(p.data) BETWEEN :dini AND :dfim AND $filialClauseP" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
                ORDER BY p.data, p.id";
    $prog_rows = fetchAllAssoc($pdo, $sqlList, array_merge($paramsProgBase, $fc, $eqsetExtraParams));
  } else {
    $sqlList = "SELECT p.id, DATE(p.data) AS data, p.financeiro, p.tipo, p.equipe,
                       COALESCE(eq.titulo, CONCAT('Equipe ', p.equipe)) AS equipe_nome
                FROM programacao p
                LEFT JOIN equipe eq ON eq.id = p.equipe
                WHERE DATE(p.data) BETWEEN :dini AND :dfim" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
                ORDER BY p.data, p.id";
    $prog_rows = fetchAllAssoc($pdo, $sqlList, array_merge($paramsProgBase, $eqsetExtraParams));
  }
}

// Nova lógica: KPI Executado Total e listagem de serviços executados do período (filtrando por filial)
$exec_total = 0.0;
$exec_rows = [];
if (!$mustRequire) {
  $filiaisSel = array_map('intval', $filiaisSel);
  // Preferir data_execucao; se ausente, usar coluna detectada no topo ($psDateCol) e, como último recurso, a data da programação
  $execDateExpr = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(p.$pDateCol)" : "DATE(p.data)" );
  $paramsExecBase = [ ':edini' => $data_ini, ':edfim' => $data_fim ];

  // Preferência por equipe.filial; fallback para programacao.<filial>
  $fcE = [];
  $filialClauseEqE = ($equipeFilialCol && $filiaisSel)
    ? bindInClause($pdo, 'eq.'.$equipeFilialCol, $filiaisSel, $fcE, 'fex')
    : null;
  $progFilialColE = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
  $filialClausePE  = (!$filialClauseEqE && $progFilialColE && $filiaisSel)
    ? bindInClause($pdo, 'p.'.$progFilialColE, $filiaisSel, $fcE, 'fpex')
    : null;

  // KPI Executado Total
  $exec_source_info = '';
  if ($filialClauseEqE && $psEquipeCol && $equipeFilialCol && $psDateCol) {
    // Caminho direto: ps + equipe, sem depender da programacao (alinha com a consulta de teste do usuário)
    $sqlExTot = "SELECT COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS total
                 FROM programacao_servico ps
                 JOIN equipe eq ON eq.id = ps.$psEquipeCol
                 WHERE DATE(ps.$psDateCol) BETWEEN :edini AND :edfim
                   AND ps.status = 'EXECUTADO' AND $filialClauseEqE" . $eqActive("ps.$psEquipeCol") . $eqsetCond("ps.$psEquipeCol");
    $rowExTot = fetchAllAssoc($pdo, $sqlExTot, array_merge($paramsExecBase, $fcE, $eqsetExtraParams));
    $exec_source_info = 'executado:ps+equipe';
  } else if ($filialClauseEqE) {
    $joinEq = $psEquipeCol ? "JOIN equipe eq ON eq.id = ps.$psEquipeCol" : "JOIN equipe eq ON eq.id = p.equipe";
    $sqlExTot = "SELECT COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS total
                 FROM programacao_servico ps
                 JOIN programacao p ON p.id = ps.programacao
                 $joinEq
                 WHERE $execDateExpr BETWEEN :edini AND :edfim
                   AND ps.status = 'EXECUTADO' AND $filialClauseEqE" . $eqActive('p.equipe') . $eqsetCond('p.equipe');
    $rowExTot = fetchAllAssoc($pdo, $sqlExTot, array_merge($paramsExecBase, $fcE, $eqsetExtraParams));
    $exec_source_info = 'executado:ps+p+equipe';
  } else if ($filialClausePE) {
    $sqlExTot = "SELECT COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS total
                 FROM programacao_servico ps
                 JOIN programacao p ON p.id = ps.programacao
                 WHERE $execDateExpr BETWEEN :edini AND :edfim
                   AND ps.status = 'EXECUTADO' AND $filialClausePE" . $eqActive('p.equipe') . $eqsetCond('p.equipe');
    $rowExTot = fetchAllAssoc($pdo, $sqlExTot, array_merge($paramsExecBase, $fcE, $eqsetExtraParams));
    $exec_source_info = 'executado:ps+p';
  } else {
    $rowExTot = fetchAllAssoc($pdo, "SELECT COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS total
                                      FROM programacao_servico ps
                                      JOIN programacao p ON p.id = ps.programacao
                                      WHERE $execDateExpr BETWEEN :edini AND :edfim
                                        AND ps.status = 'EXECUTADO'" . $eqActive('p.equipe') . $eqsetCond('p.equipe'), array_merge($paramsExecBase, $eqsetExtraParams));
    $exec_source_info = 'executado:ps+p(s/filial)';
  }
  $exec_total = (float)($rowExTot[0]['total'] ?? 0);

  // Listagem de executados
  if ($filialClauseEqE && $psEquipeCol && $equipeFilialCol && $psDateCol) {
    // Caminho direto: ps + equipe
    $equipeIdSel = "ps.$psEquipeCol";
    $sqlExList = "SELECT ps.id,
                         DATE(ps.$psDateCol) AS data_execucao,
                         ps.quantidade, ps.valor, (COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS valor_total,
                         $equipeIdSel AS equipe, COALESCE(eq.titulo, CONCAT('Equipe ', $equipeIdSel)) AS equipe_nome
                  FROM programacao_servico ps
                  JOIN equipe eq ON eq.id = ps.$psEquipeCol
                  WHERE DATE(ps.$psDateCol) BETWEEN :edini AND :edfim
                    AND ps.status = 'EXECUTADO' AND $filialClauseEqE" . $eqActive("ps.$psEquipeCol") . $eqsetCond("ps.$psEquipeCol") . "
                  ORDER BY data_execucao, ps.id";
    $exec_rows = fetchAllAssoc($pdo, $sqlExList, array_merge($paramsExecBase, $fcE, $eqsetExtraParams));
  } else if ($filialClauseEqE) {
    $joinEq = $psEquipeCol ? "JOIN equipe eq ON eq.id = ps.$psEquipeCol" : "JOIN equipe eq ON eq.id = p.equipe";
    $equipeIdSel = $psEquipeCol ? "ps.$psEquipeCol" : "p.equipe";
    $sqlExList = "SELECT ps.id,
                         $execDateExpr AS data_execucao,
                         ps.quantidade, ps.valor, (COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS valor_total,
                         $equipeIdSel AS equipe, COALESCE(eq.titulo, CONCAT('Equipe ', $equipeIdSel)) AS equipe_nome
                  FROM programacao_servico ps
                  JOIN programacao p ON p.id = ps.programacao
                  $joinEq
                  WHERE $execDateExpr BETWEEN :edini AND :edfim
                    AND ps.status = 'EXECUTADO' AND $filialClauseEqE" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
                  ORDER BY data_execucao, ps.id";
    $exec_rows = fetchAllAssoc($pdo, $sqlExList, array_merge($paramsExecBase, $fcE, $eqsetExtraParams));
  } else if ($filialClausePE) {
    $equipeJoin = $psEquipeCol ? "LEFT JOIN equipe eq ON eq.id = ps.$psEquipeCol" : "LEFT JOIN equipe eq ON eq.id = p.equipe";
    $equipeIdSel = $psEquipeCol ? "ps.$psEquipeCol" : "p.equipe";
    $sqlExList = "SELECT ps.id,
                         $execDateExpr AS data_execucao,
                         ps.quantidade, ps.valor, (COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS valor_total,
                         $equipeIdSel AS equipe, COALESCE(eq.titulo, CONCAT('Equipe ', $equipeIdSel)) AS equipe_nome
                  FROM programacao_servico ps
                  JOIN programacao p ON p.id = ps.programacao
                  $equipeJoin
                  WHERE $execDateExpr BETWEEN :edini AND :edfim
                    AND ps.status = 'EXECUTADO' AND $filialClausePE" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
                  ORDER BY data_execucao, ps.id";
    $exec_rows = fetchAllAssoc($pdo, $sqlExList, array_merge($paramsExecBase, $fcE, $eqsetExtraParams));
  } else {
    $equipeJoin = $psEquipeCol ? "LEFT JOIN equipe eq ON eq.id = ps.$psEquipeCol" : "LEFT JOIN equipe eq ON eq.id = p.equipe";
    $equipeIdSel = $psEquipeCol ? "ps.$psEquipeCol" : "p.equipe";
    $sqlExList = "SELECT ps.id,
                         $execDateExpr AS data_execucao,
                         ps.quantidade, ps.valor, (COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS valor_total,
                         $equipeIdSel AS equipe, COALESCE(eq.titulo, CONCAT('Equipe ', $equipeIdSel)) AS equipe_nome
                  FROM programacao_servico ps
                  JOIN programacao p ON p.id = ps.programacao
                  $equipeJoin
                  WHERE $execDateExpr BETWEEN :edini AND :edfim
                    AND ps.status = 'EXECUTADO'" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
                  ORDER BY data_execucao, ps.id";
    $exec_rows = fetchAllAssoc($pdo, $sqlExList, array_merge($paramsExecBase, $eqsetExtraParams));
  }
}

// Nova lógica: KPI Meta Total (soma de equipe_escala.meta no período/filial via JOIN equipe) e listagem de metas
$meta_total = 0.0;
$meta_rows = [];
if (!$mustRequire) {
  $filiaisSel = array_map('intval', $filiaisSel);
  $paramsMetaBase = [ ':mdini' => $data_ini, ':mdfim' => $data_fim ];

  if ($equipeFilialCol && $filiaisSel) {
    // Preferir filtrar por filial via equipe (como no teste do usuário)
    $fcMJ = [];
    $filialClauseEqMeta = bindInClause($pdo, 'eq.'.$equipeFilialCol, $filiaisSel, $fcMJ, 'fmj');
    // KPI Meta
  $sqlMeta = "SELECT COALESCE(SUM(ee.meta),0) AS total
        FROM equipe_escala ee
        JOIN equipe eq ON eq.id = ee.equipe
    WHERE DATE(ee.data) BETWEEN :mdini AND :mdfim AND $filialClauseEqMeta" . $eqActive('ee.equipe') . $eqsetCond('ee.equipe');
  $rowMeta = fetchAllAssoc($pdo, $sqlMeta, array_merge($paramsMetaBase, $fcMJ, $eqsetExtraParams));
    $meta_total = (float)($rowMeta[0]['total'] ?? 0);
    // Listagem
    $sqlMetaList = "SELECT DATE(ee.data) AS data, ee.meta, ee.equipe,
            COALESCE(eq.titulo, CONCAT('Equipe ', ee.equipe)) AS equipe_nome
          FROM equipe_escala ee
          JOIN equipe eq ON eq.id = ee.equipe
      WHERE DATE(ee.data) BETWEEN :mdini AND :mdfim AND $filialClauseEqMeta" . $eqActive('ee.equipe') . $eqsetCond('ee.equipe') . "
                    ORDER BY ee.data, ee.equipe";
    $meta_rows = fetchAllAssoc($pdo, $sqlMetaList, array_merge($paramsMetaBase, $fcMJ, $eqsetExtraParams));
  } else {
    // Fallback: tentar filtrar por filial na própria equipe_escala; se não existir, só por período
    $fcM = [];
    $escalaFilialColEff = detect_column($pdo, 'equipe_escala', ['filial','id_filial','cd_filial','filial_id']);
    $filialClauseEE = ($escalaFilialColEff && $filiaisSel)
      ? bindInClause($pdo, 'ee.'.$escalaFilialColEff, $filiaisSel, $fcM, 'fm2')
      : null;
  $sqlMeta = "SELECT COALESCE(SUM(ee.meta),0) AS total
        FROM equipe_escala ee
    WHERE DATE(ee.data) BETWEEN :mdini AND :mdfim" . ($filialClauseEE ? " AND $filialClauseEE" : "") . $eqActive('ee.equipe') . $eqsetCond('ee.equipe');
  $rowMeta = fetchAllAssoc($pdo, $sqlMeta, array_merge($paramsMetaBase, $fcM, $eqsetExtraParams));
    $meta_total = (float)($rowMeta[0]['total'] ?? 0);

    $sqlMetaList = "SELECT DATE(ee.data) AS data, ee.meta, ee.equipe,
            NULL AS equipe_nome
          FROM equipe_escala ee
      WHERE DATE(ee.data) BETWEEN :mdini AND :mdfim" . ($filialClauseEE ? " AND $filialClauseEE" : "") . $eqActive('ee.equipe') . $eqsetCond('ee.equipe') . "
                    ORDER BY ee.data, ee.equipe";
    $meta_rows = fetchAllAssoc($pdo, $sqlMetaList, array_merge($paramsMetaBase, $fcM, $eqsetExtraParams));
  }
}

// Série diária (ativa) para o gráfico Meta x Programado x Executado
$chart_labels = $chart_meta_data = $chart_prog_data = $chart_exec_data = [];
if (!$mustRequire) {
  // Monta vetor de datas do período
  $start = new DateTime($data_ini);
  $end = new DateTime($data_fim);
  $end->setTime(0,0,0);
  $cursor = clone $start;
  $dateKeys = [];
  while ($cursor <= $end) {
    $k = $cursor->format('Y-m-d');
    $dateKeys[] = $k;
    $chart_labels[] = $cursor->format('d/m');
    $chart_meta_data[$k] = 0.0;
    $chart_prog_data[$k] = 0.0;
    $chart_exec_data[$k] = 0.0;
    $cursor->modify('+1 day');
  }

  // Meta diária
  try {
    if ($equipeFilialCol && $filiaisSel) {
      $fcDM = [];
      $filialClauseDM = bindInClause($pdo, 'eq.' . $equipeFilialCol, array_map('intval',$filiaisSel), $fcDM, 'gfmj');
      $rows = fetchAllAssoc($pdo,
        "SELECT DATE(ee.data) AS data, COALESCE(SUM(ee.meta),0) AS v
         FROM equipe_escala ee
         JOIN equipe eq ON eq.id = ee.equipe
         WHERE DATE(ee.data) BETWEEN :mdini AND :mdfim AND $filialClauseDM" . $eqActive('ee.equipe') . $eqsetCond('ee.equipe') . "
         GROUP BY DATE(ee.data)",
        array_merge([':mdini'=>$data_ini, ':mdfim'=>$data_fim], $fcDM, $eqsetExtraParams)
      );
    } else {
      $fcDM2 = [];
      $escalaFilialColEff = detect_column($pdo, 'equipe_escala', ['filial','id_filial','cd_filial','filial_id']);
      $filialClauseDM2 = ($escalaFilialColEff && $filiaisSel) ? bindInClause($pdo, 'ee.'.$escalaFilialColEff, array_map('intval',$filiaisSel), $fcDM2, 'gfm2') : null;
      $rows = fetchAllAssoc($pdo,
        "SELECT DATE(ee.data) AS data, COALESCE(SUM(ee.meta),0) AS v
         FROM equipe_escala ee
         WHERE DATE(ee.data) BETWEEN :mdini AND :mdfim" . ($filialClauseDM2?" AND $filialClauseDM2":"") . $eqActive('ee.equipe') . $eqsetCond('ee.equipe') . "
         GROUP BY DATE(ee.data)",
        array_merge([':mdini'=>$data_ini, ':mdfim'=>$data_fim], $fcDM2, $eqsetExtraParams)
      );
    }
    foreach ($rows as $r) { $k = (string)$r['data']; if (isset($chart_meta_data[$k])) $chart_meta_data[$k] = (float)$r['v']; }
  } catch (Throwable $e) { /* silencioso */ }

  // Programado diário
  try {
    if ($equipeFilialCol && $filiaisSel) {
      $fcDP = [];
      $filialClauseDP = bindInClause($pdo, 'eq.' . $equipeFilialCol, array_map('intval',$filiaisSel), $fcDP, 'gfpp');
      $rows = fetchAllAssoc($pdo,
        "SELECT DATE(p.$pDateCol) AS data, COALESCE(SUM(p.financeiro),0) AS v
         FROM programacao p
         JOIN equipe eq ON eq.id = p.equipe
         WHERE DATE(p.$pDateCol) BETWEEN :pdini AND :pdfim AND $filialClauseDP" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
         GROUP BY DATE(p.$pDateCol)",
        array_merge([':pdini'=>$data_ini, ':pdfim'=>$data_fim], $fcDP, $eqsetExtraParams)
      );
    } else {
      $fcDP2 = [];
      $progFilialColDaily = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
      $filialClauseDP2 = ($progFilialColDaily && $filiaisSel) ? bindInClause($pdo, 'p.'.$progFilialColDaily, array_map('intval',$filiaisSel), $fcDP2, 'gfp2') : null;
      $rows = fetchAllAssoc($pdo,
        "SELECT DATE(p.$pDateCol) AS data, COALESCE(SUM(p.financeiro),0) AS v
         FROM programacao p
         WHERE DATE(p.$pDateCol) BETWEEN :pdini AND :pdfim" . ($filialClauseDP2?" AND $filialClauseDP2":"") . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
         GROUP BY DATE(p.$pDateCol)",
        array_merge([':pdini'=>$data_ini, ':pdfim'=>$data_fim], $fcDP2, $eqsetExtraParams)
      );
    }
    foreach ($rows as $r) { $k = (string)$r['data']; if (isset($chart_prog_data[$k])) $chart_prog_data[$k] = (float)$r['v']; }
  } catch (Throwable $e) { /* silencioso */ }

  // Executado diário
  try {
    $fcDE = [];
    $filialClauseDE = ($equipeFilialCol && $filiaisSel) ? bindInClause($pdo, 'eq.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcDE, 'gfex') : null;
    $progFilialColE = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
    $filialClausePE = (!$filialClauseDE && $progFilialColE && $filiaisSel) ? bindInClause($pdo, 'p.'.$progFilialColE, array_map('intval',$filiaisSel), $fcDE, 'gfpex') : null;

    if ($filialClauseDE && $psEquipeCol && $psDateCol) {
      // Caminho direto (ps + equipe)
      $rows = fetchAllAssoc($pdo,
        "SELECT DATE(ps.$psDateCol) AS data, COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS v
         FROM programacao_servico ps
         JOIN equipe eq ON eq.id = ps.$psEquipeCol
         WHERE DATE(ps.$psDateCol) BETWEEN :edini AND :edfim AND ps.status='EXECUTADO' AND $filialClauseDE" . $eqActive("ps.$psEquipeCol") . $eqsetCond("ps.$psEquipeCol") . "
         GROUP BY DATE(ps.$psDateCol)",
        array_merge([':edini'=>$data_ini, ':edfim'=>$data_fim], $fcDE, $eqsetExtraParams)
      );
    } else if ($filialClauseDE) {
      // Via programacao para obter filial da equipe
      $dateExpr = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(p.$pDateCol)" : "DATE(p.data)" );
      $joinEq = $psEquipeCol ? "JOIN equipe eq ON eq.id = ps.$psEquipeCol" : "JOIN equipe eq ON eq.id = p.equipe";
      $rows = fetchAllAssoc($pdo,
        "SELECT $dateExpr AS data, COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS v
         FROM programacao_servico ps
         JOIN programacao p ON p.id = ps.programacao
         $joinEq
         WHERE $dateExpr BETWEEN :edini AND :edfim AND ps.status='EXECUTADO' AND $filialClauseDE" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
         GROUP BY $dateExpr",
        array_merge([':edini'=>$data_ini, ':edfim'=>$data_fim], $fcDE, $eqsetExtraParams)
      );
    } else if ($filialClausePE) {
      $dateExpr = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(p.$pDateCol)" : "DATE(p.data)" );
      $rows = fetchAllAssoc($pdo,
        "SELECT $dateExpr AS data, COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS v
         FROM programacao_servico ps
         JOIN programacao p ON p.id = ps.programacao
         WHERE $dateExpr BETWEEN :edini AND :edfim AND ps.status='EXECUTADO' AND $filialClausePE" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
         GROUP BY $dateExpr",
        array_merge([':edini'=>$data_ini, ':edfim'=>$data_fim], $fcDE, $eqsetExtraParams)
      );
    } else {
      $dateExpr = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(p.$pDateCol)" : "DATE(p.data)" );
      $rows = fetchAllAssoc($pdo,
        "SELECT $dateExpr AS data, COALESCE(SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)),0) AS v
         FROM programacao_servico ps
         JOIN programacao p ON p.id = ps.programacao
         WHERE $dateExpr BETWEEN :edini AND :edfim AND ps.status='EXECUTADO'" . $eqActive('p.equipe') . $eqsetCond('p.equipe') . "
         GROUP BY $dateExpr",
        array_merge([':edini'=>$data_ini, ':edfim'=>$data_fim], $eqsetExtraParams)
      );
    }
    foreach ($rows as $r) { $k = (string)$r['data']; if (isset($chart_exec_data[$k])) $chart_exec_data[$k] = (float)$r['v']; }
  } catch (Throwable $e) { /* silencioso */ }

  // Reorganiza para arrays indexados na ordem dos labels
  $chart_meta_data = array_map(function($k){ global $chart_meta_data; return isset($chart_meta_data[$k]) ? (float)$chart_meta_data[$k] : 0.0; }, $dateKeys);
  $chart_prog_data = array_map(function($k){ global $chart_prog_data; return isset($chart_prog_data[$k]) ? (float)$chart_prog_data[$k] : 0.0; }, $dateKeys);
  $chart_exec_data = array_map(function($k){ global $chart_exec_data; return isset($chart_exec_data[$k]) ? (float)$chart_exec_data[$k] : 0.0; }, $dateKeys);
}

?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Programado por Período - IGOB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { min-height:100vh; background: radial-gradient(circle at 10% 20%, #121827, #0b1220); }
    .nav-glass { backdrop-filter: blur(14px); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); }
    .card-glass { position:relative; overflow:hidden; backdrop-filter: blur(10px); background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); }
    .card-glass.filter-card { overflow: visible; position: relative; z-index: 1200; }
    .card-glass.filter-card .dropdown-menu { z-index: 2000; position: absolute; }
  .kpi { min-height: clamp(96px, 14vh, 140px); }
    .kpi .icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .kpi-title { font-size:1rem; font-weight:700; letter-spacing:.2px; color:#aab4cf !important; }
    .kpi-value { font-size:1.6rem; font-weight:800; }
    .badge-soft { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); }
    .loading-overlay { position:fixed; inset:0; background:rgba(10,15,28,.75); backdrop-filter: blur(6px); display:none; align-items:center; justify-content:center; z-index:1050; }
    .loading-overlay .spinner-border { width:3rem; height:3rem; }
    /* Altura confortável para o gráfico, responsiva */
    #kpiChart { width: 100% !important; height: clamp(220px, 34vh, 360px) !important; }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
        <div class="col-md-4">
          <label class="form-label">Filial (obrigatório)</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countF = count($filiaisSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countF ? ($countF.' selecionada(s)') : 'Selecione ao menos 1 filial' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:65vh; overflow:auto;">
              <?php foreach ($filiais as $f): $checked = in_array((int)$f[0], array_map('intval',$filiaisSel), true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="filial[]" value="<?= (int)$f[0] ?>" id="filial<?= (int)$f[0] ?>" <?= $checked ?>>
                  <label class="form-check-label" for="filial<?= (int)$f[0] ?>"><?= h($f[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Data inicial (obrigatório)</label>
          <input type="date" name="data_ini" value="<?= h($data_ini) ?>" class="form-control" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Data final (obrigatório)</label>
          <input type="date" name="data_fim" value="<?= h($data_fim) ?>" class="form-control" />
        </div>
        <?php if (!$mustRequire): ?>
        <div class="col-md-4">
          <label class="form-label">Processo</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countP = isset($_GET['processo']) && is_array($_GET['processo']) ? count($_GET['processo']) : 0; ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countP ? ($countP.' selecionado(s)') : 'Todos' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:60vh; overflow:auto;">
              <?php foreach ($optProcessos as $opt): $checked = in_array((int)$opt[0], array_map('intval', $procSel), true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="processo[]" value="<?= (int)$opt[0] ?>" id="proc<?= (int)$opt[0] ?>" <?= $checked ?>>
                  <label class="form-check-label" for="proc<?= (int)$opt[0] ?>"><?= h($opt[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Atividade</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countA = isset($_GET['atividade']) && is_array($_GET['atividade']) ? count($_GET['atividade']) : 0; ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countA ? ($countA.' selecionada(s)') : 'Todas' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:60vh; overflow:auto;">
              <?php foreach ($optAtividades as $opt): $checked = in_array((int)$opt[0], array_map('intval', $ativSel), true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="atividade[]" value="<?= (int)$opt[0] ?>" id="atv<?= (int)$opt[0] ?>" <?= $checked ?>>
                  <label class="form-check-label" for="atv<?= (int)$opt[0] ?>"><?= h($opt[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Supervisor</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countS = isset($_GET['supervisor']) && is_array($_GET['supervisor']) ? count($_GET['supervisor']) : 0; ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?= $countS ? ($countS.' selecionado(s)') : 'Todos' ?>
            </button>
            <div class="dropdown-menu p-3 w-100" style="max-height:60vh; overflow:auto;">
              <?php foreach ($optSupervisores as $opt): $checked = in_array((string)$opt[0], array_map('strval', $supSel), true) ? 'checked' : ''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="supervisor[]" value="<?= h($opt[0]) ?>" id="sup<?= h($opt[0]) ?>" <?= $checked ?>>
                  <label class="form-check-label" for="sup<?= h($opt[0]) ?>"><?= h($opt[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary mt-2" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
          <a href="?" class="btn btn-outline-light mt-2">Limpar</a>
        </div>
      </form>
    </div>

    <?php if (!$mustRequire): ?>
    <!-- Gráfico: Meta x Programado x Executado -->
    <div class="card card-glass p-3 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Meta x Programado x Executado</h6>
        <span class="badge badge-soft">Período: <?= h(date('d/m/Y', strtotime($data_ini))) ?> a <?= h(date('d/m/Y', strtotime($data_fim))) ?></span>
      </div>
  <canvas id="kpiChart"></canvas>
    </div>
    <?php endif; ?>

    <?php if ($mustRequire): ?>
      <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        Selecione ao menos uma filial e preencha o período (data inicial e final) para exibir o relatório.
      </div>
    <?php endif; ?>
    <!-- KPIs: Meta Total, Programado Total e Executado Total -->
    <div class="row g-3 mb-4">
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-info-subtle text-info rounded"><i class="bi bi-bullseye"></i></div>
            <div>
              <div class="kpi-title text-secondary">Meta Total</div>
              <div class="kpi-value"><?= money_br_compact($meta_total) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-warning-subtle text-warning rounded"><i class="bi bi-clipboard-check"></i></div>
            <div>
              <div class="kpi-title text-secondary">Programado Total</div>
              <div class="kpi-value"><?= money_br_compact($prog_total) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex align-items-center gap-3">
            <div class="icon bg-success-subtle text-success rounded"><i class="bi bi-check2-circle"></i></div>
            <div>
              <div class="kpi-title text-secondary">Executado Total</div>
              <div class="kpi-value"><?= money_br_compact($exec_total) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if (!$mustRequire): ?>
    <?php
  // Rankings por equipe: Exec/Meta, Prog/Meta, Exec/Prog — Top 10 e Top -10
      // Agrega por equipe no período com filtros de filial consistentes
      $rank_rows = [];
      try {
        $execDirectRK = ($psEquipeCol && $psDateCol && $equipeFilialCol);

        // Params exclusivos
        $paramsRK = [
          ':rk_m_dini'=>$data_ini, ':rk_m_dfim'=>$data_fim,
          ':rk_p_dini'=>$data_ini, ':rk_p_dfim'=>$data_fim,
          ':rk_e_dini'=>$data_ini, ':rk_e_dfim'=>$data_fim,
        ];

        // Meta por equipe
        $fcRKM = [];
        if ($equipeFilialCol && $filiaisSel) {
          $filialRKM = bindInClause($pdo, 'eqm.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcRKM, 'rkm');
          $subRKM = "SELECT ee.equipe AS equipe, SUM(ee.meta) AS meta, 0 AS programado, 0 AS executado
                      FROM equipe_escala ee
                      JOIN equipe eqm ON eqm.id = ee.equipe
                      WHERE DATE(ee.data) BETWEEN :rk_m_dini AND :rk_m_dfim AND $filialRKM" . $eqActive('ee.equipe') . "
                      GROUP BY ee.equipe";
        } else {
          $escalaFilialColEffRK = detect_column($pdo, 'equipe_escala', ['filial','id_filial','cd_filial','filial_id']);
          $filialRKM2 = ($escalaFilialColEffRK && $filiaisSel) ? bindInClause($pdo, 'ee.'.$escalaFilialColEffRK, array_map('intval',$filiaisSel), $fcRKM, 'rkm2') : null;
          $subRKM = "SELECT ee.equipe AS equipe, SUM(ee.meta) AS meta, 0 AS programado, 0 AS executado
                      FROM equipe_escala ee
                      WHERE DATE(ee.data) BETWEEN :rk_m_dini AND :rk_m_dfim" . ($filialRKM2?" AND $filialRKM2":"") . $eqActive('ee.equipe') . "
                      GROUP BY ee.equipe";
        }

        // Programado por equipe
        $fcRKP = [];
        if ($equipeFilialCol && $filiaisSel) {
          $filialRKP = bindInClause($pdo, 'eqp.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcRKP, 'rkp');
          $subRKP = "SELECT p.equipe AS equipe, 0 AS meta, SUM(p.financeiro) AS programado, 0 AS executado
                      FROM programacao p
                      JOIN equipe eqp ON eqp.id = p.equipe
                      WHERE DATE(p.$pDateCol) BETWEEN :rk_p_dini AND :rk_p_dfim AND $filialRKP" . $eqActive('p.equipe') . "
                      GROUP BY p.equipe";
        } else {
          $progFilialColRK = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
          $filialRKP2 = ($progFilialColRK && $filiaisSel) ? bindInClause($pdo, 'p.'.$progFilialColRK, array_map('intval',$filiaisSel), $fcRKP, 'rkp2') : null;
          $subRKP = "SELECT p.equipe AS equipe, 0 AS meta, SUM(p.financeiro) AS programado, 0 AS executado
                      FROM programacao p
                      WHERE DATE(p.$pDateCol) BETWEEN :rk_p_dini AND :rk_p_dfim" . ($filialRKP2?" AND $filialRKP2":"") . $eqActive('p.equipe') . "
                      GROUP BY p.equipe";
        }

        // Executado por equipe
        $fcRKE = [];
        if ($equipeFilialCol && $filiaisSel && $execDirectRK) {
          $filialRKE = bindInClause($pdo, 'eqs.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcRKE, 'rke');
     $subRKE = "SELECT ps.$psEquipeCol AS equipe, 0 AS meta, 0 AS programado,
                             SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS executado
                      FROM programacao_servico ps
                      JOIN equipe eqs ON eqs.id = ps.$psEquipeCol
       WHERE DATE(ps.$psDateCol) BETWEEN :rk_e_dini AND :rk_e_dfim AND ps.status='EXECUTADO' AND $filialRKE" . $eqActive("ps.$psEquipeCol") . "
                      GROUP BY ps.$psEquipeCol";
        } else {
          $progFilialColERK = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
          $filialRKE2 = ($progFilialColERK && $filiaisSel) ? bindInClause($pdo, 'p.'.$progFilialColERK, array_map('intval',$filiaisSel), $fcRKE, 'rke2') : null;
          $dateExprERK = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(p.$pDateCol)" : "DATE(p.data)" );
     $subRKE = "SELECT p.equipe AS equipe, 0 AS meta, 0 AS programado,
                             SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS executado
                      FROM programacao_servico ps
                      JOIN programacao p ON p.id = ps.programacao
       WHERE $dateExprERK BETWEEN :rk_e_dini AND :rk_e_dfim AND ps.status='EXECUTADO'" . ($filialRKE2?" AND $filialRKE2":"") . $eqActive('p.equipe') . "
                      GROUP BY p.equipe";
        }

        $unionRK = "$subRKM UNION ALL $subRKP UNION ALL $subRKE";
        $haveV = detect_column($pdo, 'v_equipes_ativas', ['id']) !== null;
        $haveVAtiv = $haveV && (detect_column($pdo, 'v_equipes_ativas', ['atividade']) !== null);
        $haveVSup = $haveV && (detect_column($pdo, 'v_equipes_ativas', ['supervisor']) !== null);
        $selSupRK = $haveV ? ($haveVSup?"v.supervisor":"NULL AS supervisor") : "NULL AS supervisor";
        $selAtvRK = $haveV ? ($haveVAtiv?"v.atividade":"NULL AS atividade") : "NULL AS atividade";

    $sqlRK = "SELECT t.equipe,
                          COALESCE(eq.titulo, CONCAT('Equipe ', t.equipe)) AS equipe_nome,
                          $selSupRK, $selAtvRK,
                          SUM(t.meta) AS meta,
                          SUM(t.programado) AS programado,
                          SUM(t.executado) AS executado
                   FROM ($unionRK) t
                   LEFT JOIN equipe eq ON eq.id = t.equipe
                   " . ($haveV ? "LEFT JOIN v_equipes_ativas v ON v.id = t.equipe" : "") . "
       " . ($eqsetExtraSql ? ("WHERE t.equipe IN ($eqsetExtraSql)") : "") . "
                   GROUP BY t.equipe, eq.titulo" . ($haveV && $haveVSup?", v.supervisor":"") . ($haveV && $haveVAtiv?", v.atividade":"");
  $rank_rows = fetchAllAssoc($pdo, $sqlRK, array_merge($paramsRK, $fcRKM, $fcRKP, $fcRKE, $eqsetExtraParams));
      } catch (Throwable $e) {
        $rank_rows = [];
      }

      // Helper simples para extrair rankings
      $fmt_percent = function($num) {
        if ($num === null) return '—';
        return number_format($num * 100, 1, ',', '.') . '%';
      };

      $build_rank = function(array $rows, string $numField, string $denField) {
        $arr = [];
        foreach ($rows as $r) {
          $den = (float)($r[$denField] ?? 0);
          $num = (float)($r[$numField] ?? 0);
          if ($den <= 0) continue; // evita divisão por zero e casos sem meta/programado
          $ratio = $num / $den;
          $arr[] = [
            'equipe'=> (int)$r['equipe'],
            'equipe_nome'=> (string)$r['equipe_nome'],
            'supervisor'=> $r['supervisor'] ?? null,
            'atividade'=> $r['atividade'] ?? null,
            'meta'=> (float)($r['meta'] ?? 0),
            'programado'=> (float)($r['programado'] ?? 0),
            'executado'=> (float)($r['executado'] ?? 0),
            'ratio'=> $ratio,
          ];
        }
        // Top 10 (desc)
        usort($arr, function($a,$b){ return $b['ratio'] <=> $a['ratio'] ?: ($b['executado'] <=> $a['executado']); });
        $top = array_slice($arr, 0, 10);
        // Top -10 (asc)
        usort($arr, function($a,$b){ return $a['ratio'] <=> $b['ratio'] ?: ($a['executado'] <=> $b['executado']); });
        $bottom = array_slice($arr, 0, 10);
        return [$top, $bottom];
      };

      // Monta os três rankings
      list($rk_em_top, $rk_em_bottom) = $build_rank($rank_rows, 'executado', 'meta');
      list($rk_pm_top, $rk_pm_bottom) = $build_rank($rank_rows, 'programado', 'meta');
      list($rk_ep_top, $rk_ep_bottom) = $build_rank($rank_rows, 'executado', 'programado');

      // Helper de classe de badge por faixa
      $ratio_badge_class = function(float $ratio){
        if ($ratio >= 1.0) return 'text-bg-success';
        if ($ratio >= 0.8) return 'text-bg-warning';
        return 'text-bg-danger';
      };
    ?>

    <div class="row g-3 mb-4">
      <!-- Executado vs Meta -->
      <div class="col-12">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-1">Ranking - Executado em relação à Meta</h6>
          <div class="small text-secondary mb-3">Cores: <span class="badge text-bg-success">≥ 100%</span> <span class="badge text-bg-warning">80–99%</span> <span class="badge text-bg-danger">&lt; 80%</span></div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="small text-secondary mb-2"><i class="bi bi-arrow-up-right text-success"></i> Top 10</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle text-white-50">
                  <thead><tr><th>Equipe</th><th class="text-end">% Exec/Meta</th></tr></thead>
                  <tbody>
                  <?php foreach ($rk_em_top as $r): ?>
                    <tr>
                      <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                      <td class="text-end"><?php $cls=$ratio_badge_class((float)$r['ratio']); ?><span class="badge <?= h($cls) ?>"><?= h($fmt_percent($r['ratio'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary mb-2"><i class="bi bi-arrow-down-right text-danger"></i> Top -10</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle text-white-50">
                  <thead><tr><th>Equipe</th><th class="text-end">% Exec/Meta</th></tr></thead>
                  <tbody>
                  <?php foreach ($rk_em_bottom as $r): ?>
                    <tr>
                      <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                      <td class="text-end"><?php $cls=$ratio_badge_class((float)$r['ratio']); ?><span class="badge <?= h($cls) ?>"><?= h($fmt_percent($r['ratio'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Programado vs Meta -->
      <div class="col-12">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-1">Ranking - Programado em relação à Meta</h6>
          <div class="small text-secondary mb-3">Cores: <span class="badge text-bg-success">≥ 100%</span> <span class="badge text-bg-warning">80–99%</span> <span class="badge text-bg-danger">&lt; 80%</span></div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="small text-secondary mb-2"><i class="bi bi-arrow-up-right text-success"></i> Top 10</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle text-white-50">
                  <thead><tr><th>Equipe</th><th class="text-end">% Prog/Meta</th></tr></thead>
                  <tbody>
                  <?php foreach ($rk_pm_top as $r): ?>
                    <tr>
                      <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                      <td class="text-end"><?php $cls=$ratio_badge_class((float)$r['ratio']); ?><span class="badge <?= h($cls) ?>"><?= h($fmt_percent($r['ratio'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary mb-2"><i class="bi bi-arrow-down-right text-danger"></i> Top -10</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle text-white-50">
                  <thead><tr><th>Equipe</th><th class="text-end">% Prog/Meta</th></tr></thead>
                  <tbody>
                  <?php foreach ($rk_pm_bottom as $r): ?>
                    <tr>
                      <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                      <td class="text-end"><?php $cls=$ratio_badge_class((float)$r['ratio']); ?><span class="badge <?= h($cls) ?>"><?= h($fmt_percent($r['ratio'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Executado vs Programado -->
      <div class="col-12">
        <div class="card card-glass p-3 h-100">
          <h6 class="mb-1">Ranking - Executado em relação ao Programado</h6>
          <div class="small text-secondary mb-3">Cores: <span class="badge text-bg-success">≥ 100%</span> <span class="badge text-bg-warning">80–99%</span> <span class="badge text-bg-danger">&lt; 80%</span></div>
          <div class="row g-3">
            <div class="col-md-6">
              <div class="small text-secondary mb-2"><i class="bi bi-arrow-up-right text-success"></i> Top 10</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle text-white-50">
                  <thead><tr><th>Equipe</th><th class="text-end">% Exec/Prog</th></tr></thead>
                  <tbody>
                  <?php foreach ($rk_ep_top as $r): ?>
                    <tr>
                      <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                      <td class="text-end"><?php $cls=$ratio_badge_class((float)$r['ratio']); ?><span class="badge <?= h($cls) ?>"><?= h($fmt_percent($r['ratio'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <div class="small text-secondary mb-2"><i class="bi bi-arrow-down-right text-danger"></i> Top -10</div>
              <div class="table-responsive">
                <table class="table table-sm align-middle text-white-50">
                  <thead><tr><th>Equipe</th><th class="text-end">% Exec/Prog</th></tr></thead>
                  <tbody>
                  <?php foreach ($rk_ep_bottom as $r): ?>
                    <tr>
                      <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                      <td class="text-end"><?php $cls=$ratio_badge_class((float)$r['ratio']); ?><span class="badge <?= h($cls) ?>"><?= h($fmt_percent($r['ratio'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!$mustRequire): ?>
    <?php
  // Resumo por Equipe e Listagem Unificada (Diário)
  // - Resumo: soma por equipe no período (sem coluna de data)
  // - Diário: data x equipe
  $rs_rows = [];
  $ul_rows = [];
      try {
        $haveV = detect_column($pdo, 'v_equipes_ativas', ['id']) !== null;
        $haveVAtiv = $haveV && (detect_column($pdo, 'v_equipes_ativas', ['atividade']) !== null);
        $haveVSup = $haveV && (detect_column($pdo, 'v_equipes_ativas', ['supervisor']) !== null);

        // Filtros de filial por bloco com placeholders exclusivos por subconsulta
        // Meta: keys vs agg
        $fcULMK = []; $fcULMA = [];
        $filialClauseULMK = $filialClauseULMK2 = $filialClauseULMA = $filialClauseULMA2 = null;
        $escalaFilialColEffUL = null;
        if ($equipeFilialCol && $filiaisSel) {
          $filialClauseULMK = bindInClause($pdo, 'eqm.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULMK, 'ulmk');
          $filialClauseULMA = bindInClause($pdo, 'eqmA.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULMA, 'ulma');
        } else {
          $escalaFilialColEffUL = detect_column($pdo, 'equipe_escala', ['filial','id_filial','cd_filial','filial_id']);
          if ($escalaFilialColEffUL && $filiaisSel) {
            $filialClauseULMK2 = bindInClause($pdo, 'ee.'.$escalaFilialColEffUL, array_map('intval',$filiaisSel), $fcULMK, 'ulmk2');
            $filialClauseULMA2 = bindInClause($pdo, 'eeA.'.$escalaFilialColEffUL, array_map('intval',$filiaisSel), $fcULMA, 'ulma2');
          }
        }

        // Programado: keys vs agg
        $fcULPK = []; $fcULPA = [];
        $filialClauseULPK = $filialClauseULPK2 = $filialClauseULPA = $filialClauseULPA2 = null;
        $progFilialColDailyUL = null;
        if ($equipeFilialCol && $filiaisSel) {
          $filialClauseULPK = bindInClause($pdo, 'eqp.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULPK, 'ulpk');
          $filialClauseULPA = bindInClause($pdo, 'eqpA.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULPA, 'ulpa');
        } else {
          $progFilialColDailyUL = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
          if ($progFilialColDailyUL && $filiaisSel) {
            $filialClauseULPK2 = bindInClause($pdo, 'p.'.$progFilialColDailyUL, array_map('intval',$filiaisSel), $fcULPK, 'ulpk2');
            $filialClauseULPA2 = bindInClause($pdo, 'pA.'.$progFilialColDailyUL, array_map('intval',$filiaisSel), $fcULPA, 'ulpa2');
          }
        }

        // Executado: keys vs agg; considerar caminho direto
        $execDirect = ($psEquipeCol && $psDateCol && $equipeFilialCol);
        $fcULEK = []; $fcULEA = [];
        $filialClauseULEK = $filialClauseULEK2 = $filialClauseULEA = $filialClauseULEA2 = null;
        $progFilialColEUL = null;
        if ($equipeFilialCol && $filiaisSel) {
          if ($execDirect) {
            $filialClauseULEK = bindInClause($pdo, 'eqs.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULEK, 'ulek');
            $filialClauseULEA = bindInClause($pdo, 'eqsA.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULEA, 'ulea');
          } else {
            $filialClauseULEK = bindInClause($pdo, 'eqe.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULEK, 'ulek');
            $filialClauseULEA = bindInClause($pdo, 'eqeA.'.$equipeFilialCol, array_map('intval',$filiaisSel), $fcULEA, 'ulea');
          }
        } else {
          $progFilialColEUL = detect_column($pdo, 'programacao', ['filial','id_filial','cd_filial','filial_id']);
          if ($progFilialColEUL && $filiaisSel) {
            $filialClauseULEK2 = bindInClause($pdo, 'p.'.$progFilialColEUL, array_map('intval',$filiaisSel), $fcULEK, 'ulek2');
            $filialClauseULEA2 = bindInClause($pdo, 'pA.'.$progFilialColEUL, array_map('intval',$filiaisSel), $fcULEA, 'ulea2');
          }
        }

        $paramsUL = [
          ':ul_m_dini'=>$data_ini, ':ul_m_dfim'=>$data_fim,
          ':ul_p_dini'=>$data_ini, ':ul_p_dfim'=>$data_fim,
          ':ul_e_dini'=>$data_ini, ':ul_e_dfim'=>$data_fim,
          // params dos aggs
          ':al_m_dini'=>$data_ini, ':al_m_dfim'=>$data_fim,
          ':al_p_dini'=>$data_ini, ':al_p_dfim'=>$data_fim,
          ':al_e_dini'=>$data_ini, ':al_e_dfim'=>$data_fim,
        ];

        // Subselect de chaves (data,equipe): união das três fontes
  $keysMeta = "SELECT DATE(ee.data) AS data, ee.equipe AS equipe
                      FROM equipe_escala ee
                      " . ($filialClauseULMK ? "JOIN equipe eqm ON eqm.id = ee.equipe" : "") . "
    WHERE DATE(ee.data) BETWEEN :ul_m_dini AND :ul_m_dfim" . $eqActive('ee.equipe') . "
                      " . ($filialClauseULMK ? "AND $filialClauseULMK" : ($filialClauseULMK2?"AND $filialClauseULMK2":"")) . "
                      GROUP BY DATE(ee.data), ee.equipe";

  $keysProg = "SELECT DATE(p.$pDateCol) AS data, p.equipe AS equipe
                      FROM programacao p
                      " . ($filialClauseULPK ? "JOIN equipe eqp ON eqp.id = p.equipe" : "") . "
    WHERE DATE(p.$pDateCol) BETWEEN :ul_p_dini AND :ul_p_dfim" . $eqActive('p.equipe') . "
                      " . ($filialClauseULPK ? "AND $filialClauseULPK" : ($filialClauseULPK2?"AND $filialClauseULPK2":"")) . "
                      GROUP BY DATE(p.$pDateCol), p.equipe";

        if ($execDirect) {
          $keysExec = "SELECT DATE(ps.$psDateCol) AS data, ps.$psEquipeCol AS equipe
                       FROM programacao_servico ps
                       JOIN equipe eqs ON eqs.id = ps.$psEquipeCol
                       WHERE DATE(ps.$psDateCol) BETWEEN :ul_e_dini AND :ul_e_dfim AND ps.status='EXECUTADO'" . $eqActive("ps.$psEquipeCol") . "
                       " . ($filialClauseULEK?"AND $filialClauseULEK":"") . "
                       GROUP BY DATE(ps.$psDateCol), ps.$psEquipeCol";
        } else {
          $dateExprE = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(p.$pDateCol)" : "DATE(p.data)" );
          $keysExec = "SELECT $dateExprE AS data, p.equipe AS equipe
                       FROM programacao_servico ps
                       JOIN programacao p ON p.id = ps.programacao
                       " . ($equipeFilialCol?"JOIN equipe eqe ON eqe.id = p.equipe":"") . "
                       WHERE $dateExprE BETWEEN :ul_e_dini AND :ul_e_dfim AND ps.status='EXECUTADO'" . $eqActive('p.equipe') . "
                       " . ($filialClauseULEK?"AND $filialClauseULEK":($filialClauseULEK2?"AND $filialClauseULEK2":"")) . "
                       GROUP BY $dateExprE, p.equipe";
        }

        $keysSql = "(
          $keysMeta
          UNION
          $keysProg
          UNION
          $keysExec
        ) k";

        // Subagregações (com placeholders exclusivos)
  $aggMeta = "SELECT DATE(eeA.data) AS data, eeA.equipe, SUM(eeA.meta) AS meta
                    FROM equipe_escala eeA
                    " . ($filialClauseULMA ? "JOIN equipe eqmA ON eqmA.id = eeA.equipe" : "") . "
    WHERE DATE(eeA.data) BETWEEN :al_m_dini AND :al_m_dfim" . $eqActive('eeA.equipe') . "
                    " . ($filialClauseULMA ? "AND $filialClauseULMA" : ($filialClauseULMA2?"AND $filialClauseULMA2":"")) . "
                    GROUP BY DATE(eeA.data), eeA.equipe";

  $aggProg = "SELECT DATE(pA.$pDateCol) AS data, pA.equipe, SUM(pA.financeiro) AS programado
                    FROM programacao pA
                    " . ($filialClauseULPA ? "JOIN equipe eqpA ON eqpA.id = pA.equipe" : "") . "
    WHERE DATE(pA.$pDateCol) BETWEEN :al_p_dini AND :al_p_dfim" . $eqActive('pA.equipe') . "
                    " . ($filialClauseULPA ? "AND $filialClauseULPA" : ($filialClauseULPA2?"AND $filialClauseULPA2":"")) . "
                    GROUP BY DATE(pA.$pDateCol), pA.equipe";

   if ($execDirect) {
     $aggExec = "SELECT DATE(ps.$psDateCol) AS data, ps.$psEquipeCol AS equipe,
                             SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS executado
                      FROM programacao_servico ps
                      JOIN equipe eqsA ON eqsA.id = ps.$psEquipeCol
  WHERE DATE(ps.$psDateCol) BETWEEN :al_e_dini AND :al_e_dfim AND ps.status='EXECUTADO'" . $eqActive("ps.$psEquipeCol") . "
                      " . ($filialClauseULEA?"AND $filialClauseULEA":"") . "
                      GROUP BY DATE(ps.$psDateCol), ps.$psEquipeCol";
        } else {
          $dateExprAE = $psDateCol ? "DATE(ps.$psDateCol)" : ( $pDateCol ? "DATE(pA.$pDateCol)" : "DATE(pA.data)" );
     $aggExec = "SELECT $dateExprAE AS data, pA.equipe AS equipe,
                             SUM(COALESCE(ps.quantidade,0)*COALESCE(ps.valor,0)) AS executado
                      FROM programacao_servico ps
                      JOIN programacao pA ON pA.id = ps.programacao
                      " . ($equipeFilialCol?"JOIN equipe eqeA ON eqeA.id = pA.equipe":"") . "
  WHERE $dateExprAE BETWEEN :al_e_dini AND :al_e_dfim AND ps.status='EXECUTADO'" . $eqActive('pA.equipe') . "
                      " . ($filialClauseULEA?"AND $filialClauseULEA":($filialClauseULEA2?"AND $filialClauseULEA2":"")) . "
                      GROUP BY $dateExprAE, pA.equipe";
        }

  // Preferir supervisor/atividade a partir das tabelas folha/atividade, com fallback para a view v_equipes_ativas
  $haveFolha = (detect_column($pdo, 'folha', ['cpf']) !== null) && (detect_column($pdo, 'folha', ['nome','name']) !== null);
  $haveAtividadeTbl = (detect_column($pdo, 'atividade', ['id']) !== null) && (detect_column($pdo, 'atividade', ['titulo','nome','descricao']) !== null);
  $folhaNameCol = $haveFolha ? (detect_column($pdo, 'folha', ['nome','name']) ?: 'nome') : null;
  $atvTitleCol = $haveAtividadeTbl ? (detect_column($pdo, 'atividade', ['titulo','nome','descricao']) ?: 'titulo') : null;

  $joinV = $haveV ? "LEFT JOIN v_equipes_ativas v ON v.id = k.equipe" : "";
  // Usa as colunas detectadas dinamicamente na tabela equipe; se não houver, cai no fallback via view v_equipes_ativas
  $joinSup = ($haveFolha && $equipeSupCol) ? "LEFT JOIN folha fsup ON fsup.cpf = eq.$equipeSupCol" : "";
  $joinAtv = ($haveAtividadeTbl && $equipeAtvCol) ? "LEFT JOIN atividade atv ON atv.id = eq.$equipeAtvCol" : "";

  $selSup = ($haveFolha && $equipeSupCol) ? ("fsup.".$folhaNameCol." AS supervisor") : ( $haveV ? ($haveVSup?"v.supervisor":"NULL AS supervisor") : "NULL AS supervisor" );
  $selAtv = ($haveAtividadeTbl && $equipeAtvCol) ? ("atv.".$atvTitleCol." AS atividade") : ( $haveV ? ($haveVAtiv?"v.atividade":"NULL AS atividade") : "NULL AS atividade" );

  // Resumo por equipe: derive o conjunto de equipes a partir das chaves (keysSql), garantindo alinhamento com os filtros
  $sumMeta = "SELECT s.equipe, SUM(s.meta) AS meta FROM ($aggMeta) s GROUP BY s.equipe";
  $sumProg = "SELECT s.equipe, SUM(s.programado) AS programado FROM ($aggProg) s GROUP BY s.equipe";
  $sumExec = "SELECT s.equipe, SUM(s.executado) AS executado FROM ($aggExec) s GROUP BY s.equipe";

  $teamsSql = "(SELECT DISTINCT k.equipe FROM $keysSql) t";
  $joinVSum = $haveV ? "LEFT JOIN v_equipes_ativas v ON v.id = t.equipe" : "";

   $sqlRS = "SELECT t.equipe,
           COALESCE(eq.titulo, CONCAT('Equipe ', t.equipe)) AS equipe_nome,
           $selSup,
           $selAtv,
           COALESCE(m.meta,0) AS meta,
           COALESCE(pg.programado,0) AS programado,
           COALESCE(ex.executado,0) AS executado
         FROM $teamsSql
         LEFT JOIN ($sumMeta) m ON m.equipe = t.equipe
         LEFT JOIN ($sumProg) pg ON pg.equipe = t.equipe
         LEFT JOIN ($sumExec) ex ON ex.equipe = t.equipe
         LEFT JOIN equipe eq ON eq.id = t.equipe
         $joinSup
         $joinAtv
         $joinVSum
         " . ($eqsetExtraSql ? ("WHERE t.equipe IN ($eqsetExtraSql)") : "") . "
         ORDER BY equipe_nome";

  // Importante: filtrar apenas os parâmetros usados neste SQL para evitar HY093
  $rs_rows = fetchAllAssoc($pdo, $sqlRS, array_merge($paramsUL, $fcULMK, $fcULPK, $fcULEK, $fcULMA, $fcULPA, $fcULEA, $eqsetExtraParams));
  // Diário por equipe removido conforme solicitação
      } catch (Throwable $e) {
        $rs_rows = [];
        $ul_rows = [];
      }
      // Helpers de percentuais (fallback se não definidos pelos rankings)
      if (!isset($fmt_percent)) {
        $fmt_percent = function($num) { if ($num === null) return '—'; return number_format($num * 100, 1, ',', '.') . '%'; };
      }
      if (!isset($ratio_badge_class)) {
        $ratio_badge_class = function(float $ratio){ if ($ratio >= 1.0) return 'text-bg-success'; if ($ratio >= 0.8) return 'text-bg-warning'; return 'text-bg-danger'; };
      }
    ?>

    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Resumo por Equipe</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle text-white-50">
          <thead>
            <tr>
              <th>Equipe</th>
              <th>Supervisor</th>
              <th>Atividade</th>
              <th class="text-end">Meta</th>
              <th class="text-end">Programado</th>
              <th class="text-end">% Prog/Meta</th>
              <th class="text-end">Executado</th>
              <th class="text-end">% Exec/Meta</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$rs_rows): ?>
              <tr>
                <td colspan="8" class="text-center text-secondary">Nenhum resultado para os filtros aplicados.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($rs_rows as $r): ?>
              <tr>
                <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                <td><?= h($r['supervisor'] ?? '—') ?></td>
                <td><?= h($r['atividade'] ?? '—') ?></td>
                <td class="text-end">&nbsp;<?= money_br_compact((float)$r['meta']) ?></td>
                <td class="text-end">&nbsp;<?= money_br_compact((float)$r['programado']) ?></td>
                <td class="text-end">
                  <?php $mV=(float)$r['meta']; $pV=(float)$r['programado']; $pR = $mV>0 ? ($pV/$mV) : null; ?>
                  <?php if ($pR !== null): $cls=$ratio_badge_class($pR); ?>
                    <span class="badge <?= h($cls) ?>"><?= h($fmt_percent($pR)) ?></span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-end">&nbsp;<?= money_br_compact((float)$r['executado']) ?></td>
                <td class="text-end">
                  <?php $eV=(float)$r['executado']; $eR = $mV>0 ? ($eV/$mV) : null; ?>
                  <?php if ($eR !== null): $cls=$ratio_badge_class($eR); ?>
                    <span class="badge <?= h($cls) ?>"><?= h($fmt_percent($eR)) ?></span>
                  <?php else: ?>—<?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    
    <?php endif; ?>
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
          if (!hasFilial || !di || !df) { ev.preventDefault(); ev.stopPropagation(); alert('Selecione ao menos uma filial e informe data inicial e final.'); return false; }
          overlay.style.display = 'flex';
        });
      }
      // Chart.js: linhas para Meta e Programado; barras para Executado
      <?php if (!$mustRequire): ?>
      try {
        var ctx = document.getElementById('kpiChart');
        if (ctx) {
          var labels = <?= json_encode($chart_labels) ?>;
          var metaData = <?= json_encode(array_map(function($v){ return round((float)$v,2); }, $chart_meta_data)) ?>;
          var progData = <?= json_encode(array_map(function($v){ return round((float)$v,2); }, $chart_prog_data)) ?>;
          var execData = <?= json_encode(array_map(function($v){ return round((float)$v,2); }, $chart_exec_data)) ?>;
          // Eixos em milhares para facilitar leitura no dark theme
          var chart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [
                {
                  type: 'line',
                  label: 'Meta',
                  data: metaData,
                  borderColor: 'rgba(13, 202, 240, 1)', // info
                  backgroundColor: 'rgba(13, 202, 240, 0.1)',
                  tension: 0.3,
                  borderWidth: 2,
                  pointRadius: 2,
                  yAxisID: 'y',
                },
                {
                  type: 'line',
                  label: 'Programado',
                  data: progData,
                  borderColor: 'rgba(255, 193, 7, 1)', // warning
                  backgroundColor: 'rgba(255, 193, 7, 0.1)',
                  tension: 0.3,
                  borderWidth: 2,
                  pointRadius: 2,
                  yAxisID: 'y',
                },
                {
                  type: 'bar',
                  label: 'Executado',
                  data: execData,
                  backgroundColor: 'rgba(25, 135, 84, 0.6)', // success
                  borderColor: 'rgba(25, 135, 84, 1)',
                  borderWidth: 1,
                  yAxisID: 'y',
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  grid: { color: 'rgba(255,255,255,0.06)' },
                  ticks: {
                    color: 'rgba(255,255,255,0.7)',
                    callback: function(value) {
                      // formato compacto aproximado no eixo
                      var abs = Math.abs(value);
                      if (abs >= 1_000_000) return 'R$ ' + (value/1_000_000).toFixed(1) + 'MM';
                      if (abs >= 1_000) return 'R$ ' + (value/1_000).toFixed(1) + 'M';
                      return 'R$ ' + value.toFixed(0);
                    }
                  }
                },
                x: {
                  grid: { display: false },
                  ticks: { color: 'rgba(255,255,255,0.7)' }
                }
              },
              plugins: {
                legend: {
                  labels: { color: 'rgba(255,255,255,0.85)' }
                },
                tooltip: {
                  callbacks: {
                    label: function(ctx){
                      var v = ctx.parsed.y || 0;
                      var abs = Math.abs(v);
                      var s;
                      if (abs >= 1_000_000) s = 'R$ ' + (v/1_000_000).toFixed(1) + 'MM';
                      else if (abs >= 1_000) s = 'R$ ' + (v/1_000).toFixed(1) + 'M';
                      else s = 'R$ ' + v.toFixed(2);
                      return ctx.dataset.label + ': ' + s;
                    }
                  }
                }
              }
            }
          });
        }
      } catch(e) { console.warn('Chart error', e); }
      <?php endif; ?>
    })();
  </script>
</body>
</html>
