<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Cardápio';
$paginaAtual  = 'produtos';

$produtos   = [];
$categorias = [];
$erroBanco  = false;

try {
    $pdo = conectar();
    $categorias = $pdo->query("SELECT * FROM categorias ORDER BY id")->fetchAll();
    $produtos   = $pdo->query("SELECT p.*, c.slug AS categoria_slug, c.nome AS categoria_nome
                                FROM produtos p JOIN categorias c ON c.id = p.categoria_id
                                WHERE p.disponivel = 1
                                ORDER BY c.id, p.nome")->fetchAll();
} catch (Throwable $e) {
    $erroBanco = true;
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="page-banner">
  <div class="container">
    <div class="eyebrow">Cardápio</div>
    <h1>Escolha o sabor de hoje</h1>
    <p class="lede">Pizzas assadas na hora, bebidas geladas e sobremesas pra fechar com chave de ouro. Adicione ao carrinho e finalize o pedido quando quiser.</p>
  </div>
</section>
<div class="crust-divider on-charcoal"></div>

<section class="section">
  <div class="container">

    <?php if ($erroBanco): ?>
      <div class="empty-state">
        <p>Não foi possível carregar o cardápio agora. Verifique se o banco de dados foi importado (<code>database/schema.sql</code>) e se <code>app/Config/config.php</code> está configurado corretamente.</p>
      </div>
    <?php else: ?>

      <div class="filter-tabs" id="filterTabs" style="margin-bottom: 36px;">
        <button type="button" class="active" data-filtro="todos">Todos</button>
        <?php foreach ($categorias as $cat): ?>
          <button type="button" data-filtro="<?= limpar($cat['slug']) ?>"><?= limpar($cat['nome']) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="product-grid" id="productGrid">
        <?php foreach ($produtos as $p): $imgUrl = urlImagemProduto($p['imagem'] ?? null); ?>
          <div class="product-card" data-categoria="<?= limpar($p['categoria_slug']) ?>">
            <div class="product-media">
              <span class="product-tag"><?= limpar($p['categoria_nome']) ?></span>
              <?php if ($imgUrl): ?>
                <img src="<?= limpar($imgUrl) ?>" alt="<?= limpar($p['nome']) ?>" loading="lazy">
              <?php else: ?>
                <svg viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="28" fill="#F1E9D8" stroke="#E8A33D" stroke-width="2.5"/><circle cx="32" cy="32" r="20" fill="#E8A33D" opacity="0.35"/><circle cx="24" cy="26" r="4" fill="#D8472B"/><circle cx="38" cy="22" r="3.4" fill="#D8472B"/><circle cx="40" cy="38" r="4" fill="#D8472B"/><circle cx="22" cy="40" r="3" fill="#4F6B3F"/></svg>
              <?php endif; ?>
            </div>
            <div class="product-body">
              <h3><?= limpar($p['nome']) ?></h3>
              <p class="product-desc"><?= limpar($p['descricao']) ?></p>
              <div class="product-footer">
                <span class="price-tag"><?= formatarPreco($p['preco']) ?></span>
                <div class="qty-stepper" data-qty-wrap>
                  <button type="button" data-qty-menos>−</button>
                  <span data-qty-valor>1</span>
                  <button type="button" data-qty-mais>+</button>
                </div>
              </div>
              <button type="button" class="add-btn"
                data-add-produto
                data-id="<?= (int)$p['id'] ?>"
                data-nome="<?= limpar($p['nome']) ?>"
                data-preco="<?= (float)$p['preco'] ?>">
                Adicionar ao carrinho
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Filtro por categoria
  const tabs = document.querySelectorAll('#filterTabs button');
  const cards = document.querySelectorAll('#productGrid .product-card');
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      const filtro = tab.dataset.filtro;
      cards.forEach((card) => {
        card.style.display = (filtro === 'todos' || card.dataset.categoria === filtro) ? '' : 'none';
      });
    });
  });

  // Stepper de quantidade + adicionar ao carrinho
  document.querySelectorAll('.product-card').forEach((card) => {
    const valorEl = card.querySelector('[data-qty-valor]');
    let qtd = 1;
    card.querySelector('[data-qty-mais]')?.addEventListener('click', () => {
      qtd = Math.min(qtd + 1, 20);
      valorEl.textContent = qtd;
    });
    card.querySelector('[data-qty-menos]')?.addEventListener('click', () => {
      qtd = Math.max(qtd - 1, 1);
      valorEl.textContent = qtd;
    });
    const btnAdd = card.querySelector('[data-add-produto]');
    btnAdd?.addEventListener('click', () => {
      Carrinho.adicionar({
        id: btnAdd.dataset.id,
        nome: btnAdd.dataset.nome,
        preco: btnAdd.dataset.preco,
      }, qtd);

      btnAdd.textContent = 'Adicionado ✓';
      btnAdd.classList.add('added');
      setTimeout(() => {
        btnAdd.textContent = 'Adicionar ao carrinho';
        btnAdd.classList.remove('added');
      }, 1200);

      qtd = 1;
      valorEl.textContent = qtd;
    });
  });
});
</script>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
