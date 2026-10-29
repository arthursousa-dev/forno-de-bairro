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
    // lower() nos dois lados: contas antigas podem ter sido gravadas com maiúsculas.
    $sql = 'SELECT *, CASE WHEN bloqueado_ate > now() THEN 1 ELSE 0 END AS bloqueado FROM usuarios WHERE lower(email) = lower(:email)' . ($tipoEsperado ? ' AND tipo = :tipo' : '');
    $stmt = $pdo->prepare($sql);
    $params = [':email' => $email];
    if ($tipoEsperado) $params[':tipo'] = $tipoEsperado;
    $stmt->execute($params);
    $u = $stmt->fetch();

    if (!$u) {
        return null;
    }
    // Comparado no banco: bloqueado_ate vem do now() do PostgreSQL, e strtotime() usaria o fuso do PHP.
    if ((int) $u['bloqueado'] === 1) {
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

/**
 * Cria um pedido a partir do carrinho: valida, recalcula os preços
 * a partir do banco (nunca confia no valor enviado pelo cliente),
 * grava pedido + itens numa transação. Extraída de public/pedido.php
 * pra poder ser testada isoladamente, sem depender de $_POST/$_SESSION.
 *
 * @return array{sucesso: bool, erro: ?string, pedido_id: ?int, total: ?float}
 */
function criarPedido(PDO $pdo, int $usuarioId, string $endereco, string $formaPagamento, ?string $observacoes, array $itensCarrinho): array
{
    if (mb_strlen($endereco) < 8) {
        return ['sucesso' => false, 'erro' => 'Informe um endereço de entrega completo.', 'pedido_id' => null, 'total' => null];
    }
    // Limites das colunas (VARCHAR 255/500): acima disso o INSERT falhava e o cliente via só um erro genérico.
    if (mb_strlen($endereco) > 255 || mb_strlen((string) $observacoes) > 500) {
        return ['sucesso' => false, 'erro' => 'Endereço ou observações muito longos.', 'pedido_id' => null, 'total' => null];
    }
    // itens_json vem do navegador: descarta entradas que não sejam {id, quantidade} numéricos.
    $itensCarrinho = array_values(array_filter($itensCarrinho, fn($i) => is_array($i)
        && isset($i['id'], $i['quantidade']) && is_numeric($i['id']) && is_numeric($i['quantidade'])));
    if (!in_array($formaPagamento, ['dinheiro', 'cartao', 'pix'], true)) {
        return ['sucesso' => false, 'erro' => 'Selecione uma forma de pagamento.', 'pedido_id' => null, 'total' => null];
    }
    if (count($itensCarrinho) === 0) {
        return ['sucesso' => false, 'erro' => 'Seu carrinho está vazio. Adicione itens antes de finalizar o pedido.', 'pedido_id' => null, 'total' => null];
    }

    try {
        $idsValidados = array_map(fn($i) => (int) $i['id'], $itensCarrinho);
        $placeholders = implode(',', array_fill(0, count($idsValidados), '?'));
        $stmtProd = $pdo->prepare("SELECT id, nome, preco FROM produtos WHERE id IN ($placeholders) AND disponivel = true");
        $stmtProd->execute($idsValidados);
        $produtosBanco = [];
        foreach ($stmtProd->fetchAll() as $row) {
            $produtosBanco[$row['id']] = $row;
        }

        $itensFinais = [];
        $total = 0;
        foreach ($itensCarrinho as $item) {
            $id  = (int) $item['id'];
            $qtd = max(1, min(50, (int) $item['quantidade']));
            if (!isset($produtosBanco[$id])) continue;
            $preco = (float) $produtosBanco[$id]['preco'];
            $subtotal = $preco * $qtd;
            $total += $subtotal;
            $itensFinais[] = ['produto_id' => $id, 'quantidade' => $qtd, 'preco' => $preco, 'subtotal' => $subtotal];
        }

        if (!$itensFinais) {
            return ['sucesso' => false, 'erro' => 'Não foi possível validar os itens do carrinho. Tente novamente.', 'pedido_id' => null, 'total' => null];
        }

        $pdo->beginTransaction();

        $stmtPedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, status, endereco_entrega, forma_pagamento, observacoes, total)
                                      VALUES (:usuario_id, 'pendente', :endereco, :pagamento, :obs, :total)");
        $stmtPedido->execute([
            ':usuario_id' => $usuarioId,
            ':endereco'   => $endereco,
            ':pagamento'  => $formaPagamento,
            ':obs'        => $observacoes ?: null,
            ':total'      => $total,
        ]);
        $pedidoId = (int) $pdo->lastInsertId();

        $stmtItem = $pdo->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
                                    VALUES (:pedido_id, :produto_id, :quantidade, :preco, :subtotal)');
        foreach ($itensFinais as $item) {
            $stmtItem->execute([
                ':pedido_id'  => $pedidoId,
                ':produto_id' => $item['produto_id'],
                ':quantidade' => $item['quantidade'],
                ':preco'      => $item['preco'],
                ':subtotal'   => $item['subtotal'],
            ]);
        }

        $pdo->commit();

        return ['sucesso' => true, 'erro' => null, 'pedido_id' => $pedidoId, 'total' => $total];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['sucesso' => false, 'erro' => 'Não foi possível registrar seu pedido agora. Tente novamente.', 'pedido_id' => null, 'total' => null];
    }
}

/**
 * Calcula os metadados de paginação a partir de $_GET['pagina'] e do
 * total de itens (uma query COUNT separada) — para usar direto num
 * LIMIT/OFFSET na query principal.
 */
function metaPaginacao(int $totalItens, int $porPagina = 15): array
{
    $totalPaginas = max(1, (int) ceil($totalItens / $porPagina));
    $paginaAtual  = max(1, min($totalPaginas, (int) ($_GET['pagina'] ?? 1)));

    return [
        'pagina_atual'  => $paginaAtual,
        'total_paginas' => $totalPaginas,
        'offset'        => ($paginaAtual - 1) * $porPagina,
        'limite'        => $porPagina,
        'total_itens'   => $totalItens,
    ];
}

/**
 * Desenha os controles de paginação, preservando os filtros já
 * aplicados na querystring.
 */
function controlesPaginacao(array $meta): string
{
    if ($meta['total_paginas'] <= 1) {
        return '';
    }

    $paramsBase = $_GET;
    unset($paramsBase['pagina']);
    $linkPara = fn($p) => '?' . http_build_query(array_merge($paramsBase, ['pagina' => $p]));

    $atual = $meta['pagina_atual'];
    $totalPag = $meta['total_paginas'];

    $html = '<nav class="paginacao" aria-label="Navegação de páginas" style="display:flex;gap:6px;align-items:center;justify-content:center;margin-top:16px;flex-wrap:wrap">';
    $html .= $atual > 1 ? '<a href="' . $linkPara($atual - 1) . '">‹ Anterior</a>' : '<span style="opacity:.4">‹ Anterior</span>';
    for ($p = 1; $p <= $totalPag; $p++) {
        $html .= $p === $atual
            ? '<span aria-current="page" style="padding:6px 12px;font-weight:700">' . $p . '</span>'
            : '<a href="' . $linkPara($p) . '" style="padding:6px 12px">' . $p . '</a>';
    }
    $html .= $atual < $totalPag ? '<a href="' . $linkPara($atual + 1) . '">Próxima ›</a>' : '<span style="opacity:.4">Próxima ›</span>';
    $html .= '</nav>';

    return $html;
}
