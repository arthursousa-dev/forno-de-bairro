<?php
/**
 * public/admin/index.php
 * Tela de login exclusiva do painel administrativo.
 */
require_once __DIR__ . '/../../app/bootstrap.php';

if (ehAdmin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$erros = [];
$emailValor = '';

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
            $admin = autenticar(conectar(), $emailValor, $senha, 'admin');

            if (!$admin) {
                $erros[] = 'Credenciais inválidas para o painel administrativo.';
            } elseif (!$admin['ativo']) {
                $erros[] = 'Esta conta de administrador está desativada.';
            } else {
                session_regenerate_id(true);
                $_SESSION['usuario_id']   = $admin['id'];
                $_SESSION['usuario_nome'] = $admin['nome'];
                $_SESSION['usuario_tipo'] = 'admin';

                header('Location: /admin/dashboard.php');
                exit;
            }
        } catch (Throwable $e) {
            $erros[] = 'Não foi possível autenticar agora. Tente novamente em instantes.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acesso administrativo · Forno do Bairro</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background: var(--charcoal);">

<section class="section" style="min-height:100vh; display:flex; align-items:center;">
  <div class="container" style="max-width: 420px;">
    <div class="text-center" style="margin-bottom: 26px;">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" width="46" height="46" style="margin: 0 auto 14px;">
        <circle cx="24" cy="24" r="22" stroke="#E8A33D" stroke-width="2.5"/>
        <path d="M24 10c-1 4-5 5.5-5 10a5 5 0 0010 0c0-2-1-3-1.5-4.2.8.4 2.5 1.8 2.5 4.7a6 6 0 01-12 0c0-5.8 4.8-7.3 6-10.5z" fill="#D8472B"/>
      </svg>
      <h1 style="color: var(--flour); font-size: 1.7rem;">Painel Administrativo</h1>
      <p style="color: rgba(251,246,236,0.6); font-size: 0.9rem;">Forno do Bairro</p>
    </div>

    <?php if ($erros): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left: 18px;">
          <?php foreach ($erros as $erro): ?><li><?= limpar($erro) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <form id="form-admin-login" method="post" action="/admin/index.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">
        <div class="form-grid">
          <div class="field">
            <label for="email">E-mail <span class="req">*</span></label>
            <input type="email" id="email" name="email" value="<?= limpar($emailValor) ?>"
                   data-validate="required|email" placeholder="admin@fornodobairro.com.br">
            <span class="error-msg"></span>
          </div>
          <div class="field">
            <label for="senha">Senha <span class="req">*</span></label>
            <input type="password" id="senha" name="senha" data-validate="required" placeholder="Sua senha">
            <span class="error-msg"></span>
          </div>
        </div>
        <div class="form-foot" style="justify-content: flex-end;">
          <button type="submit" class="btn btn-primary btn-block">Entrar no painel</button>
        </div>
      </form>
    </div>

    <div class="alert alert-info" style="margin-top: 22px;">
      Acesso de teste: <strong class="mono">admin@fornodobairro.com.br</strong> / senha <strong class="mono">admin123</strong>
    </div>

    <p class="text-center" style="margin-top: 20px;">
      <a href="/index.php" style="color: rgba(251,246,236,0.6); font-size: 0.85rem;">← Voltar ao site</a>
    </p>
  </div>
</section>

<script src="/assets/js/validacao.js"></script>
<script>Validador.iniciar('form-admin-login');</script>
</body>
</html>
