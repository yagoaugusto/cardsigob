SELECT ps.data, e.filial, e.id AS equipe_id, e.titulo AS equipe,
       a.titulo AS atividade, s.id AS servico_id, s.grupo AS grupo_servico,
       SUM(ps.quantidade*ps.valor) AS executado_valor, SUM(ps.quantidade) AS executado_qtd
FROM programacao_servico ps
JOIN equipe e ON e.id = ps.equipe
LEFT JOIN atividade a ON a.id = e.atividade
LEFT JOIN servico s ON s.id = ps.servico
WHERE ps.status='EXECUTADO' AND e.status='ATIVO'
/* AND ps.data BETWEEN :data_ini AND :data_fim */
/* AND e.filial = :filial_id */
/* AND e.id = :equipe_id */
/* AND e.atividade = :atividade_id */
/* AND s.grupo = :grupo_servico */
GROUP BY ps.data, e.filial, e.id, e.titulo, a.titulo, s.id, s.grupo
ORDER BY ps.data, e.id, executado_valor DESC;
