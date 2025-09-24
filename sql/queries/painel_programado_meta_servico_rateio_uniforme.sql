WITH prog AS (
  SELECT data, equipe, SUM(financeiro) AS programado
  FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY data,equipe
), meta AS (
  SELECT data, equipe, SUM(meta) AS meta FROM equipe_escala GROUP BY data,equipe
), exec_serv AS (
  SELECT data, equipe, servico,
         SUM(quantidade*valor) AS exec_valor, COUNT(*) AS n_registros_exec
  FROM programacao_servico WHERE status='EXECUTADO' GROUP BY data,equipe,servico
), exec_count AS (
  SELECT data, equipe, SUM(n_registros_exec) AS n_servicos FROM exec_serv GROUP BY data,equipe
)
SELECT es.data, es.equipe, s.id AS servico_id, s.grupo AS grupo_servico,
       COALESCE(p.programado,0) AS programado,
       COALESCE(m.meta,0) AS meta_dia,
       es.exec_valor,
       CASE WHEN ec.n_servicos IS NULL OR ec.n_servicos=0 THEN 0 ELSE m.meta/ec.n_servicos END AS meta_rateada_servico
FROM exec_serv es
LEFT JOIN exec_count ec ON ec.data=es.data AND ec.equipe=es.equipe
LEFT JOIN meta m ON m.data=es.data AND m.equipe=es.equipe
LEFT JOIN prog p ON p.data=es.data AND p.equipe=es.equipe
LEFT JOIN servico s ON s.id=es.servico
ORDER BY es.data, es.equipe, es.exec_valor DESC;
