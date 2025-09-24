SELECT p.data, e.filial, e.id AS equipe_id, e.titulo AS equipe,
       a.titulo AS atividade, coord.nome AS coordenador,
       SUM(p.financeiro) AS programado
FROM programacao p
JOIN equipe e ON e.id = p.equipe
LEFT JOIN atividade a ON a.id = e.atividade
LEFT JOIN folha coord ON coord.cpf = e.coordenador
WHERE p.tipo <> 'PROGRAMAÇÃO CANCELADA' AND e.status='ATIVO'
/* AND p.data BETWEEN :data_ini AND :data_fim */
/* AND e.filial = :filial_id */
/* AND e.id = :equipe_id */
/* AND e.atividade = :atividade_id */
GROUP BY p.data, e.filial, e.id, e.titulo, a.titulo, coord.nome
ORDER BY p.data, programado DESC;
