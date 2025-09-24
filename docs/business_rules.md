# Regras de Negócio
- Programado (R$) = SUM(programacao.financeiro) excluindo 'PROGRAMAÇÃO CANCELADA'.
- Executado (R$) = SUM(programacao_servico.quantidade * programacao_servico.valor) com status='EXECUTADO'.
- Meta (dia/equipe) = SUM(equipe_escala.meta).
- Medido (R$) = SUM(fechamento_medicao.valor).
- Faturado/Pago (R$) = SUM(faturamento.valor) ou fechamento.valor_pagamento.
