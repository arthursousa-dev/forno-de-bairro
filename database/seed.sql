-- Forno do Bairro — dados de demonstração

INSERT INTO categorias (nome, slug) VALUES
('Pizzas Salgadas', 'pizzas-salgadas'),
('Pizzas Doces', 'pizzas-doces'),
('Bebidas', 'bebidas'),
('Sobremesas', 'sobremesas');

-- Administrador — senha: admin123
INSERT INTO usuarios (nome, email, senha_hash, telefone, tipo) VALUES
('Administrador', 'admin@fornodobairro.com.br',
 '$2y$10$46EEeXGuH5F02Wr5701HSOYI7Ha2Wzr1sRBlMSBOUmTfN0DQ0x45O',
 '(67) 99999-0000', 'admin');

-- Cliente de teste — senha: cliente123
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
