<?php
/**
 * public/admin/dashboard.php
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$tituloPagina = 'Dashboard';
$paginaAdminAtual = 'dashboard';

$pdo = conectar();

$hoje = date('Y-m-d');
$inicioMes = date('Y-m-01');

$pedidosHoje = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(criado_em) = :hoje");
$pedidosHoje->execute([':hoje' => $hoje]);
$totalPedidosHoje = (int)$pedidosHoje->fetchColumn();

$faturamentoMes = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE criado_em >= :inicio AND status != 'cancelado'");
$faturamentoMes->execute([':inicio' => $inicioMes]);
$totalFaturamentoMes = (float)$faturamentoMes->fetchColumn();

$pedidosPendentes = (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE status IN ('pendente','preparo','saiu_entrega')")->fetchColumn();
$totalClientes     = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente'")->fetchColumn();
$totalProdutos      = (int)$pdo->query("SELECT COUNT(*) FROM produtos WHERE disponivel = 1")->fetchColumn();
$mensagensNaoLidas  = (int)$pdo->query("SELECT COUNT(*) FROM contatos WHERE respondido = 0")->fetchColumn();

$recentes = $pdo->query("SELECT p.*, u.nome AS cliente_nome FROM pedidos p
                          JOIN usuarios u ON u.id = p.usuario_id
                          ORDER BY p.criado_em DESC LIMIT 8")->fetchAll();

include __DIR__ . '/../../app/Views/admin/header.php';
?>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="eyebrow">Hoje</div>
    <div class="kpi-value"><?= $totalPedidosHoje ?></div>
    <div class="kpi-sub">pedidos recebidos</div>
  </div>
  <div class="kpi-card">
    <div class="eyebrow">Faturamento</div>
    <div class="kpi-value" style="font-size: 1.7rem;"><?= formatarPreco($totalFaturamentoMes) ?></div>
    <div class="kpi-sub">acumulado no mês</div>
  </div>
  <div class="kpi-card">
    <div class="eyebrow">Em andamento</div>
    <div class="kpi-value"><?= $pedidosPendentes ?></div>
    <div class="kpi-sub">pedidos pendentes / em preparo / a caminho</div>
  </div>
  <div class="kpi-card">
    <div class="eyebrow">Cardápio</div>
    <div class="kpi-value"><?= $totalProdutos ?></div>
    <div class="kpi-sub">produtos disponíveis</div>
  </div>
  <div class="kpi-card">
    <div class="eyebrow">Base de clientes</div>
    <div class="kpi-value"><?= $totalClientes ?></div>
    <div class="kpi-sub">clientes cadastrados</div>
  </div>
  <div class="kpi-card">
    <div class="eyebrow">Contato</div>
    <div class="kpi-value"><?= $mensagensNaoLidas ?></div>
    <div class="kpi-sub">mensagens não respondidas</div>
  </div>
</div>

<div class="flex-between" style="margin-bottom: 18px;">
  <h3 style="text-transform:none;">Pedidos recentes</h3>
  <a href="/admin/pedidos.php" class="btn btn-secondary btn-sm">Ver todos</a>
</div>

<div class="table-card">
  <?php if (!$recentes): ?>
    <div class="empty-state"><p>Nenhum pedido registrado ainda.</p></div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>Pedido</th><th>Cliente</th><th>Data</th><th>Total</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentes as $p): ?>
          <tr>
            <td class="mono">#<?= (int)$p['id'] ?></td>
            <td><?= limpar($p['cliente_nome']) ?></td>
            <td><?= formatarData($p['criado_em'], 'd/m/Y H:i') ?></td>
            <td class="mono"><?= formatarPreco($p['total']) ?></td>
            <td><span class="badge badge-<?= limpar($p['status']) ?>"><?= limpar(statusLabel($p['status'])) ?></span></td>
            <td><a href="/admin/pedido-detalhe.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
