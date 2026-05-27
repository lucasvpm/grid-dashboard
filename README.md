# Grid Dashboard

Painel editorial Laravel que consome o plugin **Grid Connector** (WordPress), exibe métricas de publicação e gera sugestões de pauta com gancho jornalístico via IA (Google Gemini).

---

Overview do projeto: https://drive.google.com/file/d/1JAhlFTJADpuZq6oZfAtrCfcqhoELRYhE/view

## Índice

1. [Como subir o painel Laravel](#1-como-subir-o-painel-laravel)
2. [Como instalar o plugin no WordPress](#2-como-instalar-o-plugin-no-wordpress)
3. [Como rodar o seeder de posts fake](#3-como-rodar-o-seeder-de-posts-fake)
4. [Como configurar a chave do Gemini](#4-como-configurar-a-chave-do-gemini)
5. [Como rodar os testes](#5-como-rodar-os-testes)
6. [Estrutura do projeto](#6-estrutura-do-projeto)
7. [Banco de dados](#7-banco-de-dados)
8. [Variáveis de ambiente](#8-variáveis-de-ambiente)
9. [Solução de problemas](#9-solução-de-problemas)

---

## 1. Como subir o painel Laravel

### Requisitos

- PHP 8.2+
- Composer

### Passo a passo

```bash
# 1. Instalar dependências PHP
composer install

# 2. Copiar e configurar o ambiente
cp .env.example .env
php artisan key:generate

# 3. Criar o banco SQLite e rodar as migrations
touch database/database.sqlite
php artisan migrate

# 4. Subir o servidor
php artisan serve
```

Acesse: **http://localhost:8000**

A rota `/` redireciona automaticamente para `/dashboard`.

### Usando MySQL ou PostgreSQL (opcional)

Edite o `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grid_dashboard
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

Depois rode `php artisan migrate` normalmente.

---

## 2. Como instalar o plugin no WordPress

1. Copie a pasta `grid-connector/` para `wp-content/plugins/` do seu WordPress
2. No painel WP → **Plugins**, ative o **Grid Connector**
3. No menu lateral, clique em **Grid Connector**
4. Gere um token aleatório com um dos comandos abaixo e cole no campo **Bearer Token**:

```bash
# Terminal / Git Bash
openssl rand -hex 32

# PowerShell (Windows)
-join ((48..57 + 65..90 + 97..122) | Get-Random -Count 64 | ForEach-Object {[char]$_})
```

5. Clique em **Salvar configurações**

### Cadastrar o site no painel Laravel

1. Acesse http://localhost:8000/sites
2. Clique em **+ Novo site**
3. Preencha:
   - **Nome**: nome amigável (ex.: "Portal Principal")
   - **URL**: URL do WordPress (ex.: `http://meusite.local`)
   - **Token**: o mesmo token salvo no plugin
4. Clique em **Cadastrar**

O endpoint que o painel consome é:
```
GET {url}/wp-json/grid/v1/posts
Authorization: Bearer {token}
```

---

## 3. Como rodar o seeder de posts fake

No painel do WordPress, vá em **Grid Connector** e clique em:

> **Criar 30 posts de teste**

Isso gera automaticamente:
- 30 posts publicados
- 3 a 5 categorias (Tecnologia, Negócios, Cultura, Ciência, Saúde)
- 3 a 5 autores fictícios (logins prefixados com `gc_`)
- Datas distribuídas aleatoriamente nos últimos 60 dias

Você pode clicar várias vezes para acumular mais dados. Autores e categorias já existentes não são duplicados.

---

## 4. Como configurar a chave do Gemini

O projeto usa o **Google Gemini** como LLM para geração de sugestões de pauta. A chave é **gratuita e não exige cartão de crédito**.

### Obter a chave

1. Acesse https://aistudio.google.com
2. Clique em **Get API key** → **Create API key**
3. Copie a chave gerada (começa com `AIza...`)

### Configurar no `.env`

```dotenv
GEMINI_API_KEY=AIzaSy...
GEMINI_MODEL=gemini-2.0-flash
GEMINI_VERIFY_SSL=false   # necessário em ambientes Windows/Local by Flywheel
```

> **Por que Gemini?** É o único dos grandes providers (OpenAI, Anthropic, Google) que oferece chave gratuita sem precisar de cartão de crédito. O modelo `gemini-2.0-flash` responde em 1–3 segundos e suporta `responseMimeType: application/json`, garantindo saída sempre em JSON válido.

> **GEMINI_VERIFY_SSL=false** é necessário em desenvolvimento local no Windows porque o PHP não encontra o bundle de certificados CA do sistema. Em produção, mantenha como `true`.

### Modelos disponíveis no free tier

| Modelo | Velocidade | Qualidade |
|---|---|---|
| `gemini-2.0-flash` ✅ padrão | Muito rápida | Boa |
| `gemini-1.5-flash` | Rápida | Boa |
| `gemini-1.5-pro` | Moderada | Excelente |

---

## 5. Como rodar os testes

```bash
php artisan test
```

Para ver o nome de cada teste individualmente:

```bash
php artisan test --verbose
```

### Resultado esperado

```
PASS  Tests\Unit\ExampleTest
  ✓ that true is true

PASS  Tests\Feature\DashboardTest
  ✓ dashboard receives wordpress data and aggregates metrics
  ✓ dashboard shows friendly error when wordpress is unreachable

PASS  Tests\Feature\ExampleTest
  ✓ the application redirects to dashboard

PASS  Tests\Feature\SuggestionGenerationTest
  ✓ it blocks suggestion generation when site has insufficient data
  ✓ it blocks suggestion generation when rate limit is exceeded
  ✓ it generates and persists suggestions when data is sufficient

Tests: 7 passed
```

### O que cada teste cobre

| Teste | Cenário |
|---|---|
| `DashboardTest` — métricas | WordPressClient mockado retorna posts; verifica que a view exibe total, autores e categorias corretamente |
| `DashboardTest` — erro | WP lança exceção; verifica que o dashboard mostra mensagem amigável em vez de quebrar |
| `ExampleTest` | Rota `/` redireciona para `/dashboard` (302) |
| `SuggestionGenerationTest` — dados insuficientes | Menos de 5 posts disponíveis; LLM não é chamada; retorna erro na sessão |
| `SuggestionGenerationTest` — rate limit | 3 tentativas já registradas no minuto; bloqueia sem chamar WP nem LLM |
| `SuggestionGenerationTest` — sucesso | 6 posts disponíveis; LLM mockada retorna 2 sugestões; verifica persistência no banco |

Os testes **nunca** chamam o WordPress real nem a API do Gemini — tudo é mockado via `$this->app->instance()`.

---

## 6. Estrutura do projeto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php     # Exibe métricas e lista de posts
│   │   ├── SiteController.php          # CRUD de sites WordPress
│   │   └── SuggestionController.php    # Dispara geração de pautas (rate limit aqui)
│   └── Requests/
│       ├── StoreSiteRequest.php        # Validação ao criar site
│       └── UpdateSiteRequest.php       # Validação ao editar site
├── Jobs/
│   └── GenerateSuggestionsJob.php      # Monta resumo editorial e chama a LLM
├── Models/
│   ├── Site.php                        # Sites WordPress cadastrados
│   └── Suggestion.php                  # Sugestões geradas pela IA
└── Services/
    ├── WordPressClient.php             # HTTP para o plugin; cache de 10 min; busca todas as páginas
    ├── PostFilterService.php           # Normaliza campos, aplica filtros, agrega métricas
    └── LlmClient.php                   # Chamada ao Gemini com validação JSON e tratamento de erros

resources/views/
├── layout.blade.php                    # HTML base com Tailwind via CDN
├── dashboard/
│   └── index.blade.php                 # Tela principal: filtros, cards, tabela, sugestões
└── sites/
    ├── index.blade.php                 # Listagem de sites
    ├── create.blade.php                # Formulário de criação
    ├── edit.blade.php                  # Formulário de edição
    └── _form.blade.php                 # Partial compartilhado entre create e edit
```

---

## 7. Banco de dados

### Tabela `sites`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | bigint | PK auto-increment |
| `name` | string | Nome amigável do site |
| `url` | string | URL base do WordPress |
| `token` | string | Bearer token para autenticar no plugin |
| `created_at` / `updated_at` | timestamp | Gerenciados pelo Laravel |

### Tabela `suggestions`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | bigint | PK auto-increment |
| `site_id` | bigint FK | Site que originou a sugestão |
| `title` | string | Título proposto para a matéria |
| `hook` | text | Gancho jornalístico — justificativa de por que publicar agora |
| `seo_keywords` | json | Array com 3 palavras-chave para SEO |
| `created_at` / `updated_at` | timestamp | Gerenciados pelo Laravel |

> **O que é o `hook`?** É o ângulo editorial da matéria — o que torna ela relevante neste momento. Responde à pergunta *"por que publicar isso agora?"*. Exemplo: *"Com o avanço de ferramentas de IA nas redações, editores relatam mudança nas rotinas de apuração — tema em debate no setor esta semana."*

---

## 8. Variáveis de ambiente

```dotenv
# Aplicação
APP_KEY=          # gerado pelo artisan key:generate
APP_URL=http://localhost:8000
APP_ENV=local

# Banco (SQLite por padrão — não precisa de configuração adicional)
DB_CONNECTION=sqlite

# Fila — sync executa o Job na mesma request, sem precisar de worker separado
QUEUE_CONNECTION=sync

# Cache — database usa a tabela cache do SQLite
CACHE_STORE=database

# Google Gemini
GEMINI_API_KEY=           # chave do AI Studio (AIza...)
GEMINI_MODEL=gemini-2.0-flash
GEMINI_VERIFY_SSL=false   # false em dev Windows; true em produção
```

---

## 9. Solução de problemas

### `cURL error 60: SSL certificate verify failed`
Certifique-se de que `GEMINI_VERIFY_SSL=false` está no `.env`. Esse erro ocorre no Windows/Local by Flywheel porque o PHP não encontra o bundle de certificados CA do sistema operacional.

### `Gemini retornou 429` na primeira requisição
O 429 às vezes indica bloqueio por região, não rate limit real. Verifique:
1. Acesse https://aistudio.google.com pelo navegador e teste um prompt — se funcionar, o problema é na chave
2. Abra `storage/logs/laravel.log` e procure por `Gemini raw response` para ver a mensagem exata do Google
3. Tente trocar para `GEMINI_MODEL=gemini-1.5-flash` no `.env`

### `Token inválido` no WordPress
O token no campo **URL + Token** do painel Laravel deve ser idêntico ao salvo em **Grid Connector → Bearer Token** no WordPress. Qualquer espaço extra ou caractere diferente causa 401.

### Dashboard não mostra posts após cadastrar o site
Clique em **↺ Atualizar dados** para limpar o cache. Os dados ficam cacheados por 10 minutos para não sobrecarregar o WordPress.

### `Não há dados suficientes para gerar sugestões`
A geração de pautas exige pelo menos 5 posts no período/filtro selecionado. Rode o seeder de posts fake no WordPress ou ajuste o filtro de período para um intervalo maior.
