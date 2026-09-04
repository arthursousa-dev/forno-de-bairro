<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Criar conta';
$paginaAtual  = 'cadastro';

if (estaLogado()) {
    header('Location: /minha-conta.php');
    exit;
}

$erros = [];
$valores = ['nome' => '', 'email' => '', 'telefone' => '', 'endereco' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $valores['nome']     = trim($_POST['nome'] ?? '');
    $valores['email']    = trim($_POST['email'] ?? '');
    $valores['telefone'] = trim($_POST['telefone'] ?? '');
    $valores['endereco'] = trim($_POST['endereco'] ?? '');
    $senha               = (string)($_POST['senha'] ?? '');
    $confirmaSenha        = (string)($_POST['confirma_senha'] ?? '');
    $aceitouTermos        = isset($_POST['aceite']);

    if (mb_strlen($valores['nome']) < 3) {
        $erros[] = 'Informe seu nome completo.';
    }
    if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($valores['telefone'] !== '' && !preg_match('/^\(\d{2}\)\s?\d{4,5}-\d{4}$/', $valores['telefone'])) {
        $erros[] = 'Telefone em formato inválido. Use (00) 00000-0000.';
    }
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', $senha)) {
        $erros[] = 'A senha precisa ter ao menos 6 caracteres, com letras e números.';
    }
    if ($senha !== $confirmaSenha) {
        $erros[] = 'As senhas não coincidem.';
    }
    if (!$aceitouTermos) {
        $erros[] = 'Você precisa aceitar os termos para criar a conta.';
    }

    if (!$erros) {
        try {
            $pdo = conectar();

            $verifica = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
            $verifica->execute([':email' => $valores['email']]);
            if ($verifica->fetch()) {
                $erros[] = 'Este e-mail já está cadastrado. Tente fazer login.';
            } else {
                $hash = password_hash($senha, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, tipo)
                                        VALUES (:nome, :email, :senha, :telefone, :endereco, "cliente")');
                $stmt->execute([
                    ':nome'     => $valores['nome'],
                    ':email'    => $valores['email'],
                    ':senha'    => $hash,
                    ':telefone' => $valores['telefone'] ?: null,
                    ':endereco' => $valores['endereco'] ?: null,
                ]);

                $novoId = (int)$pdo->lastInsertId();
                $_SESSION['usuario_id']   = $novoId;
                $_SESSION['usuario_nome'] = $valores['nome'];
                $_SESSION['usuario_tipo'] = 'cliente';

                definirFlash('success', 'Conta criada com sucesso! Bem-vindo(a), ' . $valores['nome'] . '.');
                header('Location: /index.php');
                exit;
            }
        } catch (Throwable $e) {
            $erros[] = 'Não foi possível concluir o cadastro agora. Tente novamente em instantes.';
        }
    }
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="section" style="padding-top: 56px;">
  <div class="container" style="max-width: 600px;">
    <div class="eyebrow">Crie sua conta</div>
    <h1 style="font-size: 2.1rem; margin-bottom: 28px;">Cadastre-se no Forno do Bairro</h1>

    <?php if ($erros): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left: 18px;">
          <?php foreach ($erros as $erro): ?><li><?= limpar($erro) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <form id="form-cadastro" method="post" action="/cadastro.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">

        <div class="form-grid">
          <div class="field">
            <label for="nome">Nome completo <span class="req">*</span></label>
            <input type="text" id="nome" name="nome" value="<?= limpar($valores['nome']) ?>"
                   data-validate="required|min:3" placeholder="Seu nome completo">
            <span class="error-msg"></span>
          </div>

          <div class="field">
            <label for="email">E-mail <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="<?= limpar($valores['email']) ?>"
                   data-validate="required|email" placeholder="voce@email.com">
            <span class="error-msg"></span>
          </div>

          <div class="form-grid two-col">
            <div class="field">
              <label for="telefone">Telefone</label>
              <input type="text" id="telefone" name="telefone" value="<?= limpar($valores['telefone']) ?>"
                     data-validate="telefone" placeholder="(67) 90000-0000">
              <span class="error-msg"></span>
            </div>
            <div class="field">
              <label for="endereco">Endereço de entrega</label>
              <input type="text" id="endereco" name="endereco" value="<?= limpar($valores['endereco']) ?>"
                     placeholder="Rua, número, bairro">
            </div>
          </div>

          <div class="form-grid two-col">
            <div class="field">
              <label for="senha">Senha <span class="req">*</span></label>
              <input type="password" id="senha" name="senha"
                     data-validate="required|senha" placeholder="Mínimo 6 caracteres">
              <span class="error-msg"></span>
              <span class="hint">Use letras e números.</span>
            </div>
            <div class="field">
              <label for="confirma_senha">Confirmar senha <span class="req">*</span></label>
              <input type="password" id="confirma_senha" name="confirma_senha"
                     data-validate="required|confirma:#senha" placeholder="Repita a senha">
              <span class="error-msg"></span>
            </div>
          </div>

          <div class="field">
            <div class="checkbox-row">
              <input type="checkbox" id="aceite" name="aceite" data-validate="checked">
              <label for="aceite">Li e aceito os termos de uso e a política de privacidade do Forno do Bairro.</label>
            </div>
            <span class="error-msg"></span>
          </div>
        </div>

        <div class="form-foot" style="justify-content: flex-end;">
          <button type="submit" class="btn btn-primary btn-block">Criar minha conta</button>
        </div>

        <p class="text-center muted" style="margin-top: 22px; font-size: 0.88rem;">
          Já tem conta? <a href="/login.php" style="color: var(--ember); font-weight:600;">Entrar</a>
        </p>
      </form>
    </div>
  </div>
</section>

<script src="/assets/js/validacao.js"></script>
<script>Validador.iniciar('form-cadastro');</script>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
