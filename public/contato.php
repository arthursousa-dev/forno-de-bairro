<?php
require_once __DIR__ . '/../app/bootstrap.php';
$tituloPagina = 'Contato';
$paginaAtual  = 'contato';

$erros  = [];
$valores = ['nome' => '', 'email' => '', 'telefone' => '', 'assunto' => '', 'mensagem' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCSRF($_POST['csrf_token'] ?? null)) {
        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    $valores['nome']     = trim($_POST['nome'] ?? '');
    $valores['email']    = trim($_POST['email'] ?? '');
    $valores['telefone'] = trim($_POST['telefone'] ?? '');
    $valores['assunto']  = trim($_POST['assunto'] ?? '');
    $valores['mensagem'] = trim($_POST['mensagem'] ?? '');

    // ---- Validação no servidor (nunca confiar apenas no JavaScript) ----
    if ($valores['nome'] === '' || mb_strlen($valores['nome']) < 3) {
        $erros[] = 'Informe seu nome completo (mínimo 3 caracteres).';
    }
    if (!filter_var($valores['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if ($valores['telefone'] !== '' && !preg_match('/^\(\d{2}\)\s?\d{4,5}-\d{4}$/', $valores['telefone'])) {
        $erros[] = 'Telefone em formato inválido. Use (00) 00000-0000.';
    }
    if ($valores['assunto'] === '') {
        $erros[] = 'Selecione um assunto.';
    }
    if (mb_strlen($valores['mensagem']) < 10) {
        $erros[] = 'Sua mensagem precisa ter ao menos 10 caracteres.';
    }

    if (!$erros) {
        try {
            $pdo = conectar();
            $stmt = $pdo->prepare('INSERT INTO contatos (nome, email, telefone, assunto, mensagem)
                                    VALUES (:nome, :email, :telefone, :assunto, :mensagem)');
            $stmt->execute([
                ':nome'     => $valores['nome'],
                ':email'    => $valores['email'],
                ':telefone' => $valores['telefone'] ?: null,
                ':assunto'  => $valores['assunto'],
                ':mensagem' => $valores['mensagem'],
            ]);

            definirFlash('success', 'Mensagem enviada com sucesso! Responderemos em breve no seu e-mail.');
            header('Location: /contato.php');
            exit;
        } catch (Throwable $e) {
            $erros[] = 'Não foi possível enviar sua mensagem agora. Tente novamente em instantes.';
        }
    }
}

include __DIR__ . '/../app/Views/partials/header.php';
?>

<section class="page-banner">
  <div class="container">
    <div class="eyebrow">Fale com a gente</div>
    <h1>Dúvidas, elogios ou sugestões?</h1>
    <p class="lede">Preencha o formulário abaixo e nossa equipe responde o quanto antes.</p>
  </div>
</section>
<div class="crust-divider on-charcoal"></div>

<section class="section">
  <div class="container" style="max-width: 720px;">

    <?php if ($erros): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left: 18px;">
          <?php foreach ($erros as $erro): ?><li><?= limpar($erro) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="form-card">
      <form id="form-contato" method="post" action="/contato.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= tokenCSRF() ?>">

        <div class="form-grid">
          <div class="form-grid two-col">
            <div class="field">
              <label for="nome">Nome completo <span class="req">*</span></label>
              <input type="text" id="nome" name="nome" value="<?= limpar($valores['nome']) ?>"
                     data-validate="required|min:3" placeholder="Seu nome">
              <span class="error-msg"></span>
            </div>
            <div class="field">
              <label for="email">E-mail <span class="req">*</span></label>
              <input type="email" id="email" name="email" value="<?= limpar($valores['email']) ?>"
                     data-validate="required|email" placeholder="voce@email.com">
              <span class="error-msg"></span>
            </div>
          </div>

          <div class="form-grid two-col">
            <div class="field">
              <label for="telefone">Telefone</label>
              <input type="text" id="telefone" name="telefone" value="<?= limpar($valores['telefone']) ?>"
                     data-validate="telefone" placeholder="(67) 90000-0000">
              <span class="error-msg"></span>
            </div>
            <div class="field">
              <label for="assunto">Assunto <span class="req">*</span></label>
              <select id="assunto" name="assunto" data-validate="required">
                <option value="" <?= $valores['assunto'] === '' ? 'selected' : '' ?> disabled>Selecione...</option>
                <?php foreach (['Dúvida sobre pedido', 'Sugestão', 'Elogio', 'Reclamação', 'Trabalhe conosco', 'Outro'] as $opc): ?>
                  <option value="<?= $opc ?>" <?= $valores['assunto'] === $opc ? 'selected' : '' ?>><?= $opc ?></option>
                <?php endforeach; ?>
              </select>
              <span class="error-msg"></span>
            </div>
          </div>

          <div class="field">
            <label for="mensagem">Mensagem <span class="req">*</span></label>
            <textarea id="mensagem" name="mensagem" data-validate="required|min:10"
                      placeholder="Escreva sua mensagem..."><?= limpar($valores['mensagem']) ?></textarea>
            <span class="error-msg"></span>
          </div>
        </div>

        <div class="form-foot">
          <span class="muted" style="font-size:0.8rem;">Campos com <span class="req">*</span> são obrigatórios.</span>
          <button type="submit" class="btn btn-primary">Enviar mensagem</button>
        </div>
      </form>
    </div>
  </div>
</section>

<script src="/assets/js/validacao.js"></script>
<script>Validador.iniciar('form-contato');</script>

<?php include __DIR__ . '/../app/Views/partials/footer.php'; ?>
