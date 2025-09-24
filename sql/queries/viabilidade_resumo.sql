SELECT v.data, v.obra, v.viabilizador,
       SUM(COALESCE(v.poste_instalado,0))  AS poste_instalado,
       SUM(COALESCE(v.poste_retirado,0))   AS poste_retirado,
       SUM(COALESCE(v.poste_realocado,0))  AS poste_realocado,
       SUM(COALESCE(v.cabo_instalado,0))   AS cabo_instalado,
       SUM(COALESCE(v.cabo_retirado,0))    AS cabo_retirado,
       SUM(COALESCE(v.cabo_realocado,0))   AS cabo_realocado
FROM viabilidade v
/* AND v.data BETWEEN :data_ini AND :data_fim */
GROUP BY v.data, v.obra, v.viabilizador
ORDER BY v.data, v.obra;
