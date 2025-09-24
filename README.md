# IGOB Dashboards – Starter Pack 2.0
Este pack ajuda o Copilot a gerar painéis com joins canônicos e regras padronizadas.
Pastas: schema/, docs/, sql/queries/, sql/checks/, .copilot/

## Autenticação (Login) adicionada
Foi adicionada uma estrutura mínima de autenticação em PHP (Bootstrap 5) para rodar localmente no XAMPP.

Arquivos principais:
- `src/config.php`: Conexão PDO + sessão.
- `src/auth/functions.php`: Funções de login/logout e verificação.
- `public/login.php`: Tela de login estilo dark/glass.
- `public/index.php`: Tela inicial pós-login.
- `public/logout.php`: Termina a sessão.

### Tabela `usuario` (ajustada para email + senha texto)
```
id INT PK AUTO_INCREMENT
email VARCHAR(120) UNIQUE NOT NULL
senha VARCHAR(120) NOT NULL  -- texto plano (apenas ambiente interno / legado)
nome VARCHAR(120) NULL
ativo TINYINT(1) DEFAULT 1
```

Criação rápida:
```
CREATE TABLE IF NOT EXISTS usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(120) NOT NULL UNIQUE,
  senha VARCHAR(120) NOT NULL,
  nome VARCHAR(120) NULL,
  ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Usuário admin exemplo (senha: admin):
```
INSERT INTO usuario (email, senha, nome, ativo)
VALUES ('admin@local', 'admin', 'Administrador', 1);
```

### Executando
1. Banco MySQL local: criar database `igob`.
2. Criar tabela e inserir usuário.
3. Acessar: http://localhost/cardsigob/public/login.php

Credenciais exemplo: admin@local / admin

### Config de ambiente
Edite `src/config.php` ou exporte variáveis:
```
export DB_HOST=127.0.0.1
export DB_NAME=igob
export DB_USER=root
export DB_PASS=""
```

### To-Do Futuro
- Recuperação de senha
- Perfis / autorização por role
- CSRF tokens
- Logs de auditoria
- Docker Compose

