<?php
/**
 * app/Views/partials/header.php
 *
 * Espera que a página que o inclui já tenha dado require_once em
 * app/bootstrap.php e definido:
 *   $tituloPagina (string) - título da aba do navegador
 *   $paginaAtual  (string) - slug usado para destacar o item ativo no menu
 */

$flash = obterFlash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= limpar($tituloPagina ?? APP_NAME) ?> · <?= limpar(APP_NAME) ?></title>
<meta name="description" content="Forno do Bairro - pizzaria artesanal de bairro. Peça online, acompanhe seu pedido e conheça nosso cardápio.">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="nav-wrap">
    <a href="/index.php" class="brand">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="24" cy="24" r="22" stroke="#E8A33D" stroke-width="2.5"/>
        <path d="M24 10c-1 4-5 5.5-5 10a5 5 0 0010 0c0-2-1-3-1.5-4.2.8.4 2.5 1.8 2.5 4.7a6 6 0 01-12 0c0-5.8 4.8-7.3 6-10.5z" fill="#D8472B"/>
        <path d="M14 32c2-1.5 4-1.5 5 0s3 1.5 5 0 4-1.5 5 0 3 1.5 5 0" stroke="#FBF6EC" stroke-width="2" stroke-linecap="round"/>
      </svg>
      <span class="brand-name">Forno<span>·</span>Bairro</span>
    </a>

    <nav class="nav-links" id="navLinks">
      <a href="/index.php" class="<?= ($paginaAtual ?? '') === 'home' ? 'active' : '' ?>">Início</a>
      <a href="/sobre.php" class="<?= ($paginaAtual ?? '') === 'sobre' ? 'active' : '' ?>">Sobre</a>
      <a href="/produtos.php" class="<?= ($paginaAtual ?? '') === 'produtos' ? 'active' : '' ?>">Cardápio</a>
      <a href="/contato.php" class="<?= ($paginaAtual ?? '') === 'contato' ? 'active' : '' ?>">Contato</a>
      <?php if (estaLogado()): ?>
        <a href="/meus-pedidos.php" class="<?= ($paginaAtual ?? '') === 'pedidos' ? 'active' : '' ?>">Meus Pedidos</a>
      <?php endif; ?>
    </nav>

    <div class="nav-actions">
      <a href="/pedido.php" class="nav-icon-btn" title="Carrinho" aria-label="Carrinho">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.6 13.4a2 2 0 002 1.6h8.8a2 2 0 002-1.6L21 7H6"/><circle cx="9" cy="21" r="1.5" fill="currentColor"/><circle cx="18" cy="21" r="1.5" fill="currentColor"/></svg>
        <span class="cart-badge" style="display:none">0</span>
      </a>

      <?php if (estaLogado()): ?>
        <a href="/minha-conta.php" class="nav-icon-btn" title="Minha conta" aria-label="Minha conta">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
        </a>
      <?php else: ?>
        <a href="/login.php" class="btn btn-ghost-light btn-sm">Entrar</a>
      <?php endif; ?>

      <button class="nav-toggle" id="navToggle" aria-label="Abrir menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
    </div>
  </div>
</header>

<?php if ($flash): ?>
<div class="container" style="padding-top: 24px;">
  <div class="alert alert-<?= limpar($flash['tipo']) ?>" data-auto-dismiss>
    <?= limpar($flash['mensagem']) ?>
  </div>
</div>
<?php endif; ?>
