WITH prog AS (
  SELECT data, equipe, SUM(financeiro) AS programado
  FROM programacao WHERE tipo <> 'PROGRAMAÇÃO CANCELADA' GROUP BY data,equipe
), meta AS (
  SELECT data, equipe, SUM(meta) AS meta FROM equipe_escala GROUP BY data,equipe
), exec_serv AS (
  SELECT data, equipe, servico,
         SUM(quantidade*valor) AS exec_valor, SUM(quantidade) AS exec_qtd
  FROM programacao_servico WHERE status='EXECUTADO' GROUP BY data,equipe,servico
), exec_tot AS (
  SELECT data, equipe, SUM(exec_valor) AS exec_total_valor FROM exec_serv GROUP BY data,equipe
)
SELECT es.data, es.equipe, s.id AS servico_id, s.grupo AS grupo_servico,
       COALESCE(p.programado,0) AS programado,
       COALESCE(m.meta,0) AS meta_dia,
       es.exec_valor, es.exec_qtd,
       CASE WHEN et.exec_total_valor IS NULL OR et.exec_total_valor=0 THEN 0
            ELSE m.meta * (es.exec_valor/et.exec_total_valor) END AS meta_rateada_servico
FROM exec_serv es
LEFT JOIN exec_tot et ON et.data=es.data AND et.equipe=es.equipe
LEFT JOIN meta m ON m.data=es.data AND m.equipe=es.equipe
LEFT JOIN prog p ON p.data=es.data AND p.equipe=es.equipe
LEFT JOIN servico s ON s.id=es.servico
ORDER BY es.data, es.equipe, es.exec_valor DESC;
