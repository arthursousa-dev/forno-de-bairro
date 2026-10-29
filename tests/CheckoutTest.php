<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PDO;

final class CheckoutTest extends TestCase
{
    private PDO $pdo;
    private int $usuarioId;
    private int $produtoId;
    private ?int $categoriaId = null;

    protected function setUp(): void
    {
        $this->pdo = conectar();

        // categoria + produto de teste
        $cat = $this->pdo->query("SELECT id FROM categorias LIMIT 1")->fetch();
        if (!$cat) {
            $this->pdo->exec("INSERT INTO categorias (nome, slug) VALUES ('Categoria de Teste', 'categoria-de-teste')");
            $cat = $this->pdo->query("SELECT id FROM categorias WHERE slug = 'categoria-de-teste'")->fetch();
            $this->categoriaId = (int) $cat['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO produtos (categoria_id, nome, preco, disponivel) VALUES (:cat, :nome, :preco, true) RETURNING id'
        );
        $stmt->execute([':cat' => $cat['id'], ':nome' => 'Produto de Teste', ':preco' => 25.50]);
        $this->produtoId = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES ('Cliente Teste', :email, 'hash', 'cliente') RETURNING id"
        );
        $stmt->execute([':email' => 'checkout.teste.' . uniqid() . '@fornodobairro.local']);
        $this->usuarioId = (int) $stmt->fetchColumn();
    }

    protected function tearDown(): void
    {
        $this->pdo->prepare('DELETE FROM pedidos WHERE usuario_id = :id')->execute([':id' => $this->usuarioId]);
        $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute([':id' => $this->usuarioId]);
        $this->pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute([':id' => $this->produtoId]);
        if ($this->categoriaId) {
            $this->pdo->prepare('DELETE FROM categorias WHERE id = :id')->execute([':id' => $this->categoriaId]);
        }
    }

    public function test_pedido_valido_e_gravado_com_total_recalculado(): void
    {
        $resultado = criarPedido(
            $this->pdo,
            $this->usuarioId,
            'Rua das Acácias, 123 - Campo Grande/MS',
            'pix',
            null,
            [['id' => $this->produtoId, 'quantidade' => 3]]
        );

        $this->assertTrue($resultado['sucesso']);
        $this->assertNotNull($resultado['pedido_id']);
        $this->assertSame(76.50, $resultado['total'], 'total deveria ser 3 x 25.50, calculado a partir do banco');

        $pedido = $this->pdo->prepare('SELECT * FROM pedidos WHERE id = :id');
        $pedido->execute([':id' => $resultado['pedido_id']]);
        $linha = $pedido->fetch();

        $this->assertSame('pendente', $linha['status']);
        $this->assertEquals(76.50, $linha['total']);
    }

    public function test_preco_enviado_pelo_cliente_e_ignorado(): void
    {
        // Simula um preço adulterado vindo do front-end — a função
        // nunca deveria confiar nesse valor, só no que está no banco.
        $resultado = criarPedido(
            $this->pdo,
            $this->usuarioId,
            'Rua das Acácias, 123 - Campo Grande/MS',
            'dinheiro',
            null,
            [['id' => $this->produtoId, 'quantidade' => 1, 'preco' => 0.01]]
        );

        $this->assertTrue($resultado['sucesso']);
        $this->assertSame(25.50, $resultado['total'], 'deveria ignorar o preço 0.01 enviado e usar o preço real do banco');
    }

    public function test_endereco_curto_e_rejeitado(): void
    {
        $resultado = criarPedido($this->pdo, $this->usuarioId, 'Rua A', 'pix', null, [['id' => $this->produtoId, 'quantidade' => 1]]);

        $this->assertFalse($resultado['sucesso']);
        $this->assertNull($resultado['pedido_id']);
        $this->assertStringContainsString('endereço', $resultado['erro']);
    }

    public function test_forma_de_pagamento_invalida_e_rejeitada(): void
    {
        $resultado = criarPedido(
            $this->pdo, $this->usuarioId, 'Rua das Acácias, 123 - Campo Grande/MS',
            'boleto', null, [['id' => $this->produtoId, 'quantidade' => 1]]
        );

        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('pagamento', $resultado['erro']);
    }

    public function test_carrinho_vazio_e_rejeitado(): void
    {
        $resultado = criarPedido($this->pdo, $this->usuarioId, 'Rua das Acácias, 123 - Campo Grande/MS', 'pix', null, []);

        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('carrinho', $resultado['erro']);
    }

    public function test_produto_indisponivel_e_ignorado_no_pedido(): void
    {
        $this->pdo->prepare('UPDATE produtos SET disponivel = false WHERE id = :id')->execute([':id' => $this->produtoId]);

        $resultado = criarPedido(
            $this->pdo, $this->usuarioId, 'Rua das Acácias, 123 - Campo Grande/MS',
            'pix', null, [['id' => $this->produtoId, 'quantidade' => 1]]
        );

        // sem nenhum item disponível restando, o pedido é rejeitado
        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('carrinho', $resultado['erro']);
    }

    public function test_quantidade_e_limitada_entre_1_e_50(): void
    {
        $resultado = criarPedido(
            $this->pdo, $this->usuarioId, 'Rua das Acácias, 123 - Campo Grande/MS',
            'pix', null, [['id' => $this->produtoId, 'quantidade' => 999]]
        );

        $this->assertTrue($resultado['sucesso']);
        $this->assertSame(50 * 25.50, $resultado['total'], 'quantidade deveria ser limitada a 50 unidades');
    }

    public function test_itens_malformados_vindos_do_navegador_sao_descartados(): void
    {
        $resultado = criarPedido(
            $this->pdo, $this->usuarioId, 'Rua das Acácias, 123 - Campo Grande/MS',
            'pix', null, [42, 'x', ['id' => 'abc', 'quantidade' => 1], ['id' => $this->produtoId, 'quantidade' => 2]]
        );

        $this->assertTrue($resultado['sucesso']);
        $this->assertSame(2 * 25.50, $resultado['total']);
    }

    public function test_endereco_maior_que_a_coluna_e_rejeitado(): void
    {
        $resultado = criarPedido(
            $this->pdo, $this->usuarioId, str_repeat('Rua longa ', 30),
            'pix', null, [['id' => $this->produtoId, 'quantidade' => 1]]
        );

        $this->assertFalse($resultado['sucesso']);
        $this->assertStringContainsString('muito longos', $resultado['erro']);
    }
}
