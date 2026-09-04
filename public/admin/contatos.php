<?php
/**
 * public/admin/contatos.php
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_respondido'])) {
    if (validarCSRF($_POST['csrf_token'] ?? null)) {
        $upd = $pdo->prepare('UPDATE contatos SET respondido = 1 WHERE id = :id');
        $upd->execute([':id' => (int)$_POST['marcar_respondido']]);
        definirFlash('success', 'Mensagem marcada como respondida.');
    }
    header('Location: /admin/contatos.php');
    exit;
}

$tituloPagina = 'Mensagens de contato';
$paginaAdminAtual = 'contatos';

$mensagens = $pdo->query('SELECT * FROM contatos ORDER BY respondido ASC, criado_em DESC')->fetchAll();

include __DIR__ . '/../../app/Views/admin/header.php';
?>

<div class="flex-between" style="margin-bottom: 18px;">
  <h3 style="text-transform:none;">Mensagens recebidas (<?= count($mensagens) ?>)</h3>
</div>

<?php if (!$mensagens): ?>
  <div class="empty-state"><p>Nenhuma mensagem recebida ainda.</p></div>
<?php else: ?>
  <?php foreach ($mensagens as $m): ?>
    <div class="table-card" style="padding: 20px 24px; margin-bottom: 16px; <?= $m['respondido'] ? 'opacity:.6;' : '' ?>">
      <div class="flex-between" style="flex-wrap:wrap; gap:10px; margin-bottom: 10px;">
        <div>
          <strong><?= limpar($m['nome']) ?></strong>
          <span class="muted" style="font-size:0.85rem;"> · <?= limpar($m['email']) ?></span>
          <?php if ($m['telefone']): ?><span class="muted" style="font-size:0.85rem;"> · <?= limpar($m['telefone']) ?></span><?php endif; ?>
        </div>
        <span class="badge <?= $m['respondido'] ? 'badge-entregue' : 'badge-pendente' ?>">
          <?= $m['respondido'] ? 'Respondida' : 'Pendente' ?>
        </span>
      </div>
      <p class="muted" style="font-size: 0.8rem; margin-bottom: 4px;"><?= limpar($m['assunto']) ?> · <?= formatarData($m['criado_em']) ?></p>
      <p style="margin-bottom: 14px;"><?= nl2br(limpar($m['mensagem'])) ?></p>
      <?php if (!$m['respondido']): ?>
        <form method="post" action="/admin/contatos.php">
          <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
          <input type="hidden" name="marcar_respondido" value="<?= (int)$m['id'] ?>">
          <button type="submit" class="btn btn-secondary btn-sm">Marcar como respondida</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
