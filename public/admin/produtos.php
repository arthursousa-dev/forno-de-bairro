<?php
/**
 * public/admin/produtos.php
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$tituloPagina = 'Produtos';
$paginaAdminAtual = 'produtos';

$pdo = conectar();
$produtos = $pdo->query("SELECT p.*, c.nome AS categoria_nome FROM produtos p
                          JOIN categorias c ON c.id = p.categoria_id
                          ORDER BY c.id, p.nome")->fetchAll();

include __DIR__ . '/../../app/Views/admin/header.php';
?>

<div class="flex-between" style="margin-bottom: 18px;">
  <h3 style="text-transform:none;">Cardápio (<?= count($produtos) ?>)</h3>
  <a href="/admin/produto-form.php" class="btn btn-primary btn-sm">+ Novo produto</a>
</div>

<div class="table-card">
  <?php if (!$produtos): ?>
    <div class="empty-state"><p>Nenhum produto cadastrado ainda.</p></div>
  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Status</th><th style="width:160px;"></th></tr>
      </thead>
      <tbody>
        <?php foreach ($produtos as $p): ?>
          <tr>
            <td>
              <strong><?= limpar($p['nome']) ?></strong>
              <div class="muted" style="font-size: 0.8rem;"><?= limpar(truncar($p['descricao'] ?? '', 60)) ?></div>
            </td>
            <td><?= limpar($p['categoria_nome']) ?></td>
            <td class="mono"><?= formatarPreco($p['preco']) ?></td>
            <td>
              <?php if ($p['disponivel']): ?>
                <span class="badge badge-entregue">Disponível</span>
              <?php else: ?>
                <span class="badge badge-cancelado">Indisponível</span>
              <?php endif; ?>
            </td>
            <td style="display:flex; gap:8px;">
              <a href="/admin/produto-form.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
              <form method="post" action="/admin/produto-excluir.php"
                    onsubmit="return confirm('Excluir o produto \'<?= limpar(addslashes($p['nome'])) ?>\'? Esta ação não pode ser desfeita.');">
                <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
