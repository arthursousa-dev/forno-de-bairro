<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Sobre';
$paginaAtual  = 'sobre';
include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="page-banner">
  <div class="container">
    <div class="eyebrow">Nossa história</div>
    <h1>Um forno aceso há três gerações.</h1>
    <p class="lede">O Forno do Bairro nasceu de uma receita de família e da vontade de levar pizza de verdade para a vizinhança — sem pressa, sem atalho, sem perder a essência do forno a lenha.</p>
  </div>
</section>
<div class="crust-divider on-charcoal"></div>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <div class="eyebrow">O que nos move</div>
        <h2>Receita simples, feita com cuidado</h2>
      </div>
    </div>
    <div class="value-grid">
      <div class="value-card">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3c-1 4-5 5-5 9a5 5 0 0010 0c0-2-1-3-1.5-4.5C16.3 8 18 9.5 18 12a6 6 0 01-12 0c0-5.5 4.5-7 6-9z"/></svg>
        <h3 style="text-transform:none;">Fermentação lenta</h3>
        <p class="muted" style="font-size:0.9rem;">Nossa massa descansa por 48 horas, o que deixa a crosta leve, aerada e fácil de digerir.</p>
      </div>
      <div class="value-card">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        <h3 style="text-transform:none;">Tudo feito no dia</h3>
        <p class="muted" style="font-size:0.9rem;">Molho, massas e recheios são preparados diariamente — nada fica de um dia para o outro.</p>
      </div>
      <div class="value-card">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 11l9-7 9 7"/><path d="M5 10v9a1 1 0 001 1h12a1 1 0 001-1v-9"/></svg>
        <h3 style="text-transform:none;">Daqui do bairro</h3>
        <p class="muted" style="font-size:0.9rem;">Trabalhamos com fornecedores locais sempre que possível, valorizando quem produz por perto.</p>
      </div>
      <div class="value-card">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12a8 8 0 11-16 0 8 8 0 0116 0z"/><path d="M12 8v4l3 2"/></svg>
        <h3 style="text-transform:none;">Entrega no tempo certo</h3>
        <p class="muted" style="font-size:0.9rem;">Acompanhe cada etapa do pedido pelo site, do forno até a sua porta.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section-charcoal">
  <div class="container" style="max-width: 760px;">
    <div class="eyebrow">Nossa equipe</div>
    <h2>Quem coloca a mão na massa</h2>
    <p>O Forno do Bairro é tocado por uma equipe pequena que acredita que pizza boa não tem pressa. Da escolha dos ingredientes ao acompanhamento do seu pedido, cada etapa é pensada para que você sinta o cuidado em cada fatia.</p>
  </div>
</section>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
