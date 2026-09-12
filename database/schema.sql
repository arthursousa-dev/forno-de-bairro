-- =====================================================================
-- Forno do Bairro - Pizzaria Web
-- Banco de Dados MySQL
-- ETAPA 3/4 - Modelagem e popula\u00e7\u00e3o inicial
-- =====================================================================

CREATE DATABASE IF NOT EXISTS forno_do_bairro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE forno_do_bairro;

-- ---------------------------------------------------------------------
-- Tabela: usuarios
-- Armazena clientes e administradores
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nome              VARCHAR(120)        NOT NULL,
    email             VARCHAR(150)        NOT NULL UNIQUE,
    senha_hash        VARCHAR(255)        NOT NULL,
    telefone          VARCHAR(20)         NULL,
    endereco          VARCHAR(255)        NULL,
    tipo              ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    ativo             TINYINT(1)          NOT NULL DEFAULT 1,
    tentativas_login  TINYINT UNSIGNED    NOT NULL DEFAULT 0,
    bloqueado_ate     DATETIME            NULL,
    criado_em         DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: categorias
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    nome    VARCHAR(60) NOT NULL,
    slug    VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: produtos
-- ---------------------------------------------------------------------
CREATE TABLE produtos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id  INT             NOT NULL,
    nome          VARCHAR(120)    NOT NULL,
    descricao     VARCHAR(500)    NULL,
    preco         DECIMAL(8,2)    NOT NULL,
    imagem        VARCHAR(255)    NULL,
    disponivel    TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: pedidos
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT             NOT NULL,
    status           ENUM('pendente','preparo','saiu_entrega','entregue','cancelado')
                     NOT NULL DEFAULT 'pendente',
    endereco_entrega VARCHAR(255)    NOT NULL,
    forma_pagamento  ENUM('dinheiro','cartao','pix') NOT NULL,
    observacoes      VARCHAR(500)    NULL,
    total            DECIMAL(8,2)    NOT NULL DEFAULT 0,
    criado_em        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: pedido_itens
-- ---------------------------------------------------------------------
CREATE TABLE pedido_itens (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id       INT             NOT NULL,
    produto_id      INT             NOT NULL,
    quantidade      INT             NOT NULL DEFAULT 1,
    preco_unitario  DECIMAL(8,2)    NOT NULL,
    subtotal        DECIMAL(8,2)    NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabela: contatos (mensagens do formul\u00e1rio de contato)
-- ---------------------------------------------------------------------
CREATE TABLE contatos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120)    NOT NULL,
    email       VARCHAR(150)    NOT NULL,
    telefone    VARCHAR(20)     NULL,
    assunto     VARCHAR(150)    NOT NULL,
    mensagem    VARCHAR(1000)   NOT NULL,
    respondido  TINYINT(1)      NOT NULL DEFAULT 0,
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- DADOS INICIAIS (SEED)
-- =====================================================================

-- Categorias
INSERT INTO categorias (nome, slug) VALUES
('Pizzas Salgadas', 'pizzas-salgadas'),
('Pizzas Doces', 'pizzas-doces'),
('Bebidas', 'bebidas'),
('Sobremesas', 'sobremesas');

-- Usu\u00e1rio administrador padr\u00e3o
-- Senha: admin123  (hash gerado com password_hash / BCRYPT)
INSERT INTO usuarios (nome, email, senha_hash, telefone, tipo) VALUES
('Administrador', 'admin@fornodobairro.com.br',
 '$2y$10$46EEeXGuH5F02Wr5701HSOYI7Ha2Wzr1sRBlMSBOUmTfN0DQ0x45O',
 '(67) 99999-0000', 'admin');

-- Cliente de teste -- senha: cliente123
INSERT INTO usuarios (nome, email, senha_hash, telefone, endereco, tipo) VALUES
('Cliente Teste', 'cliente@teste.com',
 '$2y$10$9WNq.56z4wJawpTUtihmP.ZpvJ1W4r2wpVmoZhHsSxH.8cFBj3pay',
 '(67) 98888-1111', 'Rua das Acácias, 123 - Campo Grande/MS', 'cliente');

-- Produtos: Pizzas Salgadas
INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem) VALUES
(1, 'Margherita', 'Molho de tomate artesanal, muçarela de búfala, manjericão fresco e azeite extra virgem.', 42.90, 'margherita.jpg'),
(1, 'Calabresa Acebolada', 'Calabresa fatiada, cebola roxa caramelizada e azeitonas pretas.', 39.90, 'calabresa.jpg'),
(1, 'Quatro Queijos', 'Muçarela, gorgonzola, parmesão e provolone gratinados.', 46.90, 'quatro-queijos.jpg'),
(1, 'Frango com Catupiry', 'Frango desfiado temperado com catupiry original.', 44.90, 'frango-catupiry.jpg'),
(1, 'Pepperoni', 'Generosa camada de pepperoni importado e muçarela.', 47.90, 'pepperoni.jpg'),
(1, 'Portuguesa', 'Presunto, ovos, cebola, pimentão, ervilha e azeitona.', 45.90, 'portuguesa.jpg');

-- Produtos: Pizzas Doces
INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem) VALUES
(2, 'Chocolate com Morango', 'Chocolate ao leite derretido e morangos frescos fatiados.', 38.90, 'choco-morango.jpg'),
(2, 'Banana com Canela', 'Banana caramelizada, canela e leite condensado.', 34.90, 'banana-canela.jpg');

-- Produtos: Bebidas
INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem) VALUES
(3, 'Refrigerante Lata 350ml', 'Coca-Cola, Guaraná ou Fanta.', 6.00, 'refrigerante.jpg'),
(3, 'Suco Natural 500ml', 'Laranja, limão ou maracujá.', 8.50, 'suco.jpg'),
(3, 'Água Mineral 500ml', 'Com ou sem gás.', 4.00, 'agua.jpg');

-- Produtos: Sobremesas
INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem) VALUES
(4, 'Petit Gateau', 'Bolinho de chocolate quente com sorvete de creme.', 18.90, 'petit-gateau.jpg'),
(4, 'Tiramisù', 'Clássica sobremesa italiana com café e mascarpone.', 16.90, 'tiramisu.jpg');
