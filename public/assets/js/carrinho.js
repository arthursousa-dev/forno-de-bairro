/**
 * Forno do Bairro — Carrinho de compras
 * Persistido em localStorage no navegador do cliente.
 * Ao finalizar o pedido, os itens são enviados ao back-end PHP
 * (pedido.php) via campo oculto em formato JSON.
 */

const Carrinho = (() => {
  const CHAVE = 'forno_carrinho_v1';

  function obter() {
    try {
      const dados = localStorage.getItem(CHAVE);
      return dados ? JSON.parse(dados) : [];
    } catch (e) {
      return [];
    }
  }

  function salvar(itens) {
    localStorage.setItem(CHAVE, JSON.stringify(itens));
    atualizarBadge();
    document.dispatchEvent(new CustomEvent('carrinho:atualizado', { detail: itens }));
  }

  function adicionar(produto, quantidade = 1) {
    const itens = obter();
    const existente = itens.find((i) => i.id === produto.id);
    if (existente) {
      existente.quantidade += quantidade;
    } else {
      itens.push({
        id: produto.id,
        nome: produto.nome,
        preco: parseFloat(produto.preco),
        quantidade,
      });
    }
    salvar(itens);
  }

  function atualizarQuantidade(id, quantidade) {
    let itens = obter();
    if (quantidade <= 0) {
      itens = itens.filter((i) => i.id !== id);
    } else {
      const item = itens.find((i) => i.id === id);
      if (item) item.quantidade = quantidade;
    }
    salvar(itens);
  }

  function remover(id) {
    const itens = obter().filter((i) => i.id !== id);
    salvar(itens);
  }

  function limpar() {
    salvar([]);
  }

  function total() {
    return obter().reduce((soma, i) => soma + i.preco * i.quantidade, 0);
  }

  function contagem() {
    return obter().reduce((soma, i) => soma + i.quantidade, 0);
  }

  function formatarMoeda(valor) {
    return 'R$ ' + valor.toFixed(2).replace('.', ',');
  }

  function atualizarBadge() {
    const badges = document.querySelectorAll('.cart-badge');
    const qtd = contagem();
    badges.forEach((b) => {
      b.textContent = qtd;
      b.style.display = qtd > 0 ? 'flex' : 'none';
    });
  }

  /** Renderiza o "ticket" do carrinho (usado em pedido.php). */
  function renderizarTicket(containerId, totalId, formId) {
    const container = document.getElementById(containerId);
    const totalEl = document.getElementById(totalId);
    if (!container) return;

    const itens = obter();

    if (itens.length === 0) {
      container.innerHTML = '<div class="cart-empty">Seu carrinho está vazio.<br>Adicione itens do cardápio ao lado.</div>';
    } else {
      container.innerHTML = itens.map((item) => `
        <div class="cart-line">
          <div>
            <div class="name">${item.quantidade}x ${item.nome}</div>
            <div class="meta">${formatarMoeda(item.preco)} / un.</div>
          </div>
          <div style="text-align:right">
            <div class="mono">${formatarMoeda(item.preco * item.quantidade)}</div>
            <button type="button" class="remove-line" data-remover="${item.id}">remover</button>
          </div>
        </div>
      `).join('');
    }

    if (totalEl) totalEl.textContent = formatarMoeda(total());

    container.querySelectorAll('[data-remover]').forEach((btn) => {
      btn.addEventListener('click', () => remover(btn.dataset.remover));
    });

    const form = formId ? document.getElementById(formId) : null;
    if (form) {
      const campoOculto = form.querySelector('input[name="itens_json"]');
      if (campoOculto) campoOculto.value = JSON.stringify(itens);
      const botaoEnviar = form.querySelector('[type="submit"]');
      if (botaoEnviar) botaoEnviar.disabled = itens.length === 0;
    }
  }

  document.addEventListener('DOMContentLoaded', atualizarBadge);

  return { obter, adicionar, atualizarQuantidade, remover, limpar, total, contagem, formatarMoeda, renderizarTicket, atualizarBadge };
})();
