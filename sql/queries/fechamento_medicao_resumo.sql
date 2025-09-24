SELECT fm.data, fm.obra, SUM(fm.valor) AS valor_medido
FROM fechamento_medicao fm
/* AND fm.data BETWEEN :data_ini AND :data_fim */
GROUP BY fm.data, fm.obra
ORDER BY fm.data, fm.obra;
