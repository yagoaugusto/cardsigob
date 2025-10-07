<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_require_login();

global $pdo; $__user = auth_current_user();

// Helpers reutilizados (simplificados)
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function filterParams(array $params, string $sql): array {
  if (!$params) return []; if(!preg_match_all('/:([a-zA-Z0-9_]+)/',$sql,$m)) return [];
  $need=array_unique($m[1]); $out=[]; foreach($need as $n){ $k=':'.$n; if(array_key_exists($k,$params)) $out[$k]=$params[$k]; }
  return $out; }
function fetchAllAssoc(PDO $pdo, string $sql, array $params=[]): array {
  $st=$pdo->prepare($sql); $st->execute(filterParams($params,$sql)); return $st->fetchAll(PDO::FETCH_ASSOC); }
function fetchPairs(PDO $pdo, string $sql, array $params=[]): array { try { if($params && preg_match('/:([a-zA-Z0-9_]+)/',$sql)){ $st=$pdo->prepare($sql); $st->execute(filterParams($params,$sql)); return $st->fetchAll(PDO::FETCH_NUM);} return $pdo->query($sql)->fetchAll(PDO::FETCH_NUM);} catch(Throwable $e){ return []; } }
function bindInClause(PDO $pdo, string $field, array $values, array &$params, string $prefix): ?string { $keys=[]; $i=0; foreach($values as $v){ $ph=":{$prefix}{$i}"; $keys[]=$ph; $params[$ph]=$v; $i++; } return $keys? ($field.' IN('.implode(',',$keys).')'):null; }
function current_db(PDO $pdo): ?string { try { return (string)$pdo->query('SELECT DATABASE()')->fetchColumn(); } catch(Throwable $e){ return null; } }
function detect_column(PDO $pdo, string $table, array $candidates): ?string { $db=current_db($pdo); if(!$db) return null; $in=implode(',',array_map(fn($c)=>"'".str_replace("'","''",$c)."'",$candidates)); $sql="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:db AND TABLE_NAME=:t AND COLUMN_NAME IN ($in) ORDER BY FIELD(COLUMN_NAME,$in) LIMIT 1"; $st=$pdo->prepare($sql); $st->execute([':db'=>$db, ':t'=>$table]); $c=$st->fetchColumn(); return $c? (string)$c: null; }
function money_br_compact($v){ $v=(float)$v; $neg=$v<0; $abs=abs($v); if($abs>=1000000){ $n=$abs/1000000; $s=rtrim(rtrim(number_format($n,1,',',''), '0'),',').'MM'; } elseif($abs>=1000){ $n=$abs/1000; $s=rtrim(rtrim(number_format($n,1,',',''), '0'),',').'M'; } else { $s=number_format($abs,2,',','.'); } return ($neg?'- ':'').'R$ '.$s; }
function filterCluster(string $tipo): string { $t=mb_strtoupper(trim($tipo));
  $map=[
    'PROGRAMAÇÃO CANCELADA'=>'NAO_ADERENTE',
    'PARA REPROGRAMAÇÃO'=>'NAO_ADERENTE',
    'REPROGRAMADO'=>'NAO_ADERENTE',
    'PROGRAMAÇÃO NÃO ATENDIDA'=>'NAO_ADERENTE',
    'PROGRAMAÇÃO ATENDIDA'=>'ADERENTE',
    'PROGRAMAÇÃO ATENDIDA PARC'=>'ADERENTE',
    'PROGRAMAÇÃO'=>'SEM_RETORNO',
  ];
  return $map[$t] ?? 'OUTROS'; }

$eqActive = function(string $field) use ($pdo){ try { $has = detect_column($pdo,'equipe',['status']) !== null; } catch(Throwable $e){ $has=false; } return $has? (" AND $field IN (SELECT id FROM equipe WHERE status='ATIVO')"):''; };

// Filtros básicos
$filiaisSel = isset($_GET['filial']) && is_array($_GET['filial']) ? array_values(array_filter(array_map('intval',$_GET['filial']),fn($v)=>$v>0)) : [];
$data_ini = isset($_GET['data_ini'])? trim((string)$_GET['data_ini']):'';
$data_fim = isset($_GET['data_fim'])? trim((string)$_GET['data_fim']):'';
$excludeCanceladas = (isset($_GET['exclude_canceladas']) && $_GET['exclude_canceladas'] == '1');
if($data_ini && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$data_ini)) $data_ini='';
if($data_fim && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$data_fim)) $data_fim='';

// Limitar filiais às permitidas por usuario_filial
try { $uid=(int)($__user['id']??0); $ufUserCol=detect_column($pdo,'usuario_filial',['usuario','id_usuario','usuario_id','user','user_id']); $ufFilialCol=detect_column($pdo,'usuario_filial',['filial','id_filial','filial_id','cd_filial']); if($uid && $ufUserCol && $ufFilialCol){ $filiais=fetchPairs($pdo,"SELECT DISTINCT f.id, f.titulo FROM filial f JOIN usuario_filial uf ON uf.$ufFilialCol = f.id WHERE uf.$ufUserCol = :u ORDER BY f.titulo",[':u'=>$uid]); } else { $filiais=fetchPairs($pdo,'SELECT id, titulo FROM filial ORDER BY titulo'); }} catch(Throwable $e){ $filiais=fetchPairs($pdo,'SELECT id, titulo FROM filial ORDER BY titulo'); }
$allowedIds = array_map('intval', array_column($filiais,0)); if($filiaisSel) $filiaisSel = array_values(array_intersect($filiaisSel,$allowedIds));
$mustRequire = (count($filiaisSel)===0) || !$data_ini || !$data_fim;

