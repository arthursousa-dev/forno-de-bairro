-- ============================================================
-- Forno do Bairro — Schema PostgreSQL
-- ============================================================

CREATE TYPE tipo_usuario AS ENUM ('cliente', 'admin');
CREATE TYPE status_pedido AS ENUM ('pendente', 'preparo', 'saiu_entrega', 'entregue', 'cancelado');
CREATE TYPE forma_pagamento_pedido AS ENUM ('dinheiro', 'cartao', 'pix');

-- ------------------------------------------------------------
-- usuarios — clientes e administradores
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id                SERIAL PRIMARY KEY,
    nome              VARCHAR(120) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    senha_hash        VARCHAR(255) NOT NULL,
    telefone          VARCHAR(20) NULL,
    endereco          VARCHAR(255) NULL,
    tipo              tipo_usuario NOT NULL DEFAULT 'cliente',
    ativo             BOOLEAN NOT NULL DEFAULT true,
    tentativas_login  SMALLINT NOT NULL DEFAULT 0,
    bloqueado_ate     TIMESTAMP NULL,
    criado_em         TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- categorias
-- ------------------------------------------------------------
CREATE TABLE categorias (
    id    SERIAL PRIMARY KEY,
    nome  VARCHAR(60) NOT NULL,
    slug  VARCHAR(60) NOT NULL UNIQUE
);

-- ------------------------------------------------------------
-- produtos
-- ------------------------------------------------------------
CREATE TABLE produtos (
    id            SERIAL PRIMARY KEY,
    categoria_id  INT NOT NULL REFERENCES categorias(id),
    nome          VARCHAR(120) NOT NULL,
    descricao     VARCHAR(500) NULL,
    preco         NUMERIC(8,2) NOT NULL,
    imagem        VARCHAR(255) NULL,
    disponivel    BOOLEAN NOT NULL DEFAULT true,
    criado_em     TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- pedidos
-- ------------------------------------------------------------
CREATE TABLE pedidos (
    id               SERIAL PRIMARY KEY,
    usuario_id       INT NOT NULL REFERENCES usuarios(id),
    status           status_pedido NOT NULL DEFAULT 'pendente',
    endereco_entrega VARCHAR(255) NOT NULL,
    forma_pagamento  forma_pagamento_pedido NOT NULL,
    observacoes      VARCHAR(500) NULL,
    total            NUMERIC(8,2) NOT NULL DEFAULT 0,
    criado_em        TIMESTAMP NOT NULL DEFAULT now(),
    atualizado_em    TIMESTAMP NOT NULL DEFAULT now()
);

-- Repõe o ON UPDATE CURRENT_TIMESTAMP que o MySQL tinha nativo —
-- Postgres não tem cláusula equivalente, então usa trigger.
CREATE OR REPLACE FUNCTION atualizar_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.atualizado_em = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_pedidos_atualizado_em
    BEFORE UPDATE ON pedidos
    FOR EACH ROW
    EXECUTE FUNCTION atualizar_timestamp();

-- ------------------------------------------------------------
-- pedido_itens
-- ------------------------------------------------------------
CREATE TABLE pedido_itens (
    id              SERIAL PRIMARY KEY,
    pedido_id       INT NOT NULL REFERENCES pedidos(id) ON DELETE CASCADE,
    produto_id      INT NOT NULL REFERENCES produtos(id),
    quantidade      INT NOT NULL DEFAULT 1,
    preco_unitario  NUMERIC(8,2) NOT NULL,
    subtotal        NUMERIC(8,2) NOT NULL
);

-- ------------------------------------------------------------
-- contatos — mensagens do formulário de contato
-- ------------------------------------------------------------
CREATE TABLE contatos (
    id          SERIAL PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    telefone    VARCHAR(20) NULL,
    assunto     VARCHAR(150) NOT NULL,
    mensagem    VARCHAR(1000) NOT NULL,
    respondido  BOOLEAN NOT NULL DEFAULT false,
    criado_em   TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_produtos_categoria ON produtos(categoria_id);
CREATE INDEX idx_pedidos_usuario ON pedidos(usuario_id);
CREATE INDEX idx_pedido_itens_pedido ON pedido_itens(pedido_id);
