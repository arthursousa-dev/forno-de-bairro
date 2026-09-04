<?php
/**
 * public/admin/produto-form.php
 * Formulário único para criação E edição de produtos
 * (modo determinado pela presença de ?id= na URL).
 */
require_once __DIR__ . '/../../app/bootstrap.php';
exigirAdmin();

$pdo = conectar();

$produtoId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : null);
$editando  = $produtoId !== null;

$tituloPagina = $editando ? 'Editar produto' : 'Novo produto';
$paginaAdminAtual = 'produtos';

$categorias = $pdo->query('SELECT * FROM categorias ORDER BY id')->fetchAll();

$valores = [
    'nome' => '', 'categoria_id' => '', 'descricao' => '',
    'preco' => '', 'disponivel' => 1, 'imagem' => null,
];
$erros = [];

if ($editando) {
    $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $produtoId]);
    $produtoExistente = $stmt->fetch();

    if (!$produtoExistente) {
        definirFlash('error', 'Produto não encontrado.');
        header('Location: /admin/produtos.php');
        exit;
    }
    $valores = $produtoExistente;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $valores['nome']         = trim($_POST['nome'] ?? '');
    $valores['categoria_id'] = (int)($_POST['categoria_id'] ?? 0);
    $valores['descricao']    = trim($_POST['descricao'] ?? '');
    $valores['preco']        = str_replace(',', '.', trim($_POST['preco'] ?? ''));
    $valores['disponivel']   = isset($_POST['disponivel']) ? 1 : 0;
    $removerImagem            = isset($_POST['remover_imagem']);

    if (mb_strlen($valores['nome']) < 3) {
        $erros[] = 'Informe o nome do produto (mínimo 3 caracteres).';
    }
    if ($valores['categoria_id'] <= 0) {
        $erros[] = 'Selecione uma categoria.';
    }
    if (!is_numeric($valores['preco']) || (float)$valores['preco'] <= 0) {
        $erros[] = 'Informe um preço válido, maior que zero.';
    }

    $novaImagem = null;
    if (!$erros) {
        try {
            if (!empty($_FILES['imagem']['name'])) {
                $novaImagem = processarUploadImagemProduto($_FILES['imagem']);
            }
        } catch (RuntimeException $e) {
            $erros[] = $e->getMessage();
        }
    }

    if (!$erros) {
        try {
            $imagemFinal = $valores['imagem'] ?? null;

            if ($novaImagem) {
                removerImagemProduto($imagemFinal);
                $imagemFinal = $novaImagem;
            } elseif ($removerImagem) {
                removerImagemProduto($imagemFinal);
                $imagemFinal = null;
            }

            if ($editando) {
                $stmt = $pdo->prepare('UPDATE produtos SET categoria_id=:cat, nome=:nome, descricao=:desc,
                                        preco=:preco, imagem=:imagem, disponivel=:disp WHERE id=:id');
                $stmt->execute([
                    ':cat' => $valores['categoria_id'], ':nome' => $valores['nome'],
                    ':desc' => $valores['descricao'] ?: null, ':preco' => $valores['preco'],
                    ':imagem' => $imagemFinal, ':disp' => $valores['disponivel'], ':id' => $produtoId,
                ]);
                definirFlash('success', 'Produto atualizado com sucesso!');
            } else {
                $stmt = $pdo->prepare('INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, disponivel)
                                        VALUES (:cat, :nome, :desc, :preco, :imagem, :disp)');
                $stmt->execute([
                    ':cat' => $valores['categoria_id'], ':nome' => $valores['nome'],
                    ':desc' => $valores['descricao'] ?: null, ':preco' => $valores['preco'],
                    ':imagem' => $imagemFinal, ':disp' => $valores['disponivel'],
                ]);
                definirFlash('success', 'Produto cadastrado com sucesso!');
            }

            header('Location: /admin/produtos.php');
            exit;
        } catch (Throwable $e) {
            $erros[] = 'Não foi possível salvar o produto agora. Tente novamente.';
        }
    }
}

include __DIR__ . '/../../app/Views/admin/header.php';
?>

<div style="max-width: 720px;">
  <?php if ($erros): ?>
    <div class="alert alert-error">
      <ul style="margin:0; padding-left: 18px;">
        <?php foreach ($erros as $erro): ?><li><?= limpar($erro) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="post" action="/admin/produto-form.php<?= $editando ? '?id=' . $produtoId : '' ?>" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
      <?php if ($editando): ?><input type="hidden" name="id" value="<?= $produtoId ?>"><?php endif; ?>

      <div class="form-grid">
        <div class="field">
          <label for="nome">Nome do produto <span class="req">*</span></label>
          <input type="text" id="nome" name="nome" value="<?= limpar($valores['nome']) ?>" placeholder="Ex: Margherita">
        </div>

        <div class="form-grid two-col">
          <div class="field">
            <label for="categoria_id">Categoria <span class="req">*</span></label>
            <select id="categoria_id" name="categoria_id">
              <option value="">Selecione...</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (int)$valores['categoria_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                  <?= limpar($cat['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="preco">Preço (R$) <span class="req">*</span></label>
            <input type="text" id="preco" name="preco" value="<?= limpar((string)$valores['preco']) ?>" placeholder="39.90">
          </div>
        </div>

        <div class="field">
          <label for="descricao">Descrição</label>
          <textarea id="descricao" name="descricao" placeholder="Ingredientes e detalhes do produto..."><?= limpar($valores['descricao'] ?? '') ?></textarea>
        </div>

        <div class="field">
          <label for="imagem">Foto do produto</label>
          <?php $imgAtual = urlImagemProduto($valores['imagem'] ?? null); ?>
          <?php if ($imgAtual): ?>
            <div style="display:flex; align-items:center; gap:14px; margin-bottom:10px;">
              <img src="<?= limpar($imgAtual) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--line);">
              <label class="checkbox-row" style="align-items:center;">
                <input type="checkbox" name="remover_imagem" value="1"> Remover imagem atual
              </label>
            </div>
          <?php endif; ?>
          <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
          <span class="hint">JPG, PNG ou WEBP — até 3 MB. Se nenhuma imagem for enviada, um ícone ilustrativo será usado.</span>
        </div>

        <div class="checkbox-row">
          <input type="checkbox" id="disponivel" name="disponivel" <?= $valores['disponivel'] ? 'checked' : '' ?>>
          <label for="disponivel">Produto disponível para venda no cardápio</label>
        </div>
      </div>

      <div class="form-foot">
        <a href="/admin/produtos.php" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><?= $editando ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../app/Views/admin/footer.php'; ?>
