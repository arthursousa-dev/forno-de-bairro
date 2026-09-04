<?php
/**
 * app/Views/admin/header.php
 *
 * Layout-base do painel administrativo (sidebar + topbar).
 * Espera que a página que o inclui já tenha chamado exigirAdmin()
 * e definido:
 *   $tituloPagina (string)
 *   $paginaAdminAtual (string) slug do item ativo no menu lateral
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= limpar($tituloPagina ?? 'Painel administrativo') ?> · Admin Forno do Bairro</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <aside class="admin-sidebar" id="adminSidebar">
    <a href="/admin/dashboard.php" class="brand">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" width="30" height="30">
        <circle cx="24" cy="24" r="22" stroke="#E8A33D" stroke-width="2.5"/>
        <path d="M24 10c-1 4-5 5.5-5 10a5 5 0 0010 0c0-2-1-3-1.5-4.2.8.4 2.5 1.8 2.5 4.7a6 6 0 01-12 0c0-5.8 4.8-7.3 6-10.5z" fill="#D8472B"/>
      </svg>
      <span class="brand-name" style="font-size: 1.05rem;">Admin</span>
    </a>

    <nav class="admin-nav">
      <a href="/admin/dashboard.php" class="<?= ($paginaAdminAtual ?? '') === 'dashboard' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
        Dashboard
      </a>
      <a href="/admin/pedidos.php" class="<?= ($paginaAdminAtual ?? '') === 'pedidos' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h2l2.6 13.4a2 2 0 002 1.6h8.8a2 2 0 002-1.6L21 7H6"/></svg>
        Pedidos
      </a>
      <a href="/admin/produtos.php" class="<?= ($paginaAdminAtual ?? '') === 'produtos' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg>
        Produtos
      </a>
      <a href="/admin/clientes.php" class="<?= ($paginaAdminAtual ?? '') === 'clientes' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
        Clientes
      </a>
      <a href="/admin/contatos.php" class="<?= ($paginaAdminAtual ?? '') === 'contatos' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v14H8l-4 4V4z"/></svg>
        Mensagens
      </a>
    </nav>

    <div class="admin-user-box">
      <strong><?= limpar($_SESSION['usuario_nome'] ?? 'Administrador') ?></strong>
      <span>Administrador</span><br>
      <a href="/admin/logout.php">Sair do painel</a>
    </div>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="mobile-nav-toggle" id="adminNavToggle" aria-label="Abrir menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>
        <h1><?= limpar($tituloPagina ?? 'Painel administrativo') ?></h1>
      </div>
      <a href="/index.php" target="_blank" class="btn btn-secondary btn-sm">Ver site público ↗</a>
    </div>

    <?php $flashAdmin = obterFlash(); if ($flashAdmin): ?>
      <div class="alert alert-<?= limpar($flashAdmin['tipo']) ?>" data-auto-dismiss>
        <?= limpar($flashAdmin['mensagem']) ?>
      </div>
    <?php endif; ?>
