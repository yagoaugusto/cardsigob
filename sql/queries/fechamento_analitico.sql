SELECT o.id AS obra_id, o.filial,
       f.data_envio_pasta, f.data_fechamento, f.data_postagem, f.valor_postagem,
       f.data_solicitacao_termo, f.data_aprovacao_termo,
       f.valor_pagamento, f.data_pagamento,
       f.status_aprovacao, f.status_pasta, f.status_materiais,
       fm.valor_medido, fat.valor_faturado,
       DATEDIFF(f.data_fechamento, f.data_envio_pasta) AS dias_envio_ate_fechamento,
       DATEDIFF(f.data_pagamento, f.data_fechamento)   AS dias_fechamento_ate_pagamento
FROM obra o
LEFT JOIN fechamento f ON f.obra=o.id
LEFT JOIN (SELECT obra, SUM(valor) AS valor_medido FROM fechamento_medicao GROUP BY obra) fm ON fm.obra=o.id
LEFT JOIN (SELECT obra, SUM(valor) AS valor_faturado FROM faturamento GROUP BY obra) fat ON fat.obra=o.id
ORDER BY o.id;