// Colunas detectadas
$equipeFilialCol = detect_column($pdo,'equipe',['filial','id_filial','cd_filial','filial_id']);
$equipeProcCol   = detect_column($pdo,'equipe',['processo','id_processo','processo_id']);
$equipeAtvCol    = detect_column($pdo,'equipe',['atividade','id_atividade','atividade_id']);
$equipeSupCol    = detect_column($pdo,'equipe',['supervisor','supervisor_id','cpf_supervisor']);
$pDateCol        = detect_column($pdo,'programacao',['data','data_programada','dt_programada','data_prog']); if(!$pDateCol) $pDateCol='data';
$progFilialCol   = detect_column($pdo,'programacao',['filial','id_filial','cd_filial','filial_id']);

// Filtros extras
$procSel = isset($_GET['processo']) && is_array($_GET['processo']) ? array_values(array_filter(array_map('intval',$_GET['processo']),fn($v)=>$v>0)) : [];
$ativSel = isset($_GET['atividade']) && is_array($_GET['atividade']) ? array_values(array_filter(array_map('intval',$_GET['atividade']),fn($v)=>$v>0)) : [];
$supSel  = isset($_GET['supervisor']) && is_array($_GET['supervisor']) ? array_values(array_filter(array_map('strval',$_GET['supervisor']),fn($v)=>trim($v) !== '')) : [];

// Opções de selects (somente quando já escolheu filial)
$optProcessos=$optAtividades=$optSupervisores=[];
if($equipeFilialCol && $filiaisSel){
  $fcF=[]; $filialEqOpt = bindInClause($pdo,'eq.'.$equipeFilialCol,array_map('intval',$filiaisSel),$fcF,'ff');
  // Processos
  try { if($equipeProcCol){ $procTitleCol=detect_column($pdo,'processo',['titulo','nome','descricao']); $hasProcesso=detect_column($pdo,'processo',['id'])!==null; $joinPr=$hasProcesso?"LEFT JOIN processo pr ON pr.id = eq.$equipeProcCol":''; $titlePr=$hasProcesso? "COALESCE(pr.".($procTitleCol?:'id').", CONCAT('Processo ', eq.$equipeProcCol))" : "CONCAT('Processo ', eq.$equipeProcCol)"; $sql="SELECT DISTINCT eq.$equipeProcCol AS id, $titlePr AS titulo FROM equipe eq $joinPr WHERE eq.status='ATIVO' AND $filialEqOpt AND eq.$equipeProcCol IS NOT NULL ORDER BY 2"; $optProcessos = fetchPairs($pdo,$sql,$fcF);} } catch(Throwable $e){ }
  // Atividades
  try { if($equipeAtvCol){ $atvTitleCol=detect_column($pdo,'atividade',['titulo','nome','descricao']); $hasAtividade=detect_column($pdo,'atividade',['id'])!==null; $joinAtv=$hasAtividade?"LEFT JOIN atividade atv ON atv.id = eq.$equipeAtvCol":''; $titleAtv=$hasAtividade? "COALESCE(atv.".($atvTitleCol?:'id').", CONCAT('Atividade ', eq.$equipeAtvCol))" : "CONCAT('Atividade ', eq.$equipeAtvCol)"; $sql="SELECT DISTINCT eq.$equipeAtvCol AS id, $titleAtv AS titulo FROM equipe eq $joinAtv WHERE eq.status='ATIVO' AND $filialEqOpt AND eq.$equipeAtvCol IS NOT NULL ORDER BY 2"; $optAtividades = fetchPairs($pdo,$sql,$fcF);} } catch(Throwable $e){ }
  // Supervisores
  try { if($equipeSupCol){ $supNameCol=detect_column($pdo,'folha',['nome']); $hasFolha=detect_column($pdo,'folha',['cpf'])!==null; $joinFolha=$hasFolha?"LEFT JOIN folha f ON f.cpf = eq.$equipeSupCol":''; $titleSup=$hasFolha? "COALESCE(f.".($supNameCol?:'cpf').", eq.$equipeSupCol)" : "eq.$equipeSupCol"; $sql="SELECT DISTINCT eq.$equipeSupCol AS id, $titleSup AS titulo FROM equipe eq $joinFolha WHERE eq.status='ATIVO' AND $filialEqOpt AND eq.$equipeSupCol IS NOT NULL ORDER BY 2"; $optSupervisores = fetchPairs($pdo,$sql,$fcF);} } catch(Throwable $e){ }
}

// Subselect de equipes ativas + filtros extras (processo / atividade / supervisor)
$eqsetExtraSql=null; $eqsetExtraParams=[];
if(($procSel && $equipeProcCol) || ($ativSel && $equipeAtvCol) || ($supSel && $equipeSupCol)){
  $conds=["e.status='ATIVO'"]; $fcS=[]; if($equipeFilialCol && $filiaisSel){ $c=bindInClause($pdo,'e.'.$equipeFilialCol,$filiaisSel,$fcS,'xsfil'); if($c) $conds[]=$c; }
  if($equipeProcCol && $procSel){ $c=bindInClause($pdo,'e.'.$equipeProcCol,$procSel,$fcS,'xspr'); if($c) $conds[]=$c; }
  if($equipeAtvCol && $ativSel){ $c=bindInClause($pdo,'e.'.$equipeAtvCol,$ativSel,$fcS,'xsat'); if($c) $conds[]=$c; }
  if($equipeSupCol && $supSel){ $c=bindInClause($pdo,'e.'.$equipeSupCol,$supSel,$fcS,'xssu'); if($c) $conds[]=$c; }
  $eqsetExtraSql='SELECT id FROM equipe e WHERE '.implode(' AND ',$conds);
  $eqsetExtraParams=$fcS;
}
$eqsetCond = function(string $field) use ($eqsetExtraSql){ return $eqsetExtraSql? (" AND $field IN ($eqsetExtraSql)") : ''; };

