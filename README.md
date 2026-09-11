# Simulador de balancete anual (SNC / PRIMAVERA)

Aplicação PHP para **simular um exercício contabilístico completo**: a empresa define áreas de serviço, produtos/serviços e margens; o motor gera lançamentos a partida dobrada e produz balancete, razão, demonstração de resultados e balanço — no espírito do ERP PRIMAVERA.

## O que inclui

- Empresa (NIF, exercício, capital, IVA, IRC)
- Áreas de serviço (centros de custo: estrutura + pessoal)
- Produtos/serviços com intervalo de preço, custo, volume e sazonalidade
- Plano de contas SNC resumido (classes 1–8)
- Simulação de 12 meses (vendas, recebimentos, custos, salários, IVA, depreciação, IRC)
- Diário, balancete de verificação, razão e relatório anual
- Exportação PDF via **wkhtmltopdf** (balancete, relatório anual, razão e diário)
- Empresa demonstração: **NorteAtlântico Serviços, Lda** (exercício 2025)

## Como correr no Laragon

O projecto está em `C:\laragon\balancetes`. O Apache serve a pasta `public/` em **http://balancetes.test** (virtual host, sem atalho em `www`).

1. No ícone do Laragon, **Reload** (ou Stop e Start), para actualizar o `hosts` e o Apache.
2. Abra [http://balancetes.test](http://balancetes.test).

PHP 8.2+ com `pdo_sqlite`. O virtual host está em `C:\laragon\etc\apache2\sites-enabled\auto.balancetes.test.conf`.

## Fluxo de trabalho

1. **Empresa** — identifique a sociedade e o exercício.
2. **Áreas de serviço** — códigos, custos fixos e massa salarial mensal.
3. **Produtos e margens** — preço min/máx, custo unitário, volume mensal.
4. **Simulação** — gere o ano (o mesmo *seed* reproduz o mesmo resultado).
5. **Balancete / Relatório anual** — analise, exporte CSV ou descarregue PDF (wkhtmltopdf).

Atalho: no painel, *Carregar empresa demonstração + simular 2025*.

## Estrutura

```
public/          front controller e CSS
src/Controllers  HTTP
src/Models       persistência SQLite
src/Services     simulação, balancete, relatório
views/           ecrãs
storage/         database.sqlite (gerada automaticamente)
```

Os lançamentos são sempre equilibrados (débito = crédito). A base `storage/database.sqlite` não vai para o Git.

## PDF (wkhtmltopdf)

A geração de PDF usa o binário local do [wkhtmltopdf](https://wkhtmltopdf.org/) (testado com 0.12.6, Qt patched). Caminhos detectados automaticamente:

- `WKHTMLTOPDF` (variável de ambiente)
- `C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe`
- `bin/wkhtmltopdf.exe` na raiz do projecto
- `wkhtmltopdf` no PATH

Botões **Descarregar PDF** no balancete (A4 horizontal), relatório anual, razão e diário.
