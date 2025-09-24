SELECT ee.data, e.filial, e.id AS equipe_id, e.titulo AS equipe,
       a.titulo AS atividade, coord.nome AS coordenador,
       SUM(ee.meta) AS meta
FROM equipe_escala ee
JOIN equipe e ON e.id = ee.equipe
LEFT JOIN atividade a ON a.id = e.atividade
LEFT JOIN folha coord ON coord.cpf = e.coordenador
WHERE e.status='ATIVO'
/* AND ee.data BETWEEN :data_ini AND :data_fim */
/* AND e.filial = :filial_id */
GROUP BY ee.data, e.filial, e.id, e.titulo, a.titulo, coord.nome
ORDER BY ee.data, meta DESC;
