<?php
/**
 * public/admin/pedidos.php
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$tituloPagina = 'Pedidos';
$paginaAdminAtual = 'pedidos';

$pdo = conectar();

$statusFiltro = $_GET['status'] ?? 'todos';
$statusValidos = ['pendente', 'preparo', 'saiu_entrega', 'entregue', 'cancelado'];

$sql = "SELECT p.*, u.nome AS cliente_nome, u.telefone AS cliente_telefone
        FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id";
$params = [];

if (in_array($statusFiltro, $statusValidos, true)) {
    $sql .= ' WHERE p.status = :status';
    $params[':status'] = $statusFiltro;
}
$sql .= ' ORDER BY p.criado_em DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

$contagens = $pdo->query("SELECT status, COUNT(*) AS qtd FROM pedidos GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/../../app/Views/admin/header.php';
?>

<div class="filter-tabs" style="margin-bottom: 24px;">
  <a href="/admin/pedidos.php" class="<?= $statusFiltro === 'todos' ? 'active' : '' ?>" style="display:inline-flex; padding:9px 16px; border-radius:100px; border:1px solid var(--line); font-family:var(--font-mono); font-size:0.78rem; text-transform:uppercase; <?= $statusFiltro === 'todos' ? 'background:var(--charcoal); color:var(--flour); border-color:var(--charcoal);' : 'background:var(--white); color:var(--text-dark);' ?>">
    Todos (<?= array_sum($contagens) ?>)
  </a>
  <?php foreach ($statusValidos as $st): ?>
    <a href="/admin/pedidos.php?status=<?= $st ?>" style="display:inline-flex; padding:9px 16px; border-radius:100px; border:1px solid var(--line); font-family:var(--font-mono); font-size:0.78rem; text-transform:uppercase; <?= $statusFiltro === $st ? 'background:var(--charcoal); color:var(--flour); border-color:var(--charcoal);' : 'background:var(--white); color:var(--text-dark);' ?>">
      <?= statusLabel($st) ?> (<?= $contagens[$st] ?? 0 ?>)
    </a>
  <?php endforeach; ?>
</div>

<div class="table-card">
  <?php if (!$pedidos): ?>
    <div class="empty-state"><p>Nenhum pedido encontrado para este filtro.</p></div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>Pedido</th><th>Cliente</th><th>Data</th><th>Pagamento</th><th>Total</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($pedidos as $p): ?>
          <tr>
            <td class="mono">#<?= (int)$p['id'] ?></td>
            <td>
              <?= limpar($p['cliente_nome']) ?>
              <div class="muted" style="font-size:0.78rem;"><?= limpar($p['cliente_telefone'] ?? '—') ?></div>
            </td>
            <td><?= formatarData($p['criado_em'], 'd/m/Y H:i') ?></td>
            <td style="text-transform:capitalize;"><?= limpar($p['forma_pagamento']) ?></td>
            <td class="mono"><?= formatarPreco($p['total']) ?></td>
            <td><span class="badge badge-<?= limpar($p['status']) ?>"><?= limpar(statusLabel($p['status'])) ?></span></td>
            <td><a href="/admin/pedido-detalhe.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm">Gerenciar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
