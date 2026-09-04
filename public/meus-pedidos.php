<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Meus pedidos';
$paginaAtual  = 'pedidos';

exigirLogin('/login.php');

$pdo = conectar();
$stmt = $pdo->prepare('SELECT * FROM pedidos WHERE usuario_id = :id ORDER BY criado_em DESC');
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$pedidos = $stmt->fetchAll();

// Carrega os itens de cada pedido em uma única consulta.
$itensPorPedido = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtItens = $pdo->prepare("SELECT pi.*, p.nome AS produto_nome FROM pedido_itens pi
                                 JOIN produtos p ON p.id = pi.produto_id
                                 WHERE pi.pedido_id IN ($placeholders)");
    $stmtItens->execute($ids);
    foreach ($stmtItens->fetchAll() as $item) {
        $itensPorPedido[$item['pedido_id']][] = $item;
    }
}

$pedidoNovo = isset($_GET['novo']);

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="section" style="padding-top: 50px;">
  <div class="container" style="max-width: 820px;">
    <div class="eyebrow">Acompanhamento</div>
    <h1 style="font-size: 2.1rem; margin-bottom: 28px;">Meus pedidos</h1>

    <?php if (!$pedidos): ?>
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 3h2l2.6 13.4a2 2 0 002 1.6h8.8a2 2 0 002-1.6L21 7H6"/></svg>
        <p>Você ainda não fez nenhum pedido.</p>
        <a href="/produtos.php" class="btn btn-primary">Ver cardápio</a>
      </div>
    <?php else: ?>
      <?php foreach ($pedidos as $pedido): ?>
        <div class="table-card" style="margin-bottom: 20px; padding: 22px 24px;">
          <div class="flex-between" style="margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
              <strong class="mono">Pedido #<?= (int)$pedido['id'] ?></strong>
              <div class="muted" style="font-size: 0.82rem;"><?= date('d/m/Y \à\s H:i', strtotime($pedido['criado_em'])) ?></div>
            </div>
            <span class="badge badge-<?= limpar($pedido['status']) ?>"><?= limpar(statusLabel($pedido['status'])) ?></span>
          </div>

          <div style="border-top: 1px solid var(--line); padding-top: 14px;">
            <?php foreach ($itensPorPedido[$pedido['id']] ?? [] as $item): ?>
              <div class="flex-between" style="font-size: 0.88rem; padding: 4px 0;">
                <span><?= (int)$item['quantidade'] ?>x <?= limpar($item['produto_nome']) ?></span>
                <span class="mono"><?= formatarPreco($item['subtotal']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="flex-between" style="border-top: 1px solid var(--line); margin-top: 12px; padding-top: 12px;">
            <span class="muted" style="font-size: 0.85rem;">Entrega: <?= limpar($pedido['endereco_entrega']) ?></span>
            <strong class="mono"><?= formatarPreco($pedido['total']) ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php if ($pedidoNovo): ?>
<script>
  // Pedido confirmado no servidor: limpa o carrinho local do navegador.
  localStorage.removeItem('forno_carrinho_v1');
</script>
<?php endif; ?>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
