<!DOCTYPE html>
<html lang="pt-BR" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FrotaCerta Fleet Intelligence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
</head>
<body class="bg-slate-50 text-slate-800 font-sans selection:bg-indigo-500 selection:text-white">
<main class="min-h-screen flex flex-col" data-app>
    
    <!-- Tela de Login -->
    <section class="login-view flex-1 flex items-center justify-center p-6" data-login-view>
        <div class="w-full max-w-5xl grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div class="space-y-6">
                <div class="inline-flex items-center space-x-2 bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-sm font-semibold tracking-wide">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    <span>Sistema Operacional</span>
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-slate-900 leading-tight">
                    FrotaCerta <br/>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500">Fleet Intelligence</span>
                </h1>
                <p class="text-lg text-slate-500 max-w-lg">
                    O Mission Control definitivo para controle, ações, calendário e relatórios conectados ao backend operacional da sua frota.
                </p>
            </div>

            <div class="bg-white/70 backdrop-blur-xl border border-white/20 p-8 md:p-10 rounded-3xl shadow-2xl shadow-indigo-100/50 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 bg-indigo-50 rounded-full blur-2xl opacity-50 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-32 h-32 bg-cyan-50 rounded-full blur-2xl opacity-50 pointer-events-none"></div>
                
                <h2 class="text-2xl font-bold text-slate-900 mb-6 relative z-10">Acesso Restrito</h2>
                <form class="space-y-5 relative z-10" data-login-form>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email corporativo</label>
                        <input name="email" type="email" autocomplete="username" required 
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-shadow">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                        <input name="password" type="password" autocomplete="current-password" required 
                               class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-shadow">
                    </div>
                    <button type="submit" 
                            class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold py-3.5 rounded-xl shadow-lg shadow-slate-900/20 hover:shadow-indigo-500/30 transition-all active:scale-[0.98]">
                        Entrar no Mission Control
                    </button>
                    <p class="text-sm text-red-500 font-medium h-5" data-login-message></p>
                </form>
            </div>
        </div>
    </section>

    <!-- Dashboard -->
    <section class="dashboard-view is-hidden flex-1 w-full max-w-[1440px] mx-auto p-4 md:p-8" data-dashboard-view>
        <header class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <p class="text-sm font-bold text-indigo-600 tracking-wider uppercase mb-1">Mission Control</p>
                <h1 class="text-3xl font-extrabold text-slate-900">FrotaCerta</h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow-sm border border-slate-100">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-cyan-400 flex items-center justify-center text-white font-bold text-sm shadow-inner">
                        <span data-user-initials>US</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-700" data-user-name></span>
                </div>
                <button type="button" class="bg-white border border-slate-200 hover:border-slate-300 text-slate-700 p-2 rounded-full shadow-sm transition-all hover:bg-slate-50" data-refresh title="Atualizar">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
                <button type="button" class="bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 px-4 py-2 rounded-full text-sm font-semibold transition-colors" data-logout>Sair</button>
            </div>
        </header>

        <nav class="flex overflow-x-auto space-x-1 border-b border-slate-200 mb-8 pb-px" aria-label="Navegacao principal">
            <button type="button" class="tab-btn is-active relative px-5 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors whitespace-nowrap" data-tab="overview">
                Visão Geral
            </button>
            <button type="button" class="tab-btn relative px-5 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors whitespace-nowrap" data-tab="actions">
                Central de Ações
            </button>
            <button type="button" class="tab-btn relative px-5 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors whitespace-nowrap" data-tab="calendar">
                Calendário Preventivo
            </button>
            <button type="button" class="tab-btn relative px-5 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors whitespace-nowrap" data-tab="reports">
                Relatórios e Auditoria
            </button>
            <button type="button" class="tab-btn relative px-5 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors whitespace-nowrap" data-tab="erp">
                Cadastros ERP
            </button>
        </nav>

        <div class="relative">
            <!-- Overview Panel -->
            <section class="tab-panel animate-fade-in" data-panel="overview">
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-8" data-mission-grid></div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <section class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-800">Alertas Abertos</h2>
                            <span class="bg-red-100 text-red-700 py-1 px-3 rounded-full text-xs font-bold" data-alert-count>0</span>
                        </div>
                        <div class="space-y-4" data-alert-list></div>
                    </section>
                    <section class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-800">Insights Gerados</h2>
                            <span class="bg-cyan-100 text-cyan-700 py-1 px-3 rounded-full text-xs font-bold" data-insight-count>0</span>
                        </div>
                        <div class="space-y-4" data-insight-list></div>
                    </section>
                </div>
            </section>

            <!-- Actions Panel -->
            <section class="tab-panel is-hidden animate-fade-in" data-panel="actions">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Gestão de Tarefas e Aprovações</h2>
                    <span class="text-slate-500 font-medium" data-action-count></span>
                </div>
                
                <section class="mb-10">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        Aprovações Pendentes
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-approval-list></div>
                </section>
                
                <section>
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Tarefas Operacionais
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-task-list></div>
                </section>
            </section>

            <!-- Calendar Panel -->
            <section class="tab-panel is-hidden animate-fade-in" data-panel="calendar">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Planos e Vencimentos</h2>
                    <p class="text-slate-500">Visão preventiva de tacógrafos e manutenções programadas.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4" data-calendar-list></div>
            </section>

            <!-- Reports Panel -->
            <section class="tab-panel is-hidden animate-fade-in" data-panel="reports">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <section>
                        <h2 class="text-2xl font-bold text-slate-800 mb-6">Resumo Executivo</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" data-report-grid></div>
                    </section>
                    <section class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-slate-800">Log de Auditoria</h2>
                            <button type="button" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1" data-audit-download>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Exportar CSV
                            </button>
                        </div>
                        <div class="flex-1 space-y-3 overflow-y-auto pr-2 max-h-[500px]" data-audit-list></div>
                    </section>
                </div>
            </section>

            <!-- ERP Panel -->
            <section class="tab-panel is-hidden animate-fade-in" data-panel="erp">
                <div class="grid grid-cols-1 xl:grid-cols-[280px_1fr] gap-6">
                    <aside class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 h-max">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Operações</p>
                        <div class="space-y-2" data-erp-menu></div>
                    </aside>

                    <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold text-indigo-600 tracking-wider uppercase mb-1" data-erp-kicker>ERP SaaS</p>
                                <h2 class="text-2xl font-bold text-slate-900" data-erp-title>Cadastros</h2>
                                <p class="text-slate-500 mt-1" data-erp-description>Gerencie os registros operacionais da frota.</p>
                            </div>
                            <button type="button" class="bg-slate-900 hover:bg-indigo-600 text-white font-semibold px-4 py-2.5 rounded-xl shadow-sm transition-colors" data-erp-new>
                                Novo registro
                            </button>
                        </div>

                        <div class="p-4 md:p-6">
                            <div class="flex flex-col md:flex-row gap-3 md:items-center justify-between mb-4">
                                <input type="search" data-erp-search placeholder="Buscar na tabela" class="w-full md:max-w-sm bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                                <p class="text-sm text-slate-500" data-erp-count></p>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-slate-100">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-xs" data-erp-table-head></thead>
                                    <tbody class="divide-y divide-slate-100" data-erp-table-body></tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>
</main>

<div class="fixed inset-0 z-50 is-hidden" data-erp-modal>
    <div class="absolute inset-0 bg-slate-900/40" data-erp-close></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white shadow-2xl flex flex-col">
        <header class="p-6 border-b border-slate-100 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider" data-erp-form-kicker>Cadastro</p>
                <h2 class="text-2xl font-bold text-slate-900" data-erp-form-title>Novo registro</h2>
            </div>
            <button type="button" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center" data-erp-close aria-label="Fechar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"></path></svg>
            </button>
        </header>
        <form class="flex-1 overflow-y-auto p-6 space-y-4" data-erp-form></form>
        <footer class="p-6 border-t border-slate-100 flex items-center justify-end gap-3">
            <p class="mr-auto text-sm text-red-500 font-medium" data-erp-message></p>
            <button type="button" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold" data-erp-close>Cancelar</button>
            <button type="submit" form="erp-generated-form" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-indigo-600 text-white font-semibold">Salvar</button>
        </footer>
    </div>
</div>

<template data-empty-template>
    <div class="flex flex-col items-center justify-center p-8 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
        <p class="text-slate-500 font-medium">Nenhum registro encontrado no momento.</p>
    </div>
</template>
</body>
</html>
