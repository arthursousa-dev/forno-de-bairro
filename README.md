# Forno do Bairro

Aplicação full-stack de delivery para pizzaria: catálogo público, carrinho, checkout com persistência transacional, área do cliente e painel administrativo completo (produtos, pedidos, clientes, contatos).

PHP puro (sem framework), seguindo uma organização em camadas inspirada em MVC — é o projeto do portfólio com a arquitetura mais próxima do que se espera em produção.

## Arquitetura

```
app/
├── Config/
│   ├── config.php       # constantes de ambiente (DB, sessão, paths)
│   └── Database.php     # conexão PDO em Singleton
├── Core/
│   └── helpers.php       # sanitização, auth, CSRF, flash, upload seguro
├── Views/
│   ├── partials/         # header/footer do site público
│   └── admin/            # header/footer do painel administrativo
└── bootstrap.php          # ponto único de inicialização

database/
└── schema.sql             # DDL completo (usuarios, categorias, produtos,
                            #   pedidos, itens_pedido, contatos)

public/                    # document root
├── index.php, produtos.php, sobre.php, contato.php
├── cadastro.php, login.php, logout.php, minha-conta.php
├── pedido.php, meus-pedidos.php
├── admin/                 # painel administrativo (login próprio)
└── assets/                # css/js estático
```

## Decisões técnicas

- **PDO com prepared statements** em toda operação que recebe input do usuário; as poucas chamadas `->query()` diretas são exclusivamente `SELECT`s estáticos, sem interpolação de variável.
- **Senha em bcrypt** (`password_hash`/`password_verify`), nunca texto puro.
- **Bloqueio de conta** após 5 tentativas de login incorretas seguidas (15 minutos), compartilhado entre o login do cliente e o do admin.
- **Cookies de sessão** com `httponly`, `samesite=Lax` e `secure` (quando em HTTPS).
- **CSRF** em todo formulário que altera estado (cadastro, login, contato, checkout, CRUD do admin), token validado com `hash_equals()`.
- **Upload de imagem validado por MIME real** (via `finfo`), não pela extensão do arquivo — evita que um `.php` disfarçado de `.jpg` seja aceito.
- **Configuração por variável de ambiente**, com fallback local — nenhuma credencial de produção fica hardcoded.
- **Checkout transacional**: `beginTransaction()` / `commit()` / `rollBack()` ao gravar pedido + itens, evitando pedido gravado pela metade em caso de falha.
- **`.htaccess` de segurança**: bloqueia listagem de diretório, nega acesso direto a `.env`/`.sql`/`.log`, define `X-Content-Type-Options`, `X-Frame-Options` e `Referrer-Policy`.

## Como rodar localmente

1. Crie o banco a partir do schema:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. Configure as variáveis de ambiente (ou ajuste os defaults em `app/Config/config.php` para desenvolvimento local):
   ```bash
   export DB_HOST=localhost
   export DB_NAME=forno_do_bairro
   export DB_USER=root
   export DB_PASS=
   ```
3. Suba o servidor embutido do PHP a partir de `public/`:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Acesse `http://localhost:8000`.

## Roadmap

- [ ] Migrar para um framework leve (Slim ou similar) conforme o projeto cresce
- [ ] Testes automatizados para o fluxo de checkout
- [ ] Paginação nas listagens do admin

---

Feito por Arthur Sousa — [LinkedIn](https://www.linkedin.com/in/arthur-sousa-ads/) · [GitHub](https://github.com/arthursousa-dev)
