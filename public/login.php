<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Entrar';
$paginaAtual  = 'login';

if (estaLogado()) {
    header('Location: /minha-conta.php');
    exit;
}

$erros = [];
$emailValor = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '/index.php';

// Aceita apenas caminhos internos relativos (ex: /pedido.php), nunca URLs
// externas ou protocol-relative (//evil.com), prevenindo open redirect.
if (!preg_match('#^/(?!/)#', $redirect)) {
    $redirect = '/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $emailValor = trim($_POST['email'] ?? '');
    $senha      = (string)($_POST['senha'] ?? '');

    if (!filter_var($emailValor, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($senha === '') {
        $erros[] = 'Informe sua senha.';
    }

    if (!$erros) {
        try {
            $usuario = autenticar(conectar(), $emailValor, $senha);

            if (!$usuario) {
                $erros[] = 'E-mail ou senha incorretos.';
            } elseif (!$usuario['ativo']) {
                $erros[] = 'Esta conta está desativada. Entre em contato com o suporte.';
            } else {
                session_regenerate_id(true);
                $_SESSION['usuario_id']   = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];

                definirFlash('success', 'Bem-vindo(a) de volta, ' . $usuario['nome'] . '!');

                if ($usuario['tipo'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: ' . ($redirect ?: '/index.php'));
                }
                exit;
            }
        } catch (Throwable $e) {
            $erros[] = 'Não foi possível autenticar agora. Tente novamente em instantes.';
        }
    }
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="section" style="padding-top: 56px;">
  <div class="container" style="max-width: 460px;">
    <div class="eyebrow">Acesse sua conta</div>
    <h1 style="font-size: 2.1rem; margin-bottom: 28px;">Entrar</h1>

    <?php if ($erros): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left: 18px;">
          <?php foreach ($erros as $erro): ?><li><?= limpar($erro) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <form id="form-login" method="post" action="/login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
        <input type="hidden" name="redirect" value="<?= limpar($redirect) ?>">

        <div class="form-grid">
          <div class="field">
            <label for="email">E-mail <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="<?= limpar($emailValor) ?>"
                   data-validate="required|email" placeholder="voce@email.com">
            <span class="error-msg"></span>
          </div>
          <div class="field">
            <label for="senha">Senha <span class="req">*</span></label>
            <input type="password" id="senha" name="senha" data-validate="required" placeholder="Sua senha">
            <span class="error-msg"></span>
          </div>
        </div>

        <div class="form-foot" style="justify-content: flex-end;">
          <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </div>

        <p class="text-center muted" style="margin-top: 22px; font-size: 0.88rem;">
          Ainda não tem conta? <a href="/cadastro.php" style="color: var(--ember); font-weight:600;">Criar conta</a>
        </p>
      </form>
    </div>

    <div class="alert alert-info" style="margin-top:22px;">
      Conta de teste: <strong class="mono">cliente@teste.com</strong> / senha <strong class="mono">cliente123</strong>
    </div>
  </div>
</section>

<script src="/assets/js/validacao.js"></script>
<script>Validador.iniciar('form-login');</script>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
