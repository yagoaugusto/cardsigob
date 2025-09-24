<?php
require_once __DIR__ . '/../config.php';

// Assumindo agora a estrutura real com colunas: id, email, senha (texto plano), nome(opcional), ativo(opcional)
// IMPORTANTE: senha em texto plano não é recomendada em produção. Migrar futuramente para password_hash.

function auth_login(string $emailInput, string $senhaInput): bool {
    global $pdo;

    static $colMap = null; // cache por request
    if ($colMap === null) {
        $colMap = [
            'userField' => null, // email ou login
            'passField' => null, // senha ou senha_hash
            'nameField' => null, // nome (opcional)
            'statusField' => null, // status (ATIVO) se existir
        ];
        try {
            $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='usuario'")
                        ->fetchAll(PDO::FETCH_COLUMN);
            $lower = array_map('strtolower', $cols);
            // Determinar campo de usuário
            if (in_array('email', $lower)) {
                $colMap['userField'] = $cols[array_search('email', $lower)];
            } elseif (in_array('login', $lower)) {
                $colMap['userField'] = $cols[array_search('login', $lower)];
            }
            // Determinar campo de senha
            if (in_array('senha', $lower)) {
                $colMap['passField'] = $cols[array_search('senha', $lower)];
            } elseif (in_array('senha_hash', $lower)) {
                $colMap['passField'] = $cols[array_search('senha_hash', $lower)];
            }
            // Nome
            if (in_array('nome', $lower)) {
                $colMap['nameField'] = $cols[array_search('nome', $lower)];
            }
            // Status
            if (in_array('status', $lower)) {
                $colMap['statusField'] = $cols[array_search('status', $lower)];
            }
        } catch (Throwable $e) {
            // Fallback estático se não conseguimos introspectar
            $colMap['userField'] = 'email';
            $colMap['passField'] = 'senha';
            $colMap['nameField'] = 'nome';
            $colMap['statusField'] = 'status';
        }
    }

    $userField = $colMap['userField'] ?? 'email';
    $passField = $colMap['passField'] ?? 'senha';
    $nameField = $colMap['nameField'] ?? 'nome';
    $statusField = $colMap['statusField'];

    // Monta seleção garantindo alias padronizado
    $selectCols = ["id", "$userField AS user_col", "$passField AS pass_col"];
    if ($nameField) $selectCols[] = "$nameField AS nome";
    if ($statusField) $selectCols[] = "$statusField AS status";

    $where = "$userField = :u";
    if ($statusField) {
        $where .= " AND $statusField = 'ATIVO'"; // só usuários ativos
    }
    $sql = 'SELECT ' . implode(', ', $selectCols) . " FROM usuario WHERE $where LIMIT 1";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':u' => $emailInput]);
    } catch (PDOException $e) {
        // Cria log de debug para ajudar a diagnosticar se ainda está chamando coluna inexistente
        @file_put_contents(sys_get_temp_dir() . '/auth_debug.log', date('c') . " SQL FAIL: " . $e->getMessage() . " SQL=[$sql]\n", FILE_APPEND);
        throw $e;
    }

    $user = $stmt->fetch();
    if (!$user) return false;

    $senhaDb = (string)$user['pass_col'];

    // Decide método: texto plano ou hash (se migrarmos futuramente)
    // Verificação compatível com PHP 7.2 (sem str_starts_with)
    $isHash = (strncmp($senhaDb, '$2y$', 4) === 0) || (strncmp($senhaDb, '$argon2', 7) === 0);
    $ok = $isHash ? password_verify($senhaInput, $senhaDb) : hash_equals($senhaDb, $senhaInput);

    if ($ok) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['user_col'];
        $_SESSION['user_nome'] = $user['nome'] ?? $user['user_col'];
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

function auth_require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /cardsigob/public/login.php');
        exit;
    }
    // Timeout de inatividade (30 min)
    if (!empty($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
        auth_logout();
        header('Location: /cardsigob/public/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? null,
        'nome' => $_SESSION['user_nome']
    ];
}
?>
