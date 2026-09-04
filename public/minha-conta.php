<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Minha conta';
$paginaAtual  = 'conta';

exigirLogin('/login.php');

$pdo = conectar();
$erros = [];
$sucesso = false;

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: /logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'dados') {
        $nome     = trim($_POST['nome'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');

        if (mb_strlen($nome) < 3) $erros[] = 'Informe seu nome completo.';
        if ($telefone !== '' && !preg_match('/^\(\d{2}\)\s?\d{4,5}-\d{4}$/', $telefone)) {
            $erros[] = 'Telefone em formato inválido.';
        }

        if (!$erros) {
            $upd = $pdo->prepare('UPDATE usuarios SET nome = :nome, telefone = :telefone, endereco = :endereco WHERE id = :id');
            $upd->execute([
                ':nome' => $nome, ':telefone' => $telefone ?: null,
                ':endereco' => $endereco ?: null, ':id' => $usuario['id'],
            ]);
            $_SESSION['usuario_nome'] = $nome;
            $sucesso = true;
            $usuario['nome'] = $nome; $usuario['telefone'] = $telefone; $usuario['endereco'] = $endereco;
        }
    }

    if ($acao === 'senha') {
        $senhaAtual = (string)($_POST['senha_atual'] ?? '');
        $novaSenha  = (string)($_POST['nova_senha'] ?? '');
        $confirma   = (string)($_POST['confirma_nova_senha'] ?? '');

        if (!password_verify($senhaAtual, $usuario['senha_hash'])) {
            $erros[] = 'Senha atual incorreta.';
        }
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', $novaSenha)) {
            $erros[] = 'A nova senha precisa ter ao menos 6 caracteres, com letras e números.';
        }
        if ($novaSenha !== $confirma) {
            $erros[] = 'A confirmação não coincide com a nova senha.';
        }

        if (!$erros) {
            $upd = $pdo->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
            $upd->execute([':hash' => password_hash($novaSenha, PASSWORD_BCRYPT), ':id' => $usuario['id']]);
            $sucesso = true;
        }
    }
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="section" style="padding-top: 50px;">
  <div class="container" style="max-width: 680px;">
    <div class="eyebrow">Minha conta</div>
    <h1 style="font-size: 2.1rem; margin-bottom: 28px;">Olá, <?= limpar(explode(' ', $usuario['nome'])[0]) ?></h1>

    <?php if ($sucesso): ?><div class="alert alert-success">Dados atualizados com sucesso!</div><?php endif; ?>
    <?php if ($erros): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left: 18px;"><?php foreach ($erros as $e): ?><li><?= limpar($e) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>

    <div class="form-card" style="margin-bottom: 26px;">
      <h3 style="text-transform:none; margin-bottom: 18px;">Dados cadastrais</h3>
      <form id="form-dados" method="post" action="/minha-conta.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
        <input type="hidden" name="acao" value="dados">
        <div class="form-grid">
          <div class="field">
            <label for="nome">Nome completo <span class="req">*</span></label>
            <input type="text" id="nome" name="nome" value="<?= limpar($usuario['nome']) ?>" data-validate="required|min:3">
            <span class="error-msg"></span>
          </div>
          <div class="field">
            <label>E-mail</label>
            <input type="email" value="<?= limpar($usuario['email']) ?>" disabled style="opacity:.65;">
            <span class="hint">O e-mail não pode ser alterado.</span>
          </div>
          <div class="form-grid two-col">
            <div class="field">
              <label for="telefone">Telefone</label>
              <input type="text" id="telefone" name="telefone" value="<?= limpar($usuario['telefone'] ?? '') ?>" data-validate="telefone">
              <span class="error-msg"></span>
            </div>
            <div class="field">
              <label for="endereco">Endereço de entrega</label>
              <input type="text" id="endereco" name="endereco" value="<?= limpar($usuario['endereco'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div class="form-foot" style="justify-content:flex-end;">
          <button type="submit" class="btn btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>

    <div class="form-card">
      <h3 style="text-transform:none; margin-bottom: 18px;">Alterar senha</h3>
      <form id="form-senha" method="post" action="/minha-conta.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
        <input type="hidden" name="acao" value="senha">
        <div class="form-grid">
          <div class="field">
            <label for="senha_atual">Senha atual <span class="req">*</span></label>
            <input type="password" id="senha_atual" name="senha_atual" data-validate="required">
            <span class="error-msg"></span>
          </div>
          <div class="form-grid two-col">
            <div class="field">
              <label for="nova_senha">Nova senha <span class="req">*</span></label>
              <input type="password" id="nova_senha" name="nova_senha" data-validate="required|senha">
              <span class="error-msg"></span>
            </div>
            <div class="field">
              <label for="confirma_nova_senha">Confirmar nova senha <span class="req">*</span></label>
              <input type="password" id="confirma_nova_senha" name="confirma_nova_senha" data-validate="required|confirma:#nova_senha">
              <span class="error-msg"></span>
            </div>
          </div>
        </div>
        <div class="form-foot" style="justify-content:flex-end;">
          <button type="submit" class="btn btn-secondary">Atualizar senha</button>
        </div>
      </form>
    </div>

    <p class="text-center" style="margin-top: 30px;">
      <a href="/logout.php" style="color: var(--ember); font-weight:600;">Sair da conta</a>
    </p>
  </div>
</section>

<script src="/assets/js/validacao.js"></script>
<script>
Validador.iniciar('form-dados');
Validador.iniciar('form-senha');
</script>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
