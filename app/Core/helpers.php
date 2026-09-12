<?php
/**
 * app/Core/helpers.php
 *
 * Funções utilitárias compartilhadas por toda a aplicação:
 * sanitização, autenticação de sessão, CSRF, flash messages e formatação.
 */

use App\Config\Database;

require_once __DIR__ . '/../Config/Database.php';

/** Retorna a conexão PDO ativa (wrapper de conveniência sobre Database::getConnection). */
function conectar(): PDO
{
    return Database::getConnection();
}

/** Remove espaços nas pontas e converte caracteres especiais em entidades HTML. */
function limpar(string $valor): string
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

/** Formata um valor decimal como moeda brasileira (R$ 0.000,00). */
function formatarPreco(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/** Formata uma data/hora do banco (Y-m-d H:i:s) para o padrão brasileiro. */
function formatarData(string $dataHora, string $formato = 'd/m/Y \à\s H:i'): string
{
    $dt = date_create($dataHora);
    return $dt ? date_format($dt, $formato) : $dataHora;
}

/**
 * Autentica um usuário por e-mail/senha, com bloqueio de 15 minutos
 * após 5 tentativas incorretas seguidas. Retorna a linha do usuário
 * em caso de sucesso, ou null (credenciais inválidas ou bloqueado).
 */
function autenticar(PDO $pdo, string $email, string $senha, ?string $tipoEsperado = null): ?array
{
    $sql = 'SELECT * FROM usuarios WHERE email = :email' . ($tipoEsperado ? ' AND tipo = :tipo' : '');
    $stmt = $pdo->prepare($sql);
    $params = [':email' => $email];
    if ($tipoEsperado) $params[':tipo'] = $tipoEsperado;
    $stmt->execute($params);
    $u = $stmt->fetch();

    if (!$u) {
        return null;
    }
    if ($u['bloqueado_ate'] && strtotime($u['bloqueado_ate']) > time()) {
        return null;
    }
    if (!password_verify($senha, $u['senha_hash'])) {
        $tentativas = (int)$u['tentativas_login'] + 1;
        $bloqueio = $tentativas >= 5 ? "now() + interval '15 minutes'" : 'NULL';
        $pdo->prepare("UPDATE usuarios SET tentativas_login = ?, bloqueado_ate = {$bloqueio} WHERE id = ?")
            ->execute([$tentativas, $u['id']]);
        return null;
    }

    $pdo->prepare('UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?')->execute([$u['id']]);
    return $u;
}

/** Verifica se existe um usuário autenticado na sessão. */
function estaLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** Verifica se o usuário autenticado possui privilégio de administrador. */
function ehAdmin(): bool
{
    return estaLogado() && ($_SESSION['usuario_tipo'] ?? '') === 'admin';
}

/** Bloqueia o acesso de visitantes não autenticados, redirecionando ao login. */
function exigirLogin(string $destino = '/login.php'): void
{
    if (!estaLogado()) {
        $atual = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . $destino . '?redirect=' . urlencode($atual));
        exit;
    }
}

/** Bloqueia o acesso de quem não é administrador, redirecionando ao login do ADM. */
function exigirAdmin(): void
{
    if (!ehAdmin()) {
        header('Location: /admin/index.php');
        exit;
    }
}

/** Gera (ou recupera) o token CSRF vigente da sessão atual. */
function tokenCSRF(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Valida um token CSRF recebido via formulário contra o da sessão. */
function validarCSRF(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Define uma mensagem flash (sucesso/erro/info) a ser exibida na próxima página. */
function definirFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Recupera e descarta a mensagem flash da sessão (uso único). */
function obterFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Rótulo amigável em português para o status de um pedido. */
function statusLabel(string $status): string
{
    $labels = [
        'pendente'     => 'Pendente',
        'preparo'      => 'Em preparo',
        'saiu_entrega' => 'Saiu para entrega',
        'entregue'     => 'Entregue',
        'cancelado'    => 'Cancelado',
    ];
    return $labels[$status] ?? ucfirst($status);
}

/** Trunca um texto para exibição em listagens, preservando palavras inteiras. */
function truncar(string $texto, int $limite = 80): string
{
    $texto = trim($texto);
    if (mb_strlen($texto) <= $limite) {
        return $texto;
    }
    return mb_substr($texto, 0, $limite) . '…';
}

/**
 * Retorna a URL pública da imagem de um produto, ou null caso o arquivo
 * não exista em disco (nesse caso a interface usa um ícone ilustrativo).
 */
function urlImagemProduto(?string $imagem): ?string
{
    if (!$imagem) {
        return null;
    }
    $caminhoFisico = UPLOAD_PRODUTOS_PATH . '/' . basename($imagem);
    return is_file($caminhoFisico) ? UPLOAD_PRODUTOS_URL . '/' . rawurlencode(basename($imagem)) : null;
}

/**
 * Processa o upload de imagem de um produto (campo <input type="file">).
 * Retorna o nome do arquivo salvo, ou null se nenhum arquivo válido foi enviado.
 * Lança uma RuntimeException com mensagem amigável em caso de erro de validação.
 */
function processarUploadImagemProduto(array $arquivo): ?string
{
    if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Nenhum arquivo enviado: não é erro.
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha ao enviar a imagem. Tente novamente.');
    }

    if ($arquivo['size'] > UPLOAD_PRODUTOS_MAX_BYTES) {
        throw new RuntimeException('A imagem deve ter no máximo 3 MB.');
    }

    $mimesPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);

    if (!isset($mimesPermitidos[$mime])) {
        throw new RuntimeException('Formato de imagem inválido. Use JPG, PNG ou WEBP.');
    }

    if (!is_dir(UPLOAD_PRODUTOS_PATH)) {
        mkdir(UPLOAD_PRODUTOS_PATH, 0755, true);
    }

    $nomeArquivo = 'produto-' . bin2hex(random_bytes(8)) . '.' . $mimesPermitidos[$mime];
    $destino     = UPLOAD_PRODUTOS_PATH . '/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Não foi possível salvar a imagem enviada.');
    }

    return $nomeArquivo;
}

/** Remove do disco a imagem de um produto, se existir. */
function removerImagemProduto(?string $imagem): void
{
    if (!$imagem) {
        return;
    }
    $caminho = UPLOAD_PRODUTOS_PATH . '/' . basename($imagem);
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}
