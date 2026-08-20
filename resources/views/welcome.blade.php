<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FrotaCerta Fleet Intelligence</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; color: #172026; background: #f4f7f6; }
        main { max-width: 980px; margin: 0 auto; padding: 56px 20px; }
        h1 { margin: 0 0 12px; font-size: 34px; line-height: 1.1; }
        p { color: #53636c; font-size: 16px; line-height: 1.55; }
        .panel { background: #fff; border: 1px solid #dce5e2; border-radius: 8px; padding: 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-top: 24px; }
        .item { border: 1px solid #dce5e2; border-radius: 8px; padding: 16px; background: #fbfdfc; }
        .label { color: #60717a; font-size: 12px; text-transform: uppercase; }
        .value { margin-top: 8px; font-weight: 700; }
        a { color: #096c5b; font-weight: 700; text-decoration: none; }
        code { background: #eaf1ef; border-radius: 4px; padding: 2px 5px; }
    </style>
</head>
<body>
<main>
    <section class="panel">
        <h1>FrotaCerta Fleet Intelligence</h1>
        <p>Aplicacao Laravel modular para gestao inteligente de frotas, com base de Fase 1, auditoria, API e nucleo deterministico de preventiva.</p>

        <div class="grid">
            <div class="item">
                <div class="label">Aplicacao</div>
                <div class="value">Online</div>
            </div>
            <div class="item">
                <div class="label">Health</div>
                <div class="value"><a href="/api/health">/api/health</a></div>
            </div>
            <div class="item">
                <div class="label">Ready</div>
                <div class="value"><a href="/api/ready">/api/ready</a></div>
            </div>
            <div class="item">
                <div class="label">API</div>
                <div class="value"><code>/api/v1</code></div>
            </div>
        </div>
    </section>
</main>
</body>
</html>
