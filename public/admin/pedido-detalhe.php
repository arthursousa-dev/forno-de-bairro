<?php
/**
 * public/admin/pedido-detalhe.php
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$pdo = conectar();
$pedidoId = (int)($_GET['id'] ?? 0);

$statusValidos = ['pendente', 'preparo', 'saiu_entrega', 'entregue', 'cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        definirFlash('error', 'Sessão expirada. Tente novamente.');
    } else {
        $novoStatus = $_POST['status'] ?? '';
        if (in_array($novoStatus, $statusValidos, true)) {
            $upd = $pdo->prepare('UPDATE pedidos SET status = :status WHERE id = :id');
            $upd->execute([':status' => $novoStatus, ':id' => $pedidoId]);
            definirFlash('success', 'Status do pedido #' . $pedidoId . ' atualizado para "' . statusLabel($novoStatus) . '".');
        } else {
            definirFlash('error', 'Status inválido.');
        }
    }
    header('Location: /admin/pedido-detalhe.php?id=' . $pedidoId);
    exit;
}

$stmt = $pdo->prepare('SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email, u.telefone AS cliente_telefone
                        FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.id = :id');
$stmt->execute([':id' => $pedidoId]);
$pedido = $stmt->fetch();

if (!$pedido) {
    definirFlash('error', 'Pedido não encontrado.');
    header('Location: /admin/pedidos.php');
    exit;
}

$stmtItens = $pdo->prepare('SELECT pi.*, p.nome AS produto_nome FROM pedido_itens pi
                             JOIN produtos p ON p.id = pi.produto_id WHERE pi.pedido_id = :id');
$stmtItens->execute([':id' => $pedidoId]);
$itens = $stmtItens->fetchAll();

$tituloPagina = 'Pedido #' . $pedidoId;
$paginaAdminAtual = 'pedidos';
include __DIR__ . '/../../app/Views/admin/header.php';
?>

<a href="/admin/pedidos.php" class="muted" style="font-size:0.85rem; display:inline-block; margin-bottom:18px;">&larr; Voltar para pedidos</a>

<div class="order-layout">
  <div>
    <div class="table-card" style="padding: 24px; margin-bottom: 20px;">
      <h3 style="text-transform:none; margin-bottom: 18px;">Itens do pedido</h3>
      <?php foreach ($itens as $item): ?>
        <div class="flex-between" style="padding: 10px 0; border-bottom: 1px solid var(--line);">
          <span><?= (int)$item['quantidade'] ?>x <?= limpar($item['produto_nome']) ?>
            <span class="muted mono" style="font-size:0.8rem;"> (<?= formatarPreco($item['preco_unitario']) ?> un.)</span>
          </span>
          <span class="mono"><?= formatarPreco($item['subtotal']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="flex-between" style="margin-top: 16px; padding-top: 12px; border-top: 2px solid var(--line); font-size: 1.1rem; font-weight: 700;">
        <span>Total</span>
        <span class="mono"><?= formatarPreco($pedido['total']) ?></span>
      </div>
    </div>

    <div class="table-card" style="padding: 24px;">
      <h3 style="text-transform:none; margin-bottom: 14px;">Dados de entrega</h3>
      <p style="margin-bottom: 6px;"><strong>Endereço:</strong> <?= limpar($pedido['endereco_entrega']) ?></p>
      <p style="margin-bottom: 6px; text-transform:capitalize;"><strong>Pagamento:</strong> <?= limpar($pedido['forma_pagamento']) ?></p>
      <?php if ($pedido['observacoes']): ?>
        <p style="margin-bottom: 0;"><strong>Observações:</strong> <?= limpar($pedido['observacoes']) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="cart-ticket" style="position:static;">
      <h3>Pedido #<?= (int)$pedido['id'] ?></h3>
      <p style="color: rgba(251,246,236,0.6); font-size: 0.85rem; margin-bottom: 18px;">
        Recebido em <?= formatarData($pedido['criado_em']) ?>
      </p>

      <p style="margin-bottom: 4px; font-weight: 600;"><?= limpar($pedido['cliente_nome']) ?></p>
      <p style="color: rgba(251,246,236,0.6); font-size: 0.85rem; margin-bottom: 2px;"><?= limpar($pedido['cliente_email']) ?></p>
      <p style="color: rgba(251,246,236,0.6); font-size: 0.85rem; margin-bottom: 18px;"><?= limpar($pedido['cliente_telefone'] ?? '—') ?></p>

      <div style="margin-bottom: 16px;">
        <span class="badge badge-<?= limpar($pedido['status']) ?>"><?= limpar(statusLabel($pedido['status'])) ?></span>
      </div>

      <form method="post" action="/admin/pedido-detalhe.php?id=<?= (int)$pedido['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
        <div class="field" style="margin-bottom: 14px;">
          <label style="color: var(--flour);" for="status">Atualizar status</label>
          <select id="status" name="status" style="background: var(--flour);">
            <?php foreach ($statusValidos as $st): ?>
              <option value="<?= $st ?>" <?= $pedido['status'] === $st ? 'selected' : '' ?>><?= statusLabel($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Salvar status</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
