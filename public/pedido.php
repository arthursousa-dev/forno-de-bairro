<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Fazer pedido';
$paginaAtual  = 'pedido';

exigirLogin('/login.php');

$pdo = conectar();

$erros = [];
$enderecoValor = '';

// Pré-popula o endereço com o cadastrado, se houver.
try {
    $stmtUser = $pdo->prepare('SELECT endereco FROM usuarios WHERE id = :id');
    $stmtUser->execute([':id' => $_SESSION['usuario_id']]);
    $u = $stmtUser->fetch();
    $enderecoValor = $u['endereco'] ?? '';
} catch (Throwable $e) {
    $enderecoValor = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $enderecoValor   = trim($_POST['endereco_entrega'] ?? '');
    $formaPagamento  = $_POST['forma_pagamento'] ?? '';
    $observacoes     = trim($_POST['observacoes'] ?? '');
    $itensJson       = $_POST['itens_json'] ?? '[]';
    $itensCarrinho   = json_decode($itensJson, true);

    if (mb_strlen($enderecoValor) < 8) {
        $erros[] = 'Informe um endereço de entrega completo.';
    }
    if (!in_array($formaPagamento, ['dinheiro', 'cartao', 'pix'], true)) {
        $erros[] = 'Selecione uma forma de pagamento.';
    }
    if (!is_array($itensCarrinho) || count($itensCarrinho) === 0) {
        $erros[] = 'Seu carrinho está vazio. Adicione itens antes de finalizar o pedido.';
    }

    if (!$erros) {
        try {
            // Nunca confiar no preço enviado pelo cliente: recalcula a partir do banco.
            $idsValidados = array_map(fn($i) => (int)$i['id'], $itensCarrinho);
            $placeholders = implode(',', array_fill(0, count($idsValidados), '?'));
            $stmtProd = $pdo->prepare("SELECT id, nome, preco FROM produtos WHERE id IN ($placeholders) AND disponivel = 1");
            $stmtProd->execute($idsValidados);
            $produtosBanco = [];
            foreach ($stmtProd->fetchAll() as $row) {
                $produtosBanco[$row['id']] = $row;
            }

            $itensFinais = [];
            $total = 0;
            foreach ($itensCarrinho as $item) {
                $id  = (int)$item['id'];
                $qtd = max(1, min(50, (int)$item['quantidade']));
                if (!isset($produtosBanco[$id])) continue;
                $preco = (float)$produtosBanco[$id]['preco'];
                $subtotal = $preco * $qtd;
                $total += $subtotal;
                $itensFinais[] = ['produto_id' => $id, 'quantidade' => $qtd, 'preco' => $preco, 'subtotal' => $subtotal];
            }

            if (!$itensFinais) {
                $erros[] = 'Não foi possível validar os itens do carrinho. Tente novamente.';
            } else {
                $pdo->beginTransaction();

                $stmtPedido = $pdo->prepare('INSERT INTO pedidos (usuario_id, status, endereco_entrega, forma_pagamento, observacoes, total)
                                              VALUES (:usuario_id, "pendente", :endereco, :pagamento, :obs, :total)');
                $stmtPedido->execute([
                    ':usuario_id' => $_SESSION['usuario_id'],
                    ':endereco'   => $enderecoValor,
                    ':pagamento'  => $formaPagamento,
                    ':obs'        => $observacoes ?: null,
                    ':total'      => $total,
                ]);
                $pedidoId = (int)$pdo->lastInsertId();

                $stmtItem = $pdo->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
                                            VALUES (:pedido_id, :produto_id, :quantidade, :preco, :subtotal)');
                foreach ($itensFinais as $item) {
                    $stmtItem->execute([
                        ':pedido_id'  => $pedidoId,
                        ':produto_id' => $item['produto_id'],
                        ':quantidade' => $item['quantidade'],
                        ':preco'      => $item['preco'],
                        ':subtotal'   => $item['subtotal'],
                    ]);
                }

                $pdo->commit();

                definirFlash('success', 'Pedido #' . $pedidoId . ' realizado com sucesso! Acompanhe o status abaixo.');
                header('Location: /meus-pedidos.php?novo=1');
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erros[] = 'Não foi possível registrar seu pedido agora. Tente novamente.';
        }
    }
}

// Cardápio para a coluna principal.
$produtos = [];
try {
    $produtos = $pdo->query("SELECT p.*, c.nome AS categoria_nome FROM produtos p
                              JOIN categorias c ON c.id = p.categoria_id
                              WHERE p.disponivel = 1 ORDER BY c.id, p.nome")->fetchAll();
} catch (Throwable $e) {
    $produtos = [];
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="page-banner" style="padding: 44px 0 36px;">
  <div class="container">
    <div class="eyebrow">Seu pedido</div>
    <h1 style="font-size: 2.4rem;">Monte sua pizza dos sonhos</h1>
  </div>
</section>

<section class="section" style="padding-top: 40px;">
  <div class="container">

    <?php if ($erros): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left: 18px;">
          <?php foreach ($erros as $erro): ?><li><?= limpar($erro) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="order-layout">
      <div>
        <h3 style="text-transform:none; margin-bottom: 18px;">Cardápio</h3>
        <div class="product-grid">
          <?php foreach ($produtos as $p): $imgUrl = urlImagemProduto($p['imagem'] ?? null); ?>
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
                <button type="button" class="add-btn" data-add-produto
                  data-id="<?= (int)$p['id'] ?>" data-nome="<?= limpar($p['nome']) ?>" data-preco="<?= (float)$p['preco'] ?>">
                  Adicionar
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <div class="cart-ticket">
          <h3>Seu carrinho</h3>
          <div id="ticketItens"></div>
          <div class="cart-total-row"><span>Total</span><span id="ticketTotal" class="mono">R$ 0,00</span></div>

          <form id="form-checkout" method="post" action="/pedido.php" novalidate style="margin-top: 24px;">
            <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
            <input type="hidden" name="itens_json" value="[]">

            <div class="field" style="margin-bottom: 14px;">
              <label style="color: var(--flour);" for="endereco_entrega">Endereço de entrega <span class="req">*</span></label>
              <input type="text" id="endereco_entrega" name="endereco_entrega" value="<?= limpar($enderecoValor) ?>"
                     data-validate="required|min:8" placeholder="Rua, número, bairro" style="background: var(--flour);">
              <span class="error-msg"></span>
            </div>

            <div class="field" style="margin-bottom: 14px;">
              <label style="color: var(--flour);" for="forma_pagamento">Forma de pagamento <span class="req">*</span></label>
              <select id="forma_pagamento" name="forma_pagamento" data-validate="required" style="background: var(--flour);">
                <option value="" selected disabled>Selecione...</option>
                <option value="pix">Pix</option>
                <option value="cartao">Cartão (na entrega)</option>
                <option value="dinheiro">Dinheiro</option>
              </select>
              <span class="error-msg"></span>
            </div>

            <div class="field" style="margin-bottom: 18px;">
              <label style="color: var(--flour);" for="observacoes">Observações</label>
              <textarea id="observacoes" name="observacoes" placeholder="Ex: sem cebola, troco para R$ 50..." style="min-height: 70px; background: var(--flour);"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="btnFinalizar">Finalizar pedido</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="/assets/js/validacao.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-add-produto]').forEach((btn) => {
    btn.addEventListener('click', () => {
      Carrinho.adicionar({ id: btn.dataset.id, nome: btn.dataset.nome, preco: btn.dataset.preco }, 1);
      btn.textContent = 'Adicionado ✓';
      btn.classList.add('added');
      setTimeout(() => { btn.textContent = 'Adicionar'; btn.classList.remove('added'); }, 1000);
    });
  });

  function atualizarTicket() {
    Carrinho.renderizarTicket('ticketItens', 'ticketTotal', 'form-checkout');
  }
  atualizarTicket();
  document.addEventListener('carrinho:atualizado', atualizarTicket);

  Validador.iniciar('form-checkout', (evento, form) => {
    const itens = Carrinho.obter();
    if (itens.length === 0) {
      evento.preventDefault();
      alert('Adicione ao menos um item ao carrinho antes de finalizar.');
      return false;
    }
    form.querySelector('input[name="itens_json"]').value = JSON.stringify(itens);
  });
});
</script>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
