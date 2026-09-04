<?php
require_once __DIR__ . '/../app/bootstrap.php';

$tituloPagina = 'Início';
$paginaAtual  = 'home';

// Busca alguns produtos em destaque (pizzas salgadas) para a vitrine da home.
$destaques = [];
try {
    $pdo = conectar();
    $stmt = $pdo->query("SELECT p.*, c.nome AS categoria_nome FROM produtos p
                          JOIN categorias c ON c.id = p.categoria_id
                          WHERE p.disponivel = 1 AND c.slug = 'pizzas-salgadas'
                          ORDER BY p.id LIMIT 4");
    $destaques = $stmt->fetchAll();
} catch (Throwable $e) {
    // Banco ainda não configurado: a home funciona normalmente sem os destaques.
    $destaques = [];
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="hero">
  <div class="hero-flame"></div>
  <div class="container hero-grid">
    <div>
      <div class="eyebrow">Forno a lenha · desde sempre no bairro</div>
      <h1>Pizza de verdade, assada<br>na brasa do seu bairro.</h1>
      <p class="lede">Massa de fermentação lenta de 48h, molho feito todo dia e ingredientes selecionados. Peça online e acompanhe cada etapa do seu pedido até a porta de casa.</p>
      <div class="hero-actions">
        <a href="/produtos.php" class="btn btn-primary">Ver cardápio</a>
        <a href="/sobre.php" class="btn btn-ghost-light">Conhecer a história</a>
      </div>
      <div class="stat-row">
        <div class="stat"><b>48h</b><span>Fermentação da massa</span></div>
        <div class="stat"><b>12</b><span>Sabores no cardápio</span></div>
        <div class="stat"><b>30 min</b><span>Tempo médio de entrega</span></div>
      </div>
    </div>
    <div class="oven-art">
      <svg viewBox="0 0 360 360" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="180" cy="320" rx="140" ry="14" fill="#000" opacity="0.25"/>
        <path d="M70 220c0-70 50-130 110-130s110 60 110 130v60a20 20 0 01-20 20H90a20 20 0 01-20-20v-60z" fill="#3C2C24" stroke="#E8A33D" stroke-width="3"/>
        <ellipse cx="180" cy="230" rx="78" ry="56" fill="#1A1310"/>
        <circle cx="180" cy="244" r="46" fill="#E8A33D" opacity="0.18"/>
        <path d="M180 110c-6 18-26 24-26 46a26 26 0 0052 0c0-10-5-15-7-19 4 2 12 9 12 22a31 31 0 01-62 0c0-28 23-35 31-49z" fill="#D8472B"/>
        <circle cx="180" cy="244" r="34" fill="#F1E9D8"/>
        <circle cx="166" cy="234" r="6" fill="#D8472B"/>
        <circle cx="194" cy="240" r="5" fill="#D8472B"/>
        <circle cx="178" cy="256" r="5.5" fill="#D8472B"/>
        <circle cx="200" cy="226" r="4" fill="#4F6B3F"/>
        <circle cx="160" cy="252" r="4" fill="#4F6B3F"/>
      </svg>
    </div>
  </div>
</section>
<div class="crust-divider on-charcoal"></div>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <div class="eyebrow">Vitrine do dia</div>
        <h2>Os mais pedidos</h2>
      </div>
      <a href="/produtos.php" class="btn btn-secondary btn-sm">Cardápio completo</a>
    </div>

    <?php if ($destaques): ?>
    <div class="product-grid">
      <?php foreach ($destaques as $p): $imgUrl = urlImagemProduto($p['imagem'] ?? null); ?>
        <div class="product-card">
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
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty-state">
        <p>O cardápio será exibido aqui assim que o banco de dados estiver configurado. Veja <code>database/schema.sql</code>.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-charcoal">
  <div class="container">
    <div class="eyebrow">Como funciona</div>
    <h2 style="margin-bottom: 40px;">Do forno até a sua casa</h2>
    <div class="timeline" style="max-width: 640px;">
      <div class="timeline-step">
        <div class="timeline-num">01</div>
        <div><h4 style="color:#FBF6EC; text-transform:none;">Monte seu pedido</h4><p>Escolha os sabores no cardápio e adicione ao carrinho.</p></div>
      </div>
      <div class="timeline-step">
        <div class="timeline-num">02</div>
        <div><h4 style="color:#FBF6EC; text-transform:none;">Confirme entrega e pagamento</h4><p>Informe o endereço e escolha como prefere pagar.</p></div>
      </div>
      <div class="timeline-step">
        <div class="timeline-num">03</div>
        <div><h4 style="color:#FBF6EC; text-transform:none;">Acompanhe em tempo real</h4><p>Veja o status do pedido em "Meus Pedidos": preparo, saída e entrega.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container text-center">
    <h2>Bateu a fome?</h2>
    <p class="lede" style="margin: 0 auto 28px;">Faça seu pedido agora e acompanhe tudo pelo site.</p>
    <a href="/produtos.php" class="btn btn-primary">Fazer pedido</a>
  </div>
</section>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