// KPIs
$kpi_total=0; $kpi_aderente_qtd=0; $kpi_nao_aderente_qtd=0; $kpi_fin_aderente=0.0; $kpi_fin_nao_aderente=0.0; $kpi_percent_aderente=0.0; $rows_detalhe=[]; $agrupado=[];
// Detecção de coluna de comentário (lista expandida de candidatos)
$commentCol = detect_column(
  $pdo,
  'programacao',
  [
    // Prioridade mais alta first: campos de retorno específicos
    'comentario_retorno','retorno_comentario','motivo_retorno',
    // Demais variações genéricas
    'observacao_retorno','descricao_retorno',
    'comentario','comentarios','comentario_programacao','comentario_justificativa',
    'observacao','obs','justificativa','motivo'
  ]
);
// Arrays para motivos
$motivos_counts = []; $motivos_labels = []; $motivos_values = []; $motivos_chart_height = 260;
// Pareto financeiro (soma do campo financeiro por motivo)
$motivos_financeiro = []; $motivos_fin_labels = []; $motivos_fin_values = []; $motivos_fin_cum = [];
$debugMotivos = isset($_GET['debug_motivos']);
$motivos_debug = [];
if(!$mustRequire){
  $params=[':dini'=>$data_ini, ':dfim'=>$data_fim]; $fc=[];
  // Filtro de filial priorizando equipe
  $filialClauseEq = ($equipeFilialCol && $filiaisSel) ? bindInClause($pdo,'eq.'.$equipeFilialCol,$filiaisSel,$fc,'ff'): null;
  $filialClauseP  = (!$filialClauseEq && $progFilialCol && $filiaisSel) ? bindInClause($pdo,'p.'.$progFilialCol,$filiaisSel,$fc,'fp'): null;
  // SQL base lista
  $selectComentario = $commentCol ? ", p.$commentCol AS comentario" : "";
  if($filialClauseEq){
    $sql = "SELECT p.id, DATE(p.$pDateCol) AS data, p.tipo, p.financeiro, p.equipe, COALESCE(eq.titulo, CONCAT('Equipe ', p.equipe)) AS equipe_nome$selectComentario
            FROM programacao p
            JOIN equipe eq ON eq.id = p.equipe
            WHERE DATE(p.$pDateCol) BETWEEN :dini AND :dfim AND $filialClauseEq".($excludeCanceladas?" AND UPPER(p.tipo)<>'PROGRAMAÇÃO CANCELADA'":"").$eqActive('p.equipe').$eqsetCond('p.equipe')." ORDER BY p.$pDateCol, p.id";
    $rows_detalhe = fetchAllAssoc($pdo,$sql,array_merge($params,$fc,$eqsetExtraParams));
  } elseif($filialClauseP){
    $sql = "SELECT p.id, DATE(p.$pDateCol) AS data, p.tipo, p.financeiro, p.equipe, COALESCE(eq.titulo, CONCAT('Equipe ', p.equipe)) AS equipe_nome$selectComentario
            FROM programacao p
            LEFT JOIN equipe eq ON eq.id = p.equipe
            WHERE DATE(p.$pDateCol) BETWEEN :dini AND :dfim AND $filialClauseP".($excludeCanceladas?" AND UPPER(p.tipo)<>'PROGRAMAÇÃO CANCELADA'":"").$eqActive('p.equipe').$eqsetCond('p.equipe')." ORDER BY p.$pDateCol, p.id";
    $rows_detalhe = fetchAllAssoc($pdo,$sql,array_merge($params,$fc,$eqsetExtraParams));
  } else {
    $sql = "SELECT p.id, DATE(p.$pDateCol) AS data, p.tipo, p.financeiro, p.equipe, COALESCE(eq.titulo, CONCAT('Equipe ', p.equipe)) AS equipe_nome$selectComentario
            FROM programacao p
            LEFT JOIN equipe eq ON eq.id = p.equipe
            WHERE DATE(p.$pDateCol) BETWEEN :dini AND :dfim".($excludeCanceladas?" AND UPPER(p.tipo)<>'PROGRAMAÇÃO CANCELADA'":"").$eqActive('p.equipe').$eqsetCond('p.equipe')." ORDER BY p.$pDateCol, p.id";
    $rows_detalhe = fetchAllAssoc($pdo,$sql,array_merge($params,$eqsetExtraParams));
  }
  foreach($rows_detalhe as &$r){ $cluster = filterCluster($r['tipo'] ?? ''); $r['cluster']=$cluster; $kpi_total++; $fin=(float)($r['financeiro']??0); switch($cluster){ case 'ADERENTE': $kpi_aderente_qtd++; $kpi_fin_aderente += $fin; break; case 'NAO_ADERENTE': $kpi_nao_aderente_qtd++; $kpi_fin_nao_aderente += $fin; break; } $agrupado[$cluster]['qtd']=($agrupado[$cluster]['qtd']??0)+1; $agrupado[$cluster]['fin']=($agrupado[$cluster]['fin']??0)+$fin; }
  unset($r);
  // Extração de motivos + pareto financeiro
  if($commentCol){
    foreach($rows_detalhe as $rMot){
      if(!isset($rMot['comentario'])) continue; $cmRaw = $rMot['comentario']; $cm = trim((string)$cmRaw); if($cm==='') continue;
      $mot=''; $pattern='';
      if(preg_match('/^\[([^\[\]]{1,80})\]\s*/u',$cm,$m1)) { $mot=trim($m1[1]); $pattern='[colchetes]'; }
      else { $plusPos = mb_strpos($cm,'+'); if($plusPos!==false && $plusPos>0){ $mot=trim(mb_substr($cm,0,$plusPos)); $pattern='+'; }
        elseif(preg_match('/^([^:\-–—\|\/]{1,80})[:\-–—\|\/]\s+/u',$cm,$m2)){ $mot=trim($m2[1]); $pattern='delim'; }
        elseif(mb_strlen($cm)<=30 && str_word_count($cm)<=4){ $mot=$cm; $pattern='fallback'; }
      }
      if($mot==='') continue; $mot=preg_replace('/\s+/u',' ',$mot); $mot=trim($mot," -–—:/|\t\n\r"); if($mot==='') continue; if(mb_strlen($mot)>80) continue;
      $motivos_counts[$mot]=($motivos_counts[$mot]??0)+1; $finRow=(float)($rMot['financeiro']??0); $motivos_financeiro[$mot]=($motivos_financeiro[$mot]??0)+$finRow;
      if($debugMotivos && count($motivos_debug)<100) $motivos_debug[]=['orig'=>$cmRaw,'parsed'=>$mot,'pattern'=>$pattern];
    }
    if($motivos_counts){
      arsort($motivos_counts); $motivos_counts=array_slice($motivos_counts,0,20,true); $motivos_labels=array_keys($motivos_counts); $motivos_values=array_values($motivos_counts); $motivos_chart_height=max(260,70+count($motivos_labels)*24);
      if($motivos_financeiro){ arsort($motivos_financeiro); $motivos_financeiro=array_slice($motivos_financeiro,0,20,true); $motivos_fin_labels=array_keys($motivos_financeiro); $motivos_fin_values=array_values($motivos_financeiro); $totalFin=array_sum($motivos_fin_values)?:1; $run=0; foreach($motivos_fin_values as $v){ $run+=$v; $motivos_fin_cum[]=round($run/$totalFin*100,2);} }
    } elseif($debugMotivos){ foreach($rows_detalhe as $rDbg){ if(isset($rDbg['comentario'])){ $cmt=trim((string)$rDbg['comentario']); if($cmt!==''){ $motivos_debug[]=['orig'=>$cmt,'parsed'=>'(nenhum)','pattern'=>'n/a']; if(count($motivos_debug)>=30) break; } } } }
  }
  if($kpi_total>0) $kpi_percent_aderente = $kpi_aderente_qtd / $kpi_total;

  // Ranking aderência
  $aderencia_equipes=[]; foreach($rows_detalhe as $rRank){ $eqId=(int)$rRank['equipe']; if(!isset($aderencia_equipes[$eqId])) $aderencia_equipes[$eqId]=['equipe'=>$eqId,'equipe_nome'=>$rRank['equipe_nome']??('Equipe '.$eqId),'aderentes'=>0,'total'=>0]; $aderencia_equipes[$eqId]['total']++; if(($rRank['cluster']??'')==='ADERENTE') $aderencia_equipes[$eqId]['aderentes']++; }
  $rk_top=$rk_bottom=[]; if($aderencia_equipes){ foreach($aderencia_equipes as &$rk){ $rk['pct']=$rk['total']>0?($rk['aderentes']/$rk['total']):0; } unset($rk); $tmp=array_values($aderencia_equipes); usort($tmp,function($a,$b){ return ($b['pct']<=>$a['pct'])?:($b['aderentes']<=>$a['aderentes']); }); $rk_top=array_slice($tmp,0,10); usort($tmp,function($a,$b){ return ($a['pct']<=>$b['pct'])?:($a['aderentes']<=>$b['aderentes']); }); $rk_bottom=array_slice($tmp,0,10); }

  // --- Séries diárias e semanais por cluster para gráficos ---
  $clustersBase=['SEM_RETORNO','ADERENTE','NAO_ADERENTE'];
  $daily_labels=[]; $daily_counts=[]; $daily_pct=[]; foreach($clustersBase as $c){ $daily_counts[$c]=[]; $daily_pct[$c]=[]; }
  if($data_ini && $data_fim){
    try {
      $dtStart=new DateTime($data_ini); $dtEnd=new DateTime($data_fim); $dtEnd->setTime(0,0,0);
      $cursor=clone $dtStart; $dateKeys=[]; while($cursor <= $dtEnd){ $k=$cursor->format('Y-m-d'); $dateKeys[]=$k; $daily_labels[]=$cursor->format('d/m'); foreach($clustersBase as $c){ $daily_counts[$c][$k]=0; } $cursor->modify('+1 day'); }
      foreach($rows_detalhe as $rD){ $d=$rD['data']; $cl=$rD['cluster']; if(isset($daily_counts[$cl][$d])) $daily_counts[$cl][$d]++; }
      foreach($dateKeys as $k){ $tot=0; foreach($clustersBase as $c){ $tot+=($daily_counts[$c][$k]??0);} foreach($clustersBase as $c){ $daily_pct[$c][$k]=$tot>0?(($daily_counts[$c][$k]??0)/$tot*100):0; } }
      $weeklyAgg=[]; foreach($dateKeys as $k){ $dt=new DateTime($k); $isoYear=$dt->format('o'); $isoWeek=$dt->format('W'); $key=$isoYear.'-W'.$isoWeek; if(!isset($weeklyAgg[$key])){ $monday=clone $dt; $monday->modify('monday this week'); $sunday=clone $monday; $sunday->modify('sunday this week'); $weeklyAgg[$key]=['key'=>$key,'start'=>$monday,'end'=>$sunday]; foreach($clustersBase as $c){ $weeklyAgg[$key][$c]=0; } } foreach($clustersBase as $c){ $weeklyAgg[$key][$c]+=($daily_counts[$c][$k]??0);} }
      ksort($weeklyAgg); $weekly_labels=[]; $weekly_counts=[]; foreach($clustersBase as $c){ $weekly_counts[$c]=[]; }
      foreach($weeklyAgg as $wk){ $label='Sem '.substr($wk['key'],-2).' ('.$wk['start']->format('d/m').'–'.$wk['end']->format('d/m').')'; $weekly_labels[]=$label; foreach($clustersBase as $c){ $weekly_counts[$c][]= (int)($wk[$c]??0); } }
      foreach($clustersBase as $c){ $daily_counts[$c]=array_map(fn($dk)=>(int)($daily_counts[$c][$dk]??0),$dateKeys); $daily_pct[$c]=array_map(fn($dk)=>round(($daily_pct[$c][$dk]??0),2),$dateKeys); }
    } catch(Throwable $e){ $daily_labels=[]; $weekly_labels=[]; }
  }
}
?>
<!doctype html>
<html lang="pt-br" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Programações de Obras - IGOB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    body { min-height:100vh; background: radial-gradient(circle at 10% 20%, #121827, #0b1220); }
    .nav-glass { backdrop-filter: blur(14px); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); }
    .card-glass { position:relative; overflow:hidden; backdrop-filter: blur(10px); background:linear-gradient(145deg,rgba(255,255,255,0.05),rgba(255,255,255,0.02)); border:1px solid rgba(255,255,255,0.08); }
    .card-glass.filter-card { overflow: visible; position: relative; z-index: 1200; }
    .kpi { min-height: clamp(96px, 14vh, 140px); }
    .kpi .icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
    .kpi-title { font-size:.85rem; font-weight:600; letter-spacing:.5px; text-transform:uppercase; color:#aab4cf !important; }
    .kpi-value { font-size:1.5rem; font-weight:700; }
    .badge-soft { background: rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); }
    .table thead th { white-space:nowrap; }
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
              <?php foreach($filiais as $f): $checked = in_array((int)$f[0], $filiaisSel, true)?'checked':''; ?>
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
        <?php if(!$mustRequire): ?>
        <div class="col-md-4">
          <label class="form-label">Processo</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countP = count($procSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown"><?= $countP? ($countP.' selecionado(s)'):'Todos' ?></button>
            <div class="dropdown-menu p-3 w-100" style="max-height:60vh; overflow:auto;">
              <?php foreach($optProcessos as $opt): $chk=in_array((int)$opt[0],$procSel,true)?'checked':''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="processo[]" value="<?= (int)$opt[0] ?>" id="proc<?= (int)$opt[0] ?>" <?= $chk ?>>
                  <label class="form-check-label" for="proc<?= (int)$opt[0] ?>"><?= h($opt[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Atividade</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countA = count($ativSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown"><?= $countA? ($countA.' selecionada(s)'):'Todas' ?></button>
            <div class="dropdown-menu p-3 w-100" style="max-height:60vh; overflow:auto;">
              <?php foreach($optAtividades as $opt): $chk=in_array((int)$opt[0],$ativSel,true)?'checked':''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="atividade[]" value="<?= (int)$opt[0] ?>" id="atv<?= (int)$opt[0] ?>" <?= $chk ?>>
                  <label class="form-check-label" for="atv<?= (int)$opt[0] ?>"><?= h($opt[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Supervisor</label>
          <div class="dropdown w-100" data-bs-auto-close="outside">
            <?php $countS = count($supSel); ?>
            <button class="btn btn-outline-light w-100 text-start" type="button" data-bs-toggle="dropdown"><?= $countS? ($countS.' selecionado(s)'):'Todos' ?></button>
            <div class="dropdown-menu p-3 w-100" style="max-height:60vh; overflow:auto;">
              <?php foreach($optSupervisores as $opt): $chk=in_array((string)$opt[0],array_map('strval',$supSel),true)?'checked':''; ?>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="supervisor[]" value="<?= h($opt[0]) ?>" id="sup<?= h($opt[0]) ?>" <?= $chk ?>>
                  <label class="form-check-label" for="sup<?= h($opt[0]) ?>"><?= h($opt[1]) ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label d-block">&nbsp;</label>
          <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" id="excludeCanceladas" name="exclude_canceladas" value="1" <?= $excludeCanceladas? 'checked':''; ?>>
            <label class="form-check-label small" for="excludeCanceladas">Ocultar Programações Canceladas</label>
          </div>
        </div>
        <?php endif; ?>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary mt-2" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
          <a href="?" class="btn btn-outline-light mt-2">Limpar</a>
        </div>
      </form>
    </div>

    <?php if($mustRequire): ?>
      <div class="alert alert-warning d-flex align-items-center" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>Selecione ao menos uma filial e preencha o período (data inicial e final).</div>
    <?php endif; ?>

    <!-- KPIs Linha 1 -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex gap-3 align-items-center">
            <div class="icon bg-secondary-subtle text-secondary"><i class="bi bi-list-check"></i></div>
            <div>
              <div class="kpi-title">Qtd Programações</div>
              <div class="kpi-value"><?= h(number_format($kpi_total,0,',','.')) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex gap-3 align-items-center">
            <div class="icon bg-success-subtle text-success"><i class="bi bi-hand-thumbs-up"></i></div>
            <div>
              <div class="kpi-title">Qtd Aderentes</div>
              <div class="kpi-value"><?= h(number_format($kpi_aderente_qtd,0,',','.')) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex gap-3 align-items-center">
            <div class="icon bg-danger-subtle text-danger"><i class="bi bi-hand-thumbs-down"></i></div>
            <div>
              <div class="kpi-title">Qtd Não Aderentes</div>
              <div class="kpi-value"><?= h(number_format($kpi_nao_aderente_qtd,0,',','.')) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- KPIs Linha 2 -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex gap-3 align-items-center">
            <div class="icon bg-success-subtle text-success"><i class="bi bi-currency-dollar"></i></div>
            <div>
              <div class="kpi-title">Financeiro Aderente</div>
              <div class="kpi-value"><?= money_br_compact($kpi_fin_aderente) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex gap-3 align-items-center">
            <div class="icon bg-danger-subtle text-danger"><i class="bi bi-currency-dollar"></i></div>
            <div>
              <div class="kpi-title">Fin. Não Aderente</div>
              <div class="kpi-value"><?= money_br_compact($kpi_fin_nao_aderente) ?></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card card-glass p-3 kpi h-100">
          <div class="d-flex gap-3 align-items-center">
            <div class="icon bg-info-subtle text-info"><i class="bi bi-percent"></i></div>
            <div>
              <div class="kpi-title">% Aderente</div>
              <div class="kpi-value"><?= $kpi_total? number_format($kpi_percent_aderente*100,1,',','.') . '%':'—' ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php if(!$mustRequire): ?>
    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-1">Ranking de Aderência à Programação</h6>
  <div class="small text-secondary mb-3">Percentual = Programações Aderentes / Programações Totais da Equipe (considerando somente tipos classificados como ADERENTE). <?= $excludeCanceladas? '<span class="badge bg-info text-dark ms-2">CANCELADAS OCULTAS</span>':''; ?></div>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="small text-secondary mb-2"><i class="bi bi-arrow-up-right text-success"></i> Top 10</div>
          <div class="table-responsive">
            <table class="table table-sm align-middle text-white-50">
              <thead><tr><th>Equipe</th><th class="text-end">Aderentes</th><th class="text-end">Total</th><th class="text-end">% Ader.</th></tr></thead>
              <tbody>
              <?php if(!$rk_top): ?>
                <tr><td colspan="4" class="text-center text-secondary">Sem dados.</td></tr>
              <?php endif; ?>
              <?php foreach($rk_top as $r): $pct=$r['pct']; $cls = $pct>=0.8?'text-bg-success':($pct>=0.6?'text-bg-warning':'text-bg-danger'); ?>
                <tr>
                  <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                  <td class="text-end"><?= (int)$r['aderentes'] ?></td>
                  <td class="text-end"><?= (int)$r['total'] ?></td>
                  <td class="text-end"><span class="badge <?= h($cls) ?>"><?= number_format($pct*100,1,',','.') ?>%</span></td>
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
              <thead><tr><th>Equipe</th><th class="text-end">Aderentes</th><th class="text-end">Total</th><th class="text-end">% Ader.</th></tr></thead>
              <tbody>
              <?php if(!$rk_bottom): ?>
                <tr><td colspan="4" class="text-center text-secondary">Sem dados.</td></tr>
              <?php endif; ?>
              <?php foreach($rk_bottom as $r): $pct=$r['pct']; $cls = $pct>=0.8?'text-bg-success':($pct>=0.6?'text-bg-warning':'text-bg-danger'); ?>
                <tr>
                  <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                  <td class="text-end"><?= (int)$r['aderentes'] ?></td>
                  <td class="text-end"><?= (int)$r['total'] ?></td>
                  <td class="text-end"><span class="badge <?= h($cls) ?>"><?= number_format($pct*100,1,',','.') ?>%</span></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Resumo por Cluster</h6>
      <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
          <div class="card card-glass p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0 small">Distribuição Percentual por Dia</h6>
            </div>
            <canvas id="chartPctClusters" style="height:260px;"></canvas>
          </div>
        </div>
        <div class="col-12 col-xl-6">
          <div class="card card-glass p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0 small">Volumes (Dia vs Semana)</h6>
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-light active" id="btnViewDaily">Dia</button>
                <button type="button" class="btn btn-outline-light" id="btnViewWeekly">Semana</button>
              </div>
            </div>
            <canvas id="chartAbsClusters" style="height:260px;"></canvas>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle text-white-50">
          <thead><tr><th>Cluster</th><th class="text-end">Qtd</th><th class="text-end">Financeiro</th><th class="text-end">% sobre Total</th></tr></thead>
          <tbody>
            <?php if(!$agrupado): ?><tr><td colspan="4" class="text-center text-secondary">Sem dados.</td></tr><?php endif; ?>
            <?php foreach($agrupado as $cl=>$v): $q=$v['qtd']; $f=$v['fin']; $pct = $kpi_total? ($q/$kpi_total*100):0; ?>
              <tr>
                <td><?= h($cl) ?></td>
                <td class="text-end"><?= h(number_format($q,0,',','.')) ?></td>
                <td class="text-end"><?= money_br_compact($f) ?></td>
                <td class="text-end"><?= number_format($pct,1,',','.') ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
  <div class="small text-secondary">Observação: % Aderente considera (Aderentes / Total de Programações). <?= $excludeCanceladas? 'Programações Canceladas foram desconsideradas nesta visão.':''; ?> Caso precise excluir 'SEM_RETORNO' da base, ajustar lógica.</div>
    </div>

    <!-- Gráficos de Motivos -->
    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-2">Motivos de Comentário</h6>
      <div class="small text-secondary mb-3">Origem do motivo: trecho antes do primeiro "+" (ou delimitador) / ou padrão entre colchetes.</div>
      <?php if($debugMotivos): ?>
        <div class="alert alert-info small" style="white-space:pre-wrap; max-height:200px; overflow:auto;">
          <strong>DEBUG Comentários (coluna: <?= h($commentCol ?: 'n/d') ?>)</strong>\n
          <?php $dbgSample = array_slice($rows_detalhe,0,20); foreach($dbgSample as $dbg){ if(isset($dbg['comentario']) && trim((string)$dbg['comentario'])!==''){ echo '- '.h($dbg['comentario'])."\n"; } } ?>
          <?php if(!$dbgSample): ?>Nenhum registro carregado.<?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if(!$commentCol): ?>
        <div class="alert alert-secondary py-2 small mb-0">Coluna de comentário não detectada na tabela <code>programacao</code>.</div>
      <?php elseif(!$motivos_labels): ?>
        <div class="alert alert-secondary py-2 small mb-0">Nenhum motivo identificado para o período / filtros.</div>
      <?php else: ?>
        <div class="row g-4">
          <div class="col-12 col-xl-6">
            <div class="card card-glass p-3 h-100">
              <h6 class="small mb-2">Ocorrências (Top <?= h(count($motivos_labels)) ?>)</h6>
              <canvas id="chartMotivos" style="height: <?= (int)$motivos_chart_height ?>px;"></canvas>
            </div>
          </div>
          <div class="col-12 col-xl-6">
            <div class="card card-glass p-3 h-100">
              <h6 class="small mb-2">Pareto Financeiro (Top <?= h(count($motivos_fin_labels)) ?>)</h6>
              <canvas id="chartMotivosFin" style="height: <?= (int)$motivos_chart_height ?>px;"></canvas>
              <div class="small text-secondary mt-2">Linha indica cumulativo % do valor financeiro somado pelos motivos (escala direita).</div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="card card-glass p-3 mb-4">
      <h6 class="mb-3">Programações do Período</h6>
      <div class="table-responsive" style="max-height:60vh; overflow:auto;">
        <table class="table table-sm align-middle text-white-50">
          <thead><tr><th>ID</th><th>Data</th><th>Equipe</th><th>Tipo</th><?php if($commentCol): ?><th>Comentário</th><?php endif; ?><th>Cluster</th><th class="text-end">Financeiro</th></tr></thead>
          <tbody>
            <?php if(!$rows_detalhe): ?><tr><td colspan="6" class="text-center text-secondary">Nenhuma programação encontrada.</td></tr><?php endif; ?>
            <?php foreach($rows_detalhe as $r): ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= h(date('d/m/Y', strtotime($r['data']))) ?></td>
                <td><?= h($r['equipe_nome']) ?> (<?= (int)$r['equipe'] ?>)</td>
                <td><?= h($r['tipo']) ?></td>
                <?php if($commentCol): $cmRaw = (string)($r['comentario'] ?? ''); $cmTrim = trim($cmRaw); $cmShort = mb_strlen($cmTrim)>60? (mb_substr($cmTrim,0,57).'...') : $cmTrim; ?>
                  <td title="<?= h($cmTrim) ?>" style="max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    <?= h($cmShort) ?>
                  </td>
                <?php endif; ?>
                <td><?php $cl=$r['cluster']; $badge='secondary'; if($cl==='ADERENTE') $badge='success'; elseif($cl==='NAO_ADERENTE') $badge='danger'; elseif($cl==='SEM_RETORNO') $badge='warning text-dark'; ?>
                  <span class="badge bg-<?= h($badge) ?>"><?= h($cl) ?></span>
                </td>
                <td class="text-end"><?= money_br_compact($r['financeiro']) ?></td>
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
      var form=document.getElementById('filterForm');
      if(form){ form.addEventListener('submit', function(ev){ var hasFilial=!!document.querySelector('input[name="filial[]"]:checked'); var di=document.querySelector('input[name="data_ini"]').value; var df=document.querySelector('input[name="data_fim"]').value; if(!hasFilial||!di||!df){ ev.preventDefault(); alert('Selecione ao menos uma filial e informe data inicial e final.'); }}); }
      // Gráficos (apenas se houver dados)
      try {
        var pctLabels = <?= json_encode($daily_labels ?? []) ?>;
        var pctDataSR = <?= json_encode(($daily_pct['SEM_RETORNO'] ?? [])) ?>;
        var pctDataAD = <?= json_encode(($daily_pct['ADERENTE'] ?? [])) ?>;
        var pctDataNA = <?= json_encode(($daily_pct['NAO_ADERENTE'] ?? [])) ?>;
        if(pctLabels.length && document.getElementById('chartPctClusters')){
          new Chart(document.getElementById('chartPctClusters'), {
            type: 'line',
            data: {
              labels: pctLabels,
              datasets: [
                { label:'Sem Retorno %', data: pctDataSR, borderColor:'#ffc107', backgroundColor:'rgba(255,193,7,.15)', tension:.25, fill:true, stack:'pct' },
                { label:'Aderente %', data: pctDataAD, borderColor:'#198754', backgroundColor:'rgba(25,135,84,.25)', tension:.25, fill:true, stack:'pct' },
                { label:'Não Aderente %', data: pctDataNA, borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,.25)', tension:.25, fill:true, stack:'pct' }
              ]
            },
            options:{
              responsive:true,
              interaction:{mode:'index', intersect:false},
              stacked:true,
              plugins:{
                legend:{ labels:{ color:'rgba(255,255,255,0.85)' } },
                tooltip:{ callbacks:{ label:(ctx)=> ctx.dataset.label+': '+ctx.parsed.y.toFixed(1)+'%' } }
              },
              scales:{
                x:{ ticks:{ color:'rgba(255,255,255,.7)' }, grid:{ display:false } },
                y:{ beginAtZero:true, max:100, ticks:{ color:'rgba(255,255,255,.7)', callback:(v)=> v+'%' }, grid:{ color:'rgba(255,255,255,.08)' } }
              }
            }
          });
        }
        // Colunas empilhadas (absolutos) com toggle dia/semana
        var absDailyLabels = <?= json_encode($daily_labels ?? []) ?>;
        var absDailySR = <?= json_encode(($daily_counts['SEM_RETORNO'] ?? [])) ?>;
        var absDailyAD = <?= json_encode(($daily_counts['ADERENTE'] ?? [])) ?>;
        var absDailyNA = <?= json_encode(($daily_counts['NAO_ADERENTE'] ?? [])) ?>;
        var absWeeklyLabels = <?= json_encode($weekly_labels ?? []) ?>;
        var absWeeklySR = <?= json_encode($weekly_counts['SEM_RETORNO'] ?? []) ?>;
        var absWeeklyAD = <?= json_encode($weekly_counts['ADERENTE'] ?? []) ?>;
        var absWeeklyNA = <?= json_encode($weekly_counts['NAO_ADERENTE'] ?? []) ?>;
        var absCtx = document.getElementById('chartAbsClusters');
        var chartAbs = null;
        function buildAbs(mode){
          var labels = mode==='week' ? absWeeklyLabels : absDailyLabels;
          var dsSR = mode==='week' ? absWeeklySR : absDailySR;
          var dsAD = mode==='week' ? absWeeklyAD : absDailyAD;
          var dsNA = mode==='week' ? absWeeklyNA : absDailyNA;
          if(chartAbs){ chartAbs.destroy(); }
          chartAbs = new Chart(absCtx, {
            type:'bar',
            data:{
              labels: labels,
              datasets:[
                { label:'Sem Retorno', data: dsSR, backgroundColor:'rgba(255,193,7,.7)', stack:'abs' },
                { label:'Aderente', data: dsAD, backgroundColor:'rgba(25,135,84,.8)', stack:'abs' },
                { label:'Não Aderente', data: dsNA, backgroundColor:'rgba(220,53,69,.8)', stack:'abs' }
              ]
            },
            options:{
              responsive:true,
              interaction:{ mode:'index', intersect:false },
              plugins:{ legend:{ labels:{ color:'rgba(255,255,255,0.85)' } } },
              scales:{
                x:{ stacked:true, ticks:{ color:'rgba(255,255,255,.7)' }, grid:{ display:false } },
                y:{ stacked:true, beginAtZero:true, ticks:{ color:'rgba(255,255,255,.7)' }, grid:{ color:'rgba(255,255,255,.08)' } }
              }
            }
          });
        }
        if(absCtx && absDailyLabels.length){ buildAbs('day'); }
        var btnD = document.getElementById('btnViewDaily');
        var btnW = document.getElementById('btnViewWeekly');
        if(btnD && btnW){
          btnD.addEventListener('click', function(){ if(!btnD.classList.contains('active')){ btnD.classList.add('active'); btnW.classList.remove('active'); buildAbs('day'); } });
          btnW.addEventListener('click', function(){ if(!btnW.classList.contains('active')){ btnW.classList.add('active'); btnD.classList.remove('active'); buildAbs('week'); } });
        }
        // Gráfico de Motivos
        var motivosLabels = <?= json_encode($motivos_labels) ?>;
        var motivosValues = <?= json_encode($motivos_values) ?>;
        var motivosFinLabels = <?= json_encode($motivos_fin_labels) ?>;
        var motivosFinValues = <?= json_encode(array_map(fn($v)=> round($v,2), $motivos_fin_values)) ?>;
        var motivosFinCum = <?= json_encode($motivos_fin_cum) ?>;
        if(motivosLabels.length && document.getElementById('chartMotivos')) {
          new Chart(document.getElementById('chartMotivos'), {
            type:'bar',
            data:{ labels: motivosLabels, datasets:[{ label:'Qtd', data: motivosValues, backgroundColor:'rgba(13,110,253,0.7)', borderColor:'rgba(13,110,253,1)', borderWidth:1 }] },
            options:{
              responsive:true,
              indexAxis:'y',
              plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label:(ctx)=> ' '+ctx.parsed.x+' ocorrência(s)' } } },
              scales:{
                x:{ beginAtZero:true, ticks:{ color:'rgba(255,255,255,.75)' }, grid:{ color:'rgba(255,255,255,.08)' } },
                y:{ ticks:{ color:'rgba(255,255,255,.85)' }, grid:{ display:false } }
              }
            }
          });
        }
        if(motivosFinLabels.length && document.getElementById('chartMotivosFin')){
          new Chart(document.getElementById('chartMotivosFin'), {
            type:'bar',
            data:{
              labels: motivosFinLabels,
              datasets:[
                { type:'bar', label:'Valor', data: motivosFinValues, backgroundColor:'rgba(255,193,7,0.65)', borderColor:'rgba(255,193,7,1)', borderWidth:1, yAxisID:'y' },
                { type:'line', label:'Cumulativo %', data: motivosFinCum, borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,.2)', tension:.25, fill:false, yAxisID:'y1' }
              ]
            },
            options:{
              responsive:true,
              plugins:{
                legend:{ labels:{ color:'rgba(255,255,255,0.85)' } },
                tooltip:{ callbacks:{ label: function(ctx){ if(ctx.dataset.type==='line'){ return ' '+ctx.parsed.y.toFixed(1)+'%'; } else { return ' R$ '+ctx.parsed.y.toLocaleString('pt-BR',{minimumFractionDigits:2}); } } } }
              },
              interaction:{ mode:'index', intersect:false },
              scales:{
                y:{ beginAtZero:true, ticks:{ color:'rgba(255,255,255,.75)', callback:(v)=> 'R$ '+Number(v).toLocaleString('pt-BR') }, grid:{ color:'rgba(255,255,255,.08)' } },
                y1:{ beginAtZero:true, position:'right', max:100, ticks:{ color:'rgba(255,255,255,.75)', callback:(v)=> v+'%' }, grid:{ display:false } },
                x:{ ticks:{ color:'rgba(255,255,255,.75)' }, grid:{ display:false } }
              }
            }
          });
        }
      } catch(e) { console.warn('Chart init error', e); }
    })();
  </script>
</body>
</html>
