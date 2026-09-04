<?php
/**
 * public/admin/clientes.php
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$tituloPagina = 'Clientes';
$paginaAdminAtual = 'clientes';

$pdo = conectar();
$clientes = $pdo->query("SELECT u.*,
                          (SELECT COUNT(*) FROM pedidos p WHERE p.usuario_id = u.id) AS total_pedidos,
                          (SELECT COALESCE(SUM(total),0) FROM pedidos p WHERE p.usuario_id = u.id AND p.status != 'cancelado') AS total_gasto
                          FROM usuarios u WHERE u.tipo = 'cliente' ORDER BY u.criado_em DESC")->fetchAll();

include __DIR__ . '/../../app/Views/admin/header.php';
?>

<div class="flex-between" style="margin-bottom: 18px;">
  <h3 style="text-transform:none;">Clientes cadastrados (<?= count($clientes) ?>)</h3>
</div>

<div class="table-card">
  <?php if (!$clientes): ?>
    <div class="empty-state"><p>Nenhum cliente cadastrado ainda.</p></div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>Cliente</th><th>Contato</th><th>Pedidos</th><th>Total gasto</th><th>Desde</th></tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c): ?>
          <tr>
            <td><strong><?= limpar($c['nome']) ?></strong></td>
            <td>
              <?= limpar($c['email']) ?>
              <div class="muted" style="font-size:0.8rem;"><?= limpar($c['telefone'] ?? '—') ?></div>
            </td>
            <td class="mono"><?= (int)$c['total_pedidos'] ?></td>
            <td class="mono"><?= formatarPreco((float)$c['total_gasto']) ?></td>
            <td><?= formatarData($c['criado_em'], 'd/m/Y') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
