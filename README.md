# Simulador de balancete anual (SNC / PRIMAVERA)

Aplicação PHP para **simular um exercício contabilístico completo**: a empresa define áreas de serviço, produtos/serviços e margens; o motor gera lançamentos a partida dobrada e produz balancete, razão, demonstração de resultados e balanço — no espírito do ERP PRIMAVERA.

## O que inclui

- Empresa (NIF, exercício, capital, IVA, IRC)
- Áreas de serviço (centros de custo: estrutura + pessoal)
- Produtos/serviços com intervalo de preço, custo, volume e sazonalidade
- Plano de contas SNC resumido (classes 1–8)
- Simulação de 12 meses (vendas, recebimentos, custos, salários, IVA, depreciação, IRC)
- Diário, balancete de verificação, razão e relatório anual
- Empresa demonstração: **NorteAtlântico Serviços, Lda** (exercício 2025)

## Como correr no Laragon

1. Esta pasta já está em `C:\laragon\balancetes`.
2. No Laragon, adicione o site com document root em `public` **ou** abra `http://balancetes.test` se o virtual host apontar para a pasta (o `.htaccess` na raiz encaminha para `public/`).
3. PHP 8.2+ com extensão `pdo_sqlite` (incluída no PHP do Laragon).

Servidor embutido (PowerShell):

```powershell
.\scripts\serve.ps1
```

Depois abra [http://127.0.0.1:8080](http://127.0.0.1:8080).

## Fluxo de trabalho

1. **Empresa** — identifique a sociedade e o exercício.
2. **Áreas de serviço** — códigos, custos fixos e massa salarial mensal.
3. **Produtos e margens** — preço min/máx, custo unitário, volume mensal.
4. **Simulação** — gere o ano (o mesmo *seed* reproduz o mesmo resultado).
5. **Balancete / Relatório anual** — analise, exporte CSV e imprima.

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
