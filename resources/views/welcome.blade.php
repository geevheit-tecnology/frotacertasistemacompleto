<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FrotaCerta Fleet Intelligence</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="app-shell" data-app>
    <section class="login-view" data-login-view>
        <div class="login-panel">
            <div>
                <p class="eyebrow">FrotaCerta</p>
                <h1>Fleet Intelligence</h1>
                <p class="muted">Mission Control, acoes, calendario e relatorios conectados ao backend operacional.</p>
            </div>

            <form class="login-form" data-login-form>
                <label>
                    <span>Email</span>
                    <input name="email" type="email" autocomplete="username" required>
                </label>
                <label>
                    <span>Senha</span>
                    <input name="password" type="password" autocomplete="current-password" required>
                </label>
                <button type="submit">Entrar</button>
                <p class="form-message" data-login-message></p>
            </form>
        </div>
    </section>

    <section class="dashboard-view is-hidden" data-dashboard-view>
        <header class="topbar">
            <div>
                <p class="eyebrow">Mission Control</p>
                <h1>FrotaCerta</h1>
            </div>
            <div class="topbar-actions">
                <span data-user-name></span>
                <button type="button" data-refresh>Atualizar</button>
                <button type="button" class="ghost" data-logout>Sair</button>
            </div>
        </header>

        <nav class="tabs" aria-label="Navegacao principal">
            <button type="button" class="is-active" data-tab="overview">Controle</button>
            <button type="button" data-tab="actions">Acoes</button>
            <button type="button" data-tab="calendar">Calendario</button>
            <button type="button" data-tab="reports">Relatorios</button>
        </nav>

        <section class="tab-panel" data-panel="overview">
            <div class="status-grid" data-mission-grid></div>
            <div class="split-grid">
                <section>
                    <h2>Alertas abertos</h2>
                    <div class="list" data-alert-list></div>
                </section>
                <section>
                    <h2>Insights</h2>
                    <div class="list" data-insight-list></div>
                </section>
            </div>
        </section>

        <section class="tab-panel is-hidden" data-panel="actions">
            <div class="section-head">
                <h2>Central de acoes</h2>
                <span data-action-count></span>
            </div>
            <div class="list" data-task-list></div>
        </section>

        <section class="tab-panel is-hidden" data-panel="calendar">
            <div class="section-head">
                <h2>Calendario preventivo</h2>
                <span>Planos e vencimentos</span>
            </div>
            <div class="calendar-grid" data-calendar-list></div>
        </section>

        <section class="tab-panel is-hidden" data-panel="reports">
            <div class="split-grid">
                <section>
                    <h2>Resumo executivo</h2>
                    <div class="report-grid" data-report-grid></div>
                </section>
                <section>
                    <h2>Auditoria</h2>
                    <div class="list" data-audit-list></div>
                    <button type="button" class="download-link" data-audit-download>Baixar CSV</button>
                </section>
            </div>
        </section>
    </section>
</main>

<template data-empty-template>
    <div class="empty-state">Sem registros para os filtros atuais.</div>
</template>
</body>
</html>
