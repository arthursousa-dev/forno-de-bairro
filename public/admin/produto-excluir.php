<?php
/**
 * public/admin/produto-excluir.php
 * Processa a exclusão de um produto (recebe POST de produtos.php).
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarCSRF($_POST['csrf_token'] ?? null)) {
    definirFlash('error', 'Requisição inválida.');
    header('Location: /admin/produtos.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    $pdo = conectar();

    $stmt = $pdo->prepare('SELECT imagem FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        definirFlash('error', 'Produto não encontrado.');
    } else {
        // Itens de pedidos já realizados referenciam este produto via FK,
        // então não é permitido excluir produtos com histórico de vendas —
        // apenas desativá-los (evita perda de integridade dos relatórios).
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM pedido_itens WHERE produto_id = :id');
        $stmtCheck->execute([':id' => $id]);

        if ((int)$stmtCheck->fetchColumn() > 0) {
            $upd = $pdo->prepare('UPDATE produtos SET disponivel = 0 WHERE id = :id');
            $upd->execute([':id' => $id]);
            definirFlash('info', 'Este produto já possui pedidos registrados e não pode ser excluído — ele foi marcado como indisponível.');
        } else {
            $del = $pdo->prepare('DELETE FROM produtos WHERE id = :id');
            $del->execute([':id' => $id]);
            removerImagemProduto($produto['imagem']);
            definirFlash('success', 'Produto excluído com sucesso.');
        }
    }
} catch (Throwable $e) {
    definirFlash('error', 'Não foi possível excluir o produto agora.');
}

header('Location: /admin/produtos.php');
exit;
