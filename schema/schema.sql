-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 23/09/2025 às 13:15
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u251646645_igob`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alojamento`
--

CREATE TABLE `alojamento` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `titulo` varchar(50) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `lat` varchar(15) DEFAULT NULL,
  `lon` varchar(15) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `status` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alojamento_despesa`
--

CREATE TABLE `alojamento_despesa` (
  `id` int(11) NOT NULL,
  `alojamento` int(11) NOT NULL,
  `titulo` varchar(50) DEFAULT NULL,
  `recebedor` varchar(50) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `custo` varchar(50) DEFAULT NULL,
  `recorrencia` varchar(50) DEFAULT NULL,
  `status` varchar(15) DEFAULT NULL,
  `situacao` varchar(255) DEFAULT 'ABERTO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `atividade`
--

CREATE TABLE `atividade` (
  `id` int(11) NOT NULL,
  `titulo` varchar(55) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `meta` float(9,2) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calc_distancia`
--

CREATE TABLE `calc_distancia` (
  `id` int(11) NOT NULL,
  `lat_inicio` varchar(17) NOT NULL,
  `lon_inicio` varchar(17) NOT NULL,
  `lat_fim` varchar(17) NOT NULL,
  `lon_fim` varchar(17) NOT NULL,
  `dista` varchar(15) NOT NULL,
  `distb` varchar(15) NOT NULL,
  `tempoa` varchar(35) NOT NULL,
  `tempob` varchar(35) NOT NULL,
  `identificador` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `calendario`
--

CREATE TABLE `calendario` (
  `data` date NOT NULL,
  `util_falta` varchar(1) DEFAULT 'n',
  `util_prod` varchar(1) DEFAULT NULL,
  `dia` int(2) DEFAULT NULL,
  `mes` int(2) DEFAULT NULL,
  `ano` int(4) DEFAULT NULL,
  `dia_semana` varchar(50) DEFAULT NULL,
  `dia_semanat2` varchar(50) DEFAULT NULL,
  `dia_semanat3` varchar(50) DEFAULT NULL,
  `mes_desc` varchar(50) DEFAULT NULL,
  `mes_desct2` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `centro_custo`
--

CREATE TABLE `centro_custo` (
  `id` varchar(11) NOT NULL DEFAULT '',
  `titulo` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidade`
--

CREATE TABLE `cidade` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `lat` varchar(15) DEFAULT NULL,
  `lon` varchar(15) DEFAULT NULL,
  `hospital` varchar(455) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `combustivel_saldo`
--

CREATE TABLE `combustivel_saldo` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `responsavel` int(11) DEFAULT NULL,
  `movimento` varchar(25) DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `usuario_responsavel` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `combustivel_solicitacao`
--

CREATE TABLE `combustivel_solicitacao` (
  `id` int(11) NOT NULL,
  `usuario_responsavel` int(11) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `posto` varchar(100) DEFAULT NULL,
  `equipe` varchar(100) DEFAULT NULL,
  `placa` varchar(8) DEFAULT NULL,
  `km` float(12,2) DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `motorista` varchar(255) DEFAULT NULL,
  `doc_tipo` varchar(25) DEFAULT NULL,
  `doc_numero` varchar(30) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `identificador` varchar(15) DEFAULT NULL,
  `img_bomba` varchar(255) DEFAULT 'AGUARDANDO',
  `img_hodometro` varchar(255) DEFAULT 'AGUARDANDO',
  `confirmacao` varchar(25) DEFAULT 'AGUARDANDO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_disparo`
--

CREATE TABLE `email_disparo` (
  `id` int(11) NOT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `titulo` varchar(35) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `programacao` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `situacao` varchar(35) DEFAULT NULL,
  `fonte` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_disparo_user`
--

CREATE TABLE `email_disparo_user` (
  `id` int(11) NOT NULL,
  `tipo` varchar(35) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL,
  `equipe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_grupo`
--

CREATE TABLE `email_grupo` (
  `id` int(11) NOT NULL,
  `descricao` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_grupo_usuario`
--

CREATE TABLE `email_grupo_usuario` (
  `id` int(11) NOT NULL,
  `email_grupo` int(11) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_rotina`
--

CREATE TABLE `email_rotina` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `rotina` varchar(25) DEFAULT NULL,
  `situacao` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `envolvido_grupo`
--

CREATE TABLE `envolvido_grupo` (
  `id` int(11) NOT NULL,
  `filial` int(255) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `envolvido_grupo_item`
--

CREATE TABLE `envolvido_grupo_item` (
  `id` int(11) NOT NULL,
  `grupo` int(11) DEFAULT NULL,
  `envolvido` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe`
--

CREATE TABLE `equipe` (
  `id` int(11) NOT NULL,
  `titulo` varchar(55) DEFAULT NULL,
  `encarregado` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `alojamento` int(11) DEFAULT NULL,
  `processo` int(11) DEFAULT NULL,
  `atividade` int(11) DEFAULT NULL,
  `coordenador` varchar(26) DEFAULT NULL,
  `supervisor` varchar(26) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO',
  `programacao` varchar(1) DEFAULT 'N',
  `veiculo` varchar(8) NOT NULL,
  `fiscal` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe_composicao`
--

CREATE TABLE `equipe_composicao` (
  `id` int(11) NOT NULL,
  `equipe` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` varchar(1) NOT NULL,
  `matricula_dinamo` varchar(15) NOT NULL,
  `matricula_concessionaria` varchar(15) NOT NULL,
  `funcao` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe_escala`
--

CREATE TABLE `equipe_escala` (
  `id` int(11) NOT NULL,
  `equipe` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `dia` int(2) DEFAULT NULL,
  `mes` int(2) DEFAULT NULL,
  `ano` int(4) DEFAULT NULL,
  `meta` float(8,2) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe_grupo`
--

CREATE TABLE `equipe_grupo` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipe_grupo_itens`
--

CREATE TABLE `equipe_grupo_itens` (
  `id` int(11) NOT NULL,
  `grupo` int(11) DEFAULT NULL,
  `equipe` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estrutura`
--

CREATE TABLE `estrutura` (
  `id` int(11) NOT NULL,
  `filial` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estrutura_itens`
--

CREATE TABLE `estrutura_itens` (
  `id` int(11) NOT NULL,
  `estrutura` int(3) NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `quantidade` double(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `faturamento`
--

CREATE TABLE `faturamento` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `pedido` varchar(100) DEFAULT NULL,
  `folha` varchar(100) DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamento`
--

CREATE TABLE `fechamento` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `data_envio_pasta` date DEFAULT NULL,
  `data_fechamento` date DEFAULT NULL,
  `data_postagem` date DEFAULT NULL,
  `valor_postagem` float(8,2) DEFAULT NULL,
  `postagem` varchar(15) DEFAULT 'AGUARDANDO',
  `data_solicitacao_termo` date DEFAULT NULL,
  `data_aprovacao_termo` date DEFAULT NULL,
  `valor_pagamento` float(8,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `pagamento` varchar(15) DEFAULT 'AGUARDANDO',
  `status_aprovacao` varchar(255) DEFAULT 'AGUARDANDO',
  `status_pasta` varchar(55) DEFAULT 'AGUARDANDO',
  `status_materiais` varchar(55) DEFAULT 'AGUARDANDO',
  `material_data_tratativa` date DEFAULT NULL,
  `material_data_envio` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamento_desenho`
--

CREATE TABLE `fechamento_desenho` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamento_medicao`
--

CREATE TABLE `fechamento_medicao` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamento_status`
--

CREATE TABLE `fechamento_status` (
  `id` int(11) NOT NULL,
  `descricao` varchar(85) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `fechamento_status_controle`
--

CREATE TABLE `fechamento_status_controle` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `filial`
--

CREATE TABLE `filial` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `responsavel` varchar(30) NOT NULL,
  `telefone` varchar(15) NOT NULL,
  `email` varchar(155) NOT NULL,
  `contratante_nome` varchar(55) NOT NULL,
  `contratante_telefone` varchar(55) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `folha`
--

CREATE TABLE `folha` (
  `cpf` varchar(26) NOT NULL DEFAULT '',
  `filial` varchar(20) DEFAULT NULL,
  `ccusto` varchar(11) DEFAULT NULL,
  `matricula` varchar(11) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `cargo` varchar(11) DEFAULT NULL,
  `estado` varchar(1) DEFAULT NULL,
  `admissao` date DEFAULT NULL,
  `demissao` date DEFAULT NULL,
  `nascimento` date DEFAULT NULL,
  `valor_agregacao` float(8,2) DEFAULT 0.00,
  `telefone` varchar(12) DEFAULT NULL,
  `email` varchar(85) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `importacao_apontador`
--

CREATE TABLE `importacao_apontador` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `cidade` int(11) DEFAULT NULL,
  `servico` int(11) DEFAULT NULL,
  `retorno` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_email_conclusao`
--

CREATE TABLE `log_email_conclusao` (
  `id` int(7) NOT NULL,
  `usuario` int(5) NOT NULL,
  `energizacao` date NOT NULL,
  `pms` varchar(75) NOT NULL,
  `si` varchar(75) NOT NULL,
  `observacao` text NOT NULL,
  `momento` datetime NOT NULL,
  `obra` int(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `material`
--

CREATE TABLE `material` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `codigo_atual` varchar(20) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `unidade` varchar(5) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nao_adesao`
--

CREATE TABLE `nao_adesao` (
  `id` int(11) NOT NULL,
  `programacao` int(11) DEFAULT NULL,
  `motivo` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `log` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nupost_base`
--

CREATE TABLE `nupost_base` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `filial` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nupost_imagem`
--

CREATE TABLE `nupost_imagem` (
  `id` int(11) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `identificador` varchar(50) DEFAULT NULL,
  `tipo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nupost_material`
--

CREATE TABLE `nupost_material` (
  `id` int(11) NOT NULL,
  `codigo` varchar(11) DEFAULT NULL,
  `material` varchar(55) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `estoque_min` float(8,2) DEFAULT NULL,
  `status` varchar(25) DEFAULT 'ATIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nupost_movimentacao`
--

CREATE TABLE `nupost_movimentacao` (
  `id` varchar(20) NOT NULL DEFAULT '',
  `movimentacao` varchar(25) DEFAULT NULL,
  `tipo` varchar(25) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `equipe` int(11) DEFAULT NULL,
  `veiculo` varchar(10) DEFAULT NULL,
  `material` int(11) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `quantidade` float(8,2) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `base` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra`
--

CREATE TABLE `obra` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nota` varchar(25) DEFAULT '',
  `pep` varchar(350) DEFAULT '',
  `reservas` varchar(255) DEFAULT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  `municipio` varchar(55) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `utd` varchar(255) DEFAULT NULL,
  `prioridade` varchar(55) DEFAULT NULL,
  `tipo` varchar(45) DEFAULT NULL,
  `solicitante` varchar(255) DEFAULT NULL,
  `poste_distribuicao` int(3) DEFAULT NULL,
  `poste_transmissao` int(3) DEFAULT NULL,
  `rede_transmissao` float(8,4) DEFAULT NULL,
  `rede_distribuicao` float(8,4) DEFAULT NULL,
  `data_entrada` date DEFAULT NULL,
  `data_finalizacao` date DEFAULT NULL,
  `valor_servico` float(9,2) DEFAULT NULL,
  `valor_material` float(8,2) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `log_criacao` datetime DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO',
  `situacao` varchar(15) DEFAULT 'PROGRAMAR',
  `situacao_cliente` varchar(55) DEFAULT NULL,
  `situacao_fechamento` varchar(55) DEFAULT 'AGUARDANDO',
  `desenho_fechamento` varchar(255) DEFAULT 'AGUARDANDO',
  `reserva_informada` varchar(125) DEFAULT 'AGUARDANDO',
  `validar_lista` varchar(125) DEFAULT 'AGUARDANDO',
  `reserva_validada` varchar(125) DEFAULT 'AGUARDANDO',
  `material_fisico` varchar(125) DEFAULT 'AGUARDANDO',
  `responsavel` int(11) DEFAULT NULL,
  `responsavel_cliente` int(11) DEFAULT NULL,
  `responsavel_fechamento` int(11) DEFAULT NULL,
  `levantador` int(255) DEFAULT NULL,
  `projetista` int(11) DEFAULT NULL,
  `lat` varchar(18) DEFAULT '0',
  `lon` varchar(18) DEFAULT '0',
  `asbuilt` varchar(3) DEFAULT 'NÃO',
  `cliente_normal` int(3) DEFAULT 0,
  `cliente_especial` int(3) DEFAULT 0,
  `contrato` varchar(22) DEFAULT NULL,
  `legenda` varchar(22) DEFAULT NULL,
  `parada` varchar(3) NOT NULL DEFAULT 'NAO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_envolvidos`
--

CREATE TABLE `obra_envolvidos` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_estrutura`
--

CREATE TABLE `obra_estrutura` (
  `id` int(7) NOT NULL,
  `estrutura` int(7) NOT NULL,
  `qtd_estrutura` float(8,2) NOT NULL,
  `material` varchar(15) NOT NULL,
  `qtd_material` float(8,2) NOT NULL,
  `obra` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_evidencia`
--

CREATE TABLE `obra_evidencia` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `servico` int(11) DEFAULT NULL,
  `tipo` varchar(55) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `extensao` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_material`
--

CREATE TABLE `obra_material` (
  `id` int(11) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `quantidade` float(8,2) DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `log_entrada` datetime DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `status` varchar(35) DEFAULT 'ATIVO',
  `log_removido` datetime DEFAULT NULL,
  `user_removeu` int(11) DEFAULT NULL,
  `reserva` varchar(125) DEFAULT 'AGUARDANDO',
  `fisico` varchar(125) DEFAULT 'AGUARDANDO',
  `log_reserva` datetime DEFAULT NULL,
  `motivo` varchar(35) DEFAULT NULL,
  `envio` varchar(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_mat_devolucao`
--

CREATE TABLE `obra_mat_devolucao` (
  `id` int(11) NOT NULL,
  `solicitacao` varchar(30) DEFAULT NULL,
  `programacao` int(11) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `solicitante` int(11) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `status` varchar(25) DEFAULT 'SOLICITADO',
  `aprovador` int(11) DEFAULT NULL,
  `log_aprovacao` datetime DEFAULT NULL,
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_mat_itens_separacao`
--

CREATE TABLE `obra_mat_itens_separacao` (
  `id` int(11) NOT NULL,
  `solicitacao` varchar(30) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `programacao` int(11) DEFAULT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `quantidade` float(8,2) DEFAULT 0.00,
  `devolvido` float(8,2) DEFAULT 0.00,
  `separado` float(8,2) DEFAULT 0.00,
  `quant_saldo` float(8,2) DEFAULT 0.00,
  `user` int(11) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `obra_material` int(11) DEFAULT NULL,
  `observacao` varchar(150) NOT NULL,
  `comentario` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_mat_separacao`
--

CREATE TABLE `obra_mat_separacao` (
  `id` int(11) NOT NULL,
  `solicitacao` varchar(30) DEFAULT NULL,
  `programacao` int(11) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `solicitante` int(11) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `status` varchar(25) DEFAULT 'SOLICITADO',
  `aprovador` int(11) DEFAULT NULL,
  `log_aprovacao` datetime DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `comentario_solicitacao` text DEFAULT NULL,
  `tipo_reserva` varchar(255) DEFAULT NULL,
  `num_reserva` varchar(255) DEFAULT NULL,
  `devolvido` varchar(3) NOT NULL DEFAULT 'NAO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_prioridade`
--

CREATE TABLE `obra_prioridade` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_servico`
--

CREATE TABLE `obra_servico` (
  `id` int(11) NOT NULL,
  `servico` varchar(15) DEFAULT NULL,
  `quantidade` float(9,2) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `observacao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_situacao`
--

CREATE TABLE `obra_situacao` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `tipo` varchar(55) DEFAULT NULL,
  `situacao` text DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `comentario` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `obra_tipo`
--

CREATE TABLE `obra_tipo` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `powerbi`
--

CREATE TABLE `powerbi` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `link` text DEFAULT NULL,
  `incorporacao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `preapr`
--

CREATE TABLE `preapr` (
  `id` int(4) NOT NULL,
  `obra` int(7) NOT NULL,
  `responsavel` int(7) NOT NULL,
  `data` date NOT NULL,
  `tipotrabalho` varchar(40) NOT NULL,
  `tiporede` varchar(40) NOT NULL,
  `niveltensao` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `preapr_itens`
--

CREATE TABLE `preapr_itens` (
  `id` int(3) NOT NULL,
  `grupo_id` int(2) DEFAULT NULL,
  `grupo_desc` varchar(18) DEFAULT NULL,
  `tipo` varchar(6) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `img` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `preapr_registros`
--

CREATE TABLE `preapr_registros` (
  `id` int(4) NOT NULL,
  `obra` int(7) NOT NULL,
  `item` int(7) NOT NULL,
  `resposta` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `processo`
--

CREATE TABLE `processo` (
  `id` int(11) NOT NULL,
  `titulo` varchar(55) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO',
  `apresentar_bi` varchar(1) DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `programacao`
--

CREATE TABLE `programacao` (
  `id` int(11) NOT NULL,
  `equipe` int(11) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `desligamento` varchar(25) DEFAULT NULL,
  `si` varchar(105) DEFAULT 'AGUARDANDO',
  `si_status` varchar(25) DEFAULT 'AGUARDANDO',
  `linhaviva` varchar(25) DEFAULT NULL,
  `tipo` varchar(25) DEFAULT NULL,
  `programador` int(11) DEFAULT NULL,
  `log_programacao` datetime DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `log_cancelamento` varchar(150) DEFAULT NULL,
  `justificativa_cancelamento` text DEFAULT NULL,
  `previsao_finalizacao` varchar(1) DEFAULT 'N',
  `postes` int(11) DEFAULT 0,
  `cavas` float(8,2) DEFAULT 0.00,
  `equipamentos` int(11) DEFAULT 0,
  `vaos_cabo` int(11) DEFAULT 0,
  `clientes` int(11) DEFAULT 0,
  `base` int(3) DEFAULT 0,
  `financeiro` float(9,2) DEFAULT 0.00,
  `tipo_reserva` varchar(20) DEFAULT NULL,
  `turno` varchar(10) DEFAULT 'DIA TODO',
  `num_reserva` varchar(255) DEFAULT NULL,
  `informacoes` text DEFAULT NULL,
  `comentario_retorno` text DEFAULT NULL,
  `cobrar_disponibilidade` varchar(3) DEFAULT '',
  `filial` int(11) DEFAULT NULL,
  `estruturas` int(11) DEFAULT 0,
  `anomalias` int(3) NOT NULL DEFAULT 0,
  `inicio` varchar(5) DEFAULT NULL,
  `fim` varchar(5) DEFAULT NULL,
  `documento` varchar(10) NOT NULL DEFAULT 'A',
  `planejamento` text DEFAULT NULL,
  `inicio_desl` time DEFAULT '00:00:00',
  `fim_desl` time DEFAULT '00:00:00',
  `inst_solicitada` text DEFAULT NULL,
  `nivel_tensao` varchar(10) DEFAULT 'N.INFO',
  `uso_compartilhado` varchar(255) DEFAULT NULL,
  `pre_apr` varchar(155) DEFAULT NULL,
  `resp_inter` varchar(255) DEFAULT NULL,
  `suplente_inter` varchar(255) DEFAULT NULL,
  `encarregados` varchar(255) DEFAULT NULL,
  `suplente_encarreg` varchar(255) DEFAULT NULL,
  `protecao_corte` varchar(100) DEFAULT NULL,
  `comentario_desligamento` text DEFAULT NULL,
  `referencia_coordenada` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `programacao_estrutura`
--

CREATE TABLE `programacao_estrutura` (
  `id` int(7) NOT NULL,
  `estrutura` int(7) NOT NULL,
  `qtd_estrutura` float(8,2) NOT NULL,
  `material` varchar(15) NOT NULL,
  `qtd_material` float(8,2) NOT NULL,
  `programacao` int(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `programacao_item_servico`
--

CREATE TABLE `programacao_item_servico` (
  `id` int(11) NOT NULL,
  `programacao_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `quantidade` float(9,2) NOT NULL,
  `valor_unitario` float(8,2) NOT NULL,
  `valor_total` float(9,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `programacao_servico`
--

CREATE TABLE `programacao_servico` (
  `id` int(11) NOT NULL,
  `programacao` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `equipe` int(11) DEFAULT NULL,
  `obra` int(11) DEFAULT NULL,
  `servico` int(11) DEFAULT NULL,
  `quantidade` float(8,2) DEFAULT NULL,
  `quant_prog_hist` float(8,2) DEFAULT NULL,
  `log_cadastro` varchar(125) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `status` varchar(15) DEFAULT 'PROGRAMADO',
  `validacao` varchar(125) DEFAULT 'AGUARDANDO',
  `log_execucao` varchar(125) DEFAULT NULL,
  `data_execucao` date DEFAULT NULL,
  `horainicio` time DEFAULT NULL,
  `horafim` time DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `comentario_exec` text DEFAULT NULL,
  `os` varchar(35) DEFAULT NULL,
  `si` varchar(35) DEFAULT NULL,
  `ocorrencia` varchar(35) DEFAULT NULL,
  `cidade` varchar(55) DEFAULT NULL,
  `zona` varchar(55) DEFAULT NULL,
  `utd` varchar(55) DEFAULT NULL,
  `log_exclusao` varchar(125) DEFAULT NULL,
  `fatorcorrecao` float(8,2) DEFAULT 1.00,
  `log_lancamento` datetime DEFAULT NULL,
  `user_lancamento` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `saldo_material`
--

CREATE TABLE `saldo_material` (
  `id` int(11) NOT NULL,
  `codigo` varchar(15) DEFAULT NULL,
  `quantidade` float(8,2) DEFAULT NULL,
  `log` datetime DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `servico`
--

CREATE TABLE `servico` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `codigo` varchar(15) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `grupo` varchar(25) DEFAULT NULL,
  `valor` float(8,2) DEFAULT NULL,
  `min` float(4,2) DEFAULT NULL,
  `hora` float(4,2) DEFAULT NULL,
  `dia` float(4,2) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO',
  `evidencia` varchar(3) DEFAULT 'NÃO',
  `ponto` float(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `status_cancelamento`
--

CREATE TABLE `status_cancelamento` (
  `id` int(11) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL,
  `tipo` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `templatepi`
--

CREATE TABLE `templatepi` (
  `id` int(11) NOT NULL,
  `posicao` int(2) DEFAULT NULL,
  `zona` varchar(37) DEFAULT NULL,
  `equipe` varchar(45) DEFAULT NULL,
  `atividade` text DEFAULT NULL,
  `template` varchar(35) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(11) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO',
  `primeiro_acesso` varchar(1) DEFAULT 'S',
  `grupo` int(2) DEFAULT NULL,
  `meta_fechamento` float(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_filial`
--

CREATE TABLE `usuario_filial` (
  `id` int(11) NOT NULL,
  `usuario` int(11) DEFAULT NULL,
  `filial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_grupo`
--

CREATE TABLE `usuario_grupo` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `status` varchar(15) DEFAULT 'ATIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `utd`
--

CREATE TABLE `utd` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `valor_servico`
--

CREATE TABLE `valor_servico` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `poste` float(8,2) DEFAULT NULL,
  `cava` float(8,2) DEFAULT NULL,
  `rede` float(8,2) DEFAULT NULL,
  `equipamento` float(8,2) DEFAULT NULL,
  `cliente` float(8,2) DEFAULT NULL,
  `base` float(8,2) DEFAULT 0.00,
  `estrutura` float(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculo`
--

CREATE TABLE `veiculo` (
  `id` int(11) NOT NULL,
  `filial` int(11) DEFAULT NULL,
  `placa` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `viabilidade`
--

CREATE TABLE `viabilidade` (
  `id` int(11) NOT NULL,
  `obra` int(11) DEFAULT NULL,
  `viabilizador` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `poste_instalado` int(11) DEFAULT NULL,
  `poste_retirado` int(11) DEFAULT NULL,
  `poste_realocado` int(11) DEFAULT NULL,
  `cabo_instalado` int(11) DEFAULT NULL,
  `cabo_retirado` int(11) DEFAULT NULL,
  `cabo_realocado` int(11) DEFAULT NULL,
  `trafo_instalado` int(11) DEFAULT NULL,
  `trafo_retirado` int(11) DEFAULT NULL,
  `trafo_realocado` int(11) DEFAULT NULL,
  `equip_instalado` int(11) DEFAULT NULL,
  `equip_retirado` int(11) DEFAULT NULL,
  `equip_realocado` int(11) DEFAULT NULL,
  `comentario_pontos` text DEFAULT NULL,
  `equipamento` varchar(15) DEFAULT NULL,
  `solo` varchar(15) DEFAULT NULL,
  `manobra` varchar(15) DEFAULT NULL,
  `arrastamento` varchar(15) DEFAULT NULL,
  `linha_viva` varchar(15) DEFAULT NULL,
  `desligamento` varchar(15) DEFAULT NULL,
  `area_comercial` varchar(15) DEFAULT NULL,
  `poda` varchar(15) DEFAULT NULL,
  `acesso_caminhao` varchar(15) DEFAULT NULL,
  `acesso_caminhao_coment` text DEFAULT NULL,
  `poda_coment` text DEFAULT NULL,
  `area_comercial_coment` text DEFAULT NULL,
  `desligamento_coment` text DEFAULT NULL,
  `linha_viva_coment` text DEFAULT NULL,
  `arrastamento_coment` text DEFAULT NULL,
  `manobra_coment` text DEFAULT NULL,
  `solo_coment` text DEFAULT NULL,
  `equipamento_coment` text DEFAULT NULL,
  `comentario_geral` text DEFAULT NULL,
  `porteira` varchar(15) DEFAULT NULL,
  `corrego` varchar(15) DEFAULT NULL,
  `exec_projeto` varchar(15) DEFAULT NULL,
  `qtd_equipes` varchar(15) DEFAULT NULL,
  `apoio_transito` varchar(15) DEFAULT NULL,
  `mutuo` varchar(15) DEFAULT NULL,
  `dia_util` varchar(15) DEFAULT NULL,
  `comunicacao` varchar(15) DEFAULT NULL,
  `gerador` varchar(15) DEFAULT NULL,
  `cliente_vital` varchar(15) DEFAULT NULL,
  `acesso_carreta` varchar(15) DEFAULT NULL,
  `descarga_poste` varchar(15) DEFAULT NULL,
  `porteira_coment` text DEFAULT NULL,
  `corrego_coment` text DEFAULT NULL,
  `exec_projeto_coment` text DEFAULT NULL,
  `qtd_equipes_coment` text DEFAULT NULL,
  `apoio_transito_coment` text DEFAULT NULL,
  `mutuo_coment` text DEFAULT NULL,
  `dia_util_coment` text DEFAULT NULL,
  `comunicacao_coment` text DEFAULT NULL,
  `gerador_coment` text DEFAULT NULL,
  `cliente_vital_coment` text DEFAULT NULL,
  `acesso_carreta_coment` text DEFAULT NULL,
  `descarga_poste_coment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_analise1`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_analise1` (
`data` date
,`equipe` varchar(55)
,`supervisor` varchar(255)
,`meta` double(8,2)
,`produzido` double(19,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_bloqueio_evidencia`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_bloqueio_evidencia` (
`obra` int(11)
,`user_lancamento` int(11)
,`servico` int(11)
,`aguard_evidencia` bigint(21)
,`evidencias` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_calendario_set`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_calendario_set` (
`data` date
,`util_falta` varchar(1)
,`util_prod` varchar(1)
,`dia` int(2)
,`mes` int(2)
,`ano` int(4)
,`dia_semana` varchar(50)
,`dia_semanat2` varchar(50)
,`dia_semanat3` varchar(50)
,`mes_desc` varchar(50)
,`mes_desct2` varchar(50)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_coordenador`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_coordenador` (
`coordenador` varchar(26)
,`nome` varchar(255)
,`filial` int(11)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_data_serv`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_data_serv` (
`id_obra` int(11)
,`ultimo_servico` date
,`primeiro_servico` date
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_dias_projecao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_dias_projecao` (
`equipe` int(11)
,`corridos` bigint(21)
,`faltantes` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_equipes_ativas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_equipes_ativas` (
`id` int(11)
,`filial` int(11)
,`equipe` varchar(55)
,`coordenador_id` varchar(26)
,`supervisor_id` varchar(26)
,`encarregado` varchar(255)
,`processo` int(11)
,`atividade_id` int(11)
,`lat` varchar(15)
,`lon` varchar(15)
,`atividade` varchar(55)
,`coordenador` varchar(255)
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_escala_setembro`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_escala_setembro` (
`id` int(11)
,`filial` int(11)
,`equipe` int(11)
,`data` date
,`dia` int(2)
,`mes` int(2)
,`ano` int(4)
,`meta` double(19,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_fechamento_medicao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_fechamento_medicao` (
`id` int(11)
,`obra` int(11)
,`valor` float(8,2)
,`data` date
,`comentario` text
,`log` datetime
,`usuario` int(11)
,`filial` int(11)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_floriano_diario`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_floriano_diario` (
`id` int(11)
,`tipo` varchar(55)
,`situacao` text
,`log` datetime
,`dia` date
,`comentario` text
,`nome` varchar(255)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_floriano_documentos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_floriano_documentos` (
`tipo` varchar(55)
,`comentario` text
,`endereco` varchar(281)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_floriano_estruturas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_floriano_estruturas` (
`grupo_servico` varchar(25)
,`quantidade_locacao` double(19,2)
,`quantidade_supressao` double(19,2)
,`quantidade_escavacao` int(11)
,`quantidade_dist_estrutura` int(11)
,`quantidade_implantacao` int(11)
,`quantidade_aterramento` int(11)
,`quantidade_lanc_cabo` int(11)
,`quantidade_nivelamento` int(11)
,`quantidade_pintura` int(11)
,`quantidade_comissionamento` int(11)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_floriano_programacao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_floriano_programacao` (
`titulo` varchar(55)
,`data` date
,`hoje` date
,`acumulado` double(19,2)
,`programado` double(19,2)
,`executado` double(19,2)
,`maior_prog` date
,`menor_prog` date
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_indic_financeiro`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_indic_financeiro` (
`equipe` int(11)
,`filial` int(11)
,`data` date
,`hoje` date
,`programado` double(19,2)
,`meta` double(19,2)
,`produzido` double(19,2)
,`homem_hora_prog` decimal(22,0)
,`obra_programada` decimal(32,0)
,`viabilidade` varchar(15)
,`data_finalizacao` date
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_indic_retorno_prazo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_indic_retorno_prazo` (
`solicitacao` varchar(30)
,`programacao` int(11)
,`equipe` int(11)
,`filial` int(11)
,`status` varchar(25)
,`data_retorno` date
,`data_solicitacao` date
,`dias_dif` int(8)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_indic_solicit_mat`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_indic_solicit_mat` (
`solicitacao` varchar(30)
,`programacao` int(11)
,`equipe` int(11)
,`filial` int(11)
,`data_solicitacao` date
,`data_programacao` date
,`dias_dif` int(8)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_looker_equipesMeta`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_looker_equipesMeta` (
`titulo` varchar(55)
,`atividade` varchar(55)
,`processo` varchar(55)
,`meta` float(9,2)
,`filial` varchar(255)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_looker_fechamentoEmedicao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_looker_fechamentoEmedicao` (
`quantidade` bigint(21)
,`data` varchar(10)
,`filial` int(11)
,`tipo` varchar(45)
,`situacao` varchar(10)
,`valor_executado` double(19,2)
,`medido` double(19,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_looker_obras`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_looker_obras` (
`id` int(11)
,`filial` int(11)
,`filial_titulo` varchar(255)
,`codigo` varchar(20)
,`descricao` varchar(100)
,`municipio` varchar(55)
,`utd` varchar(255)
,`tipo` varchar(45)
,`data_entrada` date
,`data_finalizacao` date
,`dias_vencimento` bigint(10)
,`status` varchar(15)
,`situacao` varchar(15)
,`situacao_fechamento` varchar(55)
,`responsavel` varchar(255)
,`viabilidade` bigint(21)
,`valor_orcado` float(9,2)
,`valor_executado` double(19,2)
,`medicao` double(19,2)
,`lat` varchar(18)
,`lon` varchar(18)
,`postes_orc` decimal(33,0)
,`postes_instalados` double(19,2)
,`primeira_prog` date
,`ultima_prog` date
,`ultimo_lanc` date
,`nota` varchar(25)
,`pep` varchar(350)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_looker_programacaoEquipe`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_looker_programacaoEquipe` (
`data` date
,`filial` int(11)
,`titulo` varchar(55)
,`meta` double(8,2)
,`meta_global` float(9,2)
,`atividade` varchar(55)
,`processo` varchar(55)
,`programacoes` bigint(21)
,`postes` decimal(32,0)
,`financeiro_programado` double(19,2)
,`poste_instalado` double(19,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_mapa`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_mapa` (
`id_obra` varchar(11)
,`id_equipe` varchar(11)
,`descricao` varchar(100)
,`lat` varchar(18)
,`lon` varchar(18)
,`tipo` varchar(6)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_momento`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_momento` (
`atualizacao` datetime /* mariadb-5.3 */
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_obras_evidencia`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_obras_evidencia` (
`id` int(11)
,`codigo` varchar(20)
,`filial` int(11)
,`descricao` varchar(100)
,`municipio` varchar(55)
,`tipo` varchar(45)
,`situacao` varchar(15)
,`situacao_fechamento` varchar(55)
,`data_entrada` date
,`asbuilt` bigint(21)
,`documento` bigint(21)
,`obra_concluida` bigint(21)
,`servico` bigint(21)
,`viabilidade` bigint(21)
,`levantamento` bigint(21)
,`base` bigint(21)
,`cava` bigint(21)
,`sucata` bigint(21)
,`poste` bigint(21)
,`transformador` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_obras_fechamento`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_obras_fechamento` (
`filial` int(11)
,`obra_id` int(11)
,`codigo` varchar(20)
,`pep` varchar(350)
,`data_entrada` date
,`dias_vencimento_projeto` bigint(21)
,`data_limite_projeto` date
,`data_finalizacao` date
,`dias_venciamento` bigint(21)
,`descricao` varchar(100)
,`municipio` varchar(55)
,`utd` varchar(255)
,`tipo` varchar(45)
,`prioridade` varchar(55)
,`postes` bigint(12)
,`situacao` varchar(15)
,`situacao_cliente` varchar(55)
,`situacao_fechamento` varchar(55)
,`desenho_fechamento` varchar(255)
,`valor_servico` float(9,2)
,`observacao` text
,`responsavel` varchar(255)
,`responsavel_fechamento` varchar(255)
,`projetista` varchar(255)
,`data_envio_pasta` date
,`data_fechamento` date
,`data_postagem` date
,`valor_postagem` double(19,2)
,`data_solicitacao_termo` date
,`data_aprovacao_termo` date
,`pagamento` varchar(15)
,`postagem` varchar(15)
,`valor_medido` double(19,2)
,`data_pagamento` date
,`valor_pagamento` float(8,2)
,`status_pasta` varchar(55)
,`status_aprovacao` varchar(255)
,`status_materiais` varchar(55)
,`material_data_envio` date
,`material_data_tratativa` date
,`valor_faturado` double(19,2)
,`executado` double(19,2)
,`ultima_programacao` date
,`primeira_programacao` date
,`ultimo_lanc` date
,`ultima_movimentacao` date
,`lat` varchar(18)
,`lon` varchar(18)
,`viabilidade` varchar(15)
,`finalizacao` date
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_obra_evolucao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_obra_evolucao` (
`id` int(11)
,`filial` int(11)
,`codigo` varchar(20)
,`descricao` varchar(100)
,`situacao` varchar(15)
,`asbuilt` varchar(3)
,`responsavel` int(11)
,`executado` double(19,2)
,`orcado` double(19,2)
,`data_entrada` date
,`ultimo_lanc` date
,`primeiro_lanc` date
,`dias_na_obra` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_obra_situacao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_obra_situacao` (
`id` int(11)
,`filial` int(11)
,`tipo` varchar(55)
,`situacao` text
,`comentario` text
,`nome` varchar(255)
,`log` datetime
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_pbi_meta_prog_exec`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_pbi_meta_prog_exec` (
`data` date
,`filial` int(11)
,`encarregado` varchar(255)
,`titulo` varchar(55)
,`atividade` varchar(55)
,`programado` double(19,2)
,`meta` double(8,2)
,`executado` double(19,2)
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_pbi_programacoes`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_pbi_programacoes` (
`id` int(11)
,`filial` int(11)
,`data` date
,`desligamento` varchar(25)
,`si` varchar(105)
,`si_status` varchar(25)
,`tipo` varchar(25)
,`log_programacao` datetime
,`observacao` text
,`informacoes` text
,`justificativa_cancelamento` text
,`postes` int(11)
,`cavas` float(8,2)
,`equipamentos` int(11)
,`vaos_cabo` int(11)
,`clientes` int(11)
,`base` int(3)
,`estruturas` int(11)
,`financeiro` float(9,2)
,`turno` varchar(10)
,`equipe` varchar(55)
,`obra_descricao` varchar(100)
,`obra_codigo` varchar(20)
,`programador` varchar(255)
,`atividade` varchar(55)
,`obra_tipo` varchar(45)
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_perc_atividade`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_perc_atividade`  AS SELECT `v_projecao_equipe`.`filial` AS `filial`, `v_projecao_equipe`.`atividade` AS `atividade`, sum(`v_projecao_equipe`.`produzido`) AS `produzido`, sum(`v_projecao_equipe`.`meta_acumulada`) AS `meta_acumulada`, (sum(`v_projecao_equipe`.`produzido`) / sum(`v_projecao_equipe`.`meta_acumulada`) - 1) * 100 AS `percentual`, sum(coalesce(`v_projecao_equipe`.`produzido`,0)) - sum(`v_projecao_equipe`.`meta_acumulada`) AS `diferenca`, sum(`v_projecao_equipe`.`projecao`) AS `projecao`, sum(`v_projecao_equipe`.`meta_mes`) AS `meta_mes`, count(case when `v_projecao_equipe`.`produzido` >= `v_projecao_equipe`.`meta_acumulada` then 1 else NULL end) AS `equipes_batendo_meta`, count(case when `v_projecao_equipe`.`produzido` < `v_projecao_equipe`.`meta_acumulada` then 1 when `v_projecao_equipe`.`produzido` is null then 1 else NULL end) AS `equipes_fora_meta`, count(1) AS `equipes` FROM `v_projecao_equipe` GROUP BY `v_projecao_equipe`.`atividade` ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_perc_coordenador`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_perc_coordenador`  AS SELECT `v_projecao_equipe`.`filial` AS `filial`, `v_projecao_equipe`.`coordenador` AS `coordenador`, sum(`v_projecao_equipe`.`produzido`) AS `produzido`, sum(`v_projecao_equipe`.`meta_acumulada`) AS `meta_acumulada`, (sum(`v_projecao_equipe`.`produzido`) / sum(`v_projecao_equipe`.`meta_acumulada`) - 1) * 100 AS `percentual`, sum(coalesce(`v_projecao_equipe`.`produzido`,0)) - sum(`v_projecao_equipe`.`meta_acumulada`) AS `diferenca`, sum(`v_projecao_equipe`.`projecao`) AS `projecao`, sum(`v_projecao_equipe`.`meta_mes`) AS `meta_mes`, count(case when `v_projecao_equipe`.`produzido` >= `v_projecao_equipe`.`meta_acumulada` then 1 else NULL end) AS `equipes_batendo_meta`, count(case when `v_projecao_equipe`.`produzido` < `v_projecao_equipe`.`meta_acumulada` then 1 when `v_projecao_equipe`.`produzido` is null then 1 else NULL end) AS `equipes_fora_meta`, count(1) AS `equipes` FROM `v_projecao_equipe` GROUP BY `v_projecao_equipe`.`coordenador`, `v_projecao_equipe`.`filial` ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_perc_filial`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_perc_filial`  AS SELECT `v_projecao_equipe`.`filial` AS `filial`, sum(`v_projecao_equipe`.`produzido`) AS `produzido`, sum(`v_projecao_equipe`.`meta_acumulada`) AS `meta_acumulada`, (sum(`v_projecao_equipe`.`produzido`) / sum(`v_projecao_equipe`.`meta_acumulada`) - 1) * 100 AS `percentual`, sum(`v_projecao_equipe`.`produzido`) - sum(`v_projecao_equipe`.`meta_acumulada`) AS `diferenca`, sum(`v_projecao_equipe`.`projecao`) AS `projecao`, sum(`v_projecao_equipe`.`meta_mes`) AS `meta_mes`, count(case when `v_projecao_equipe`.`produzido` >= `v_projecao_equipe`.`meta_acumulada` then 1 else NULL end) AS `equipes_batendo_meta`, count(case when `v_projecao_equipe`.`produzido` < `v_projecao_equipe`.`meta_acumulada` then 1 when `v_projecao_equipe`.`produzido` is null then 1 else NULL end) AS `equipes_fora_meta`, count(1) AS `equipes` FROM `v_projecao_equipe` GROUP BY `v_projecao_equipe`.`filial` ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_perc_processo`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_perc_processo`  AS SELECT `v_projecao_equipe`.`filial` AS `filial`, `v_projecao_equipe`.`processo` AS `processo`, sum(`v_projecao_equipe`.`produzido`) AS `produzido`, sum(`v_projecao_equipe`.`meta_acumulada`) AS `meta_acumulada`, (sum(`v_projecao_equipe`.`produzido`) / sum(`v_projecao_equipe`.`meta_acumulada`) - 1) * 100 AS `percentual`, sum(coalesce(`v_projecao_equipe`.`produzido`,0)) - sum(`v_projecao_equipe`.`meta_acumulada`) AS `diferenca`, sum(`v_projecao_equipe`.`projecao`) AS `projecao`, sum(`v_projecao_equipe`.`meta_mes`) AS `meta_mes`, count(case when `v_projecao_equipe`.`produzido` >= `v_projecao_equipe`.`meta_acumulada` then 1 else NULL end) AS `equipes_batendo_meta`, count(case when `v_projecao_equipe`.`produzido` < `v_projecao_equipe`.`meta_acumulada` then 1 when `v_projecao_equipe`.`produzido` is null then 1 else NULL end) AS `equipes_fora_meta`, count(1) AS `equipes` FROM `v_projecao_equipe` GROUP BY `v_projecao_equipe`.`processo` ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `v_perc_supervisor`
--

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_perc_supervisor`  AS SELECT `v_projecao_equipe`.`filial` AS `filial`, `v_projecao_equipe`.`supervisor` AS `supervisor`, sum(`v_projecao_equipe`.`produzido`) AS `produzido`, sum(`v_projecao_equipe`.`meta_acumulada`) AS `meta_acumulada`, (sum(`v_projecao_equipe`.`produzido`) / sum(`v_projecao_equipe`.`meta_acumulada`) - 1) * 100 AS `percentual`, sum(`v_projecao_equipe`.`produzido`) - sum(`v_projecao_equipe`.`meta_acumulada`) AS `diferenca`, sum(`v_projecao_equipe`.`projecao`) AS `projecao`, sum(`v_projecao_equipe`.`meta_mes`) AS `meta_mes`, count(case when `v_projecao_equipe`.`produzido` >= `v_projecao_equipe`.`meta_acumulada` then 1 else NULL end) AS `equipes_batendo_meta`, count(case when `v_projecao_equipe`.`produzido` < `v_projecao_equipe`.`meta_acumulada` then 1 when `v_projecao_equipe`.`produzido` is null then 1 else NULL end) AS `equipes_fora_meta`, count(1) AS `equipes` FROM `v_projecao_equipe` GROUP BY `v_projecao_equipe`.`supervisor` ;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_programado`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_programado` (
`id` int(11)
,`data` date
,`equipe` int(11)
,`valor_total` double(19,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_quant_viabilidade`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_quant_viabilidade` (
`id` int(11)
,`quant_viabiliade` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_servicos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_servicos` (
`data` date
,`mes` int(3)
,`ano` int(5)
,`filial` int(11)
,`equipe` int(11)
,`atividade` int(11)
,`processo` int(11)
,`os` varchar(35)
,`si` varchar(35)
,`ocorrencia` varchar(35)
,`obra_id` int(11)
,`obra_codigo` varchar(20)
,`obra_descricao` varchar(100)
,`servico_codigo` varchar(15)
,`descricao` varchar(255)
,`grupo_servico` varchar(25)
,`quantidade` double(18,1)
,`valor` float(8,2)
,`fatorcorrecao` float(8,2)
,`valor_total` double(19,2)
,`coordenador` varchar(26)
,`supervisor` varchar(26)
,`comentario_exec` text
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_supervisor`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_supervisor` (
`supervisor` varchar(26)
,`nome` varchar(255)
,`filial` int(11)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_usuario_fechamento`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_usuario_fechamento` (
`id` int(11)
,`nome` varchar(255)
,`filial` int(11)
,`meta_fechamento` float(10,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_wpp_resumo_programacao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_wpp_resumo_programacao` (
`filial` int(11)
,`titulo` varchar(255)
,`data` date
,`postes` decimal(32,0)
,`cavas` double(19,2)
,`equipamentos` decimal(32,0)
,`vaos_cabo` decimal(32,0)
,`clientes` decimal(32,0)
,`previsao_finalizacao` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_wpp_resumo_programacao_semana`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_wpp_resumo_programacao_semana` (
`filial` int(11)
,`titulo` varchar(255)
,`min_data` date
,`max_data` date
,`postes` decimal(32,0)
,`cavas` double(19,2)
,`equipamentos` decimal(32,0)
,`vaos_cabo` decimal(32,0)
,`clientes` decimal(32,0)
,`previsao_finalizacao` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v__equipe_na_obra`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v__equipe_na_obra` (
`id` int(11)
,`filial` int(11)
,`equipe` int(11)
,`data` date
,`obra` int(11)
,`turno` varchar(10)
,`meta` float(8,2)
,`meta_obra` double(12,6)
,`acoes` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v__evidencia`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v__evidencia` (
`id` int(11)
,`filial` int(11)
,`obra` int(11)
,`tipo` varchar(55)
,`endereco` varchar(281)
,`extensao` varchar(6)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v__itens_material`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v__itens_material` (
`filial` int(11)
,`equipe` int(11)
,`obra` int(11)
,`descricao` varchar(255)
,`unidade` varchar(5)
,`data` date
,`quantidade` float(8,2)
,`separado` float(8,2)
,`falta` double(19,2)
,`financeiro` float(9,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v__material_obra`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v__material_obra` (
`filial` int(11)
,`obra` int(11)
,`unidade` varchar(5)
,`descricao` varchar(255)
,`quantidade` float(8,2)
,`estoque` double(19,2)
,`comprometido` double(19,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v__programacao`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v__programacao` (
`id` int(11)
,`filial` int(11)
,`equipe` int(11)
,`data` date
,`tipo` varchar(25)
,`comentario_retorno` text
,`turno` varchar(10)
,`custo` float(8,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v__status_separacao_mat`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v__status_separacao_mat` (
`filial` int(11)
,`obra` int(11)
,`equipe` int(11)
,`data` date
,`status` varchar(25)
);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alojamento`
--
ALTER TABLE `alojamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `alojamento_despesa`
--
ALTER TABLE `alojamento_despesa`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `atividade`
--
ALTER TABLE `atividade`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `calc_distancia`
--
ALTER TABLE `calc_distancia`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `calendario`
--
ALTER TABLE `calendario`
  ADD PRIMARY KEY (`data`);

--
-- Índices de tabela `centro_custo`
--
ALTER TABLE `centro_custo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cidade`
--
ALTER TABLE `cidade`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `combustivel_saldo`
--
ALTER TABLE `combustivel_saldo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `combustivel_solicitacao`
--
ALTER TABLE `combustivel_solicitacao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_disparo`
--
ALTER TABLE `email_disparo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_disparo_user`
--
ALTER TABLE `email_disparo_user`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_grupo`
--
ALTER TABLE `email_grupo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_grupo_usuario`
--
ALTER TABLE `email_grupo_usuario`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_rotina`
--
ALTER TABLE `email_rotina`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `envolvido_grupo`
--
ALTER TABLE `envolvido_grupo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `envolvido_grupo_item`
--
ALTER TABLE `envolvido_grupo_item`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `equipe`
--
ALTER TABLE `equipe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipe_titulo` (`titulo`),
  ADD KEY `idx_equipe_filial` (`filial`),
  ADD KEY `idx_equipe_processo` (`processo`),
  ADD KEY `idx_equipe_atividade` (`atividade`),
  ADD KEY `idx_equipe_coordenador` (`coordenador`),
  ADD KEY `idx_equipe_supervisor` (`supervisor`);

--
-- Índices de tabela `equipe_composicao`
--
ALTER TABLE `equipe_composicao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `equipe_escala`
--
ALTER TABLE `equipe_escala`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipe_escala_data` (`data`),
  ADD KEY `idx_equipe_escala_equipe` (`equipe`);

--
-- Índices de tabela `equipe_grupo`
--
ALTER TABLE `equipe_grupo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `equipe_grupo_itens`
--
ALTER TABLE `equipe_grupo_itens`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `estrutura`
--
ALTER TABLE `estrutura`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `estrutura_itens`
--
ALTER TABLE `estrutura_itens`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `faturamento`
--
ALTER TABLE `faturamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fechamento`
--
ALTER TABLE `fechamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fechamento_desenho`
--
ALTER TABLE `fechamento_desenho`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fechamento_medicao`
--
ALTER TABLE `fechamento_medicao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fechamento_status`
--
ALTER TABLE `fechamento_status`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fechamento_status_controle`
--
ALTER TABLE `fechamento_status_controle`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `filial`
--
ALTER TABLE `filial`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `folha`
--
ALTER TABLE `folha`
  ADD PRIMARY KEY (`cpf`);

--
-- Índices de tabela `importacao_apontador`
--
ALTER TABLE `importacao_apontador`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `log_email_conclusao`
--
ALTER TABLE `log_email_conclusao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_material_codigo` (`codigo`),
  ADD KEY `idx_material_filial` (`filial`);

--
-- Índices de tabela `nao_adesao`
--
ALTER TABLE `nao_adesao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `nupost_base`
--
ALTER TABLE `nupost_base`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `nupost_imagem`
--
ALTER TABLE `nupost_imagem`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `nupost_material`
--
ALTER TABLE `nupost_material`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `nupost_movimentacao`
--
ALTER TABLE `nupost_movimentacao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra`
--
ALTER TABLE `obra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obra_filial` (`filial`),
  ADD KEY `idx_obra_codigo` (`codigo`),
  ADD KEY `idx_obra_descricao` (`descricao`);

--
-- Índices de tabela `obra_envolvidos`
--
ALTER TABLE `obra_envolvidos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra_estrutura`
--
ALTER TABLE `obra_estrutura`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra_evidencia`
--
ALTER TABLE `obra_evidencia`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra_material`
--
ALTER TABLE `obra_material`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obra_material_codigo` (`codigo`),
  ADD KEY `idx_obra_material_obra` (`obra`),
  ADD KEY `idx_obra_material_user` (`user`);

--
-- Índices de tabela `obra_mat_devolucao`
--
ALTER TABLE `obra_mat_devolucao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra_mat_itens_separacao`
--
ALTER TABLE `obra_mat_itens_separacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obra_mat_itens_separacao_solicitacao` (`solicitacao`),
  ADD KEY `idx_obra_mat_itens_separacao_obra` (`obra`),
  ADD KEY `idx_obra_mat_itens_separacao_programacao` (`programacao`),
  ADD KEY `idx_obra_mat_itens_separacao_codigo` (`codigo`);

--
-- Índices de tabela `obra_mat_separacao`
--
ALTER TABLE `obra_mat_separacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obra_mat_separacao_solicitacao` (`solicitacao`),
  ADD KEY `idx_obra_mat_separacao_programacao` (`programacao`),
  ADD KEY `idx_obra_mat_separacao_obra` (`obra`),
  ADD KEY `idx_obra_mat_separacao_solicitante` (`solicitante`),
  ADD KEY `idx_obra_mat_separacao_aprovador` (`aprovador`);

--
-- Índices de tabela `obra_prioridade`
--
ALTER TABLE `obra_prioridade`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra_servico`
--
ALTER TABLE `obra_servico`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obra_situacao`
--
ALTER TABLE `obra_situacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_obra_situacao_obra` (`obra`);

--
-- Índices de tabela `obra_tipo`
--
ALTER TABLE `obra_tipo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `powerbi`
--
ALTER TABLE `powerbi`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `preapr`
--
ALTER TABLE `preapr`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `preapr_itens`
--
ALTER TABLE `preapr_itens`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `preapr_registros`
--
ALTER TABLE `preapr_registros`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `processo`
--
ALTER TABLE `processo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `programacao`
--
ALTER TABLE `programacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_programacao_equipe` (`equipe`),
  ADD KEY `idx_programacao_obra` (`obra`),
  ADD KEY `idx_programacao_data` (`data`);

--
-- Índices de tabela `programacao_estrutura`
--
ALTER TABLE `programacao_estrutura`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `programacao_item_servico`
--
ALTER TABLE `programacao_item_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_programacao_id` (`programacao_id`),
  ADD KEY `fk_servico_id` (`servico_id`);

--
-- Índices de tabela `programacao_servico`
--
ALTER TABLE `programacao_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_programacao_servico_data` (`data`),
  ADD KEY `idx_programacao_servico_equipe` (`equipe`),
  ADD KEY `idx_programacao_servico_obra` (`obra`),
  ADD KEY `idx_programacao_servico_servico` (`servico`);

--
-- Índices de tabela `saldo_material`
--
ALTER TABLE `saldo_material`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `servico`
--
ALTER TABLE `servico`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `status_cancelamento`
--
ALTER TABLE `status_cancelamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `templatepi`
--
ALTER TABLE `templatepi`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuario_filial`
--
ALTER TABLE `usuario_filial`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuario_grupo`
--
ALTER TABLE `usuario_grupo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `utd`
--
ALTER TABLE `utd`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `valor_servico`
--
ALTER TABLE `valor_servico`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `veiculo`
--
ALTER TABLE `veiculo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `viabilidade`
--
ALTER TABLE `viabilidade`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alojamento`
--
ALTER TABLE `alojamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alojamento_despesa`
--
ALTER TABLE `alojamento_despesa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `atividade`
--
ALTER TABLE `atividade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `calc_distancia`
--
ALTER TABLE `calc_distancia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cidade`
--
ALTER TABLE `cidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `combustivel_saldo`
--
ALTER TABLE `combustivel_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `combustivel_solicitacao`
--
ALTER TABLE `combustivel_solicitacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_disparo`
--
ALTER TABLE `email_disparo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_disparo_user`
--
ALTER TABLE `email_disparo_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_grupo`
--
ALTER TABLE `email_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_grupo_usuario`
--
ALTER TABLE `email_grupo_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `email_rotina`
--
ALTER TABLE `email_rotina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `envolvido_grupo`
--
ALTER TABLE `envolvido_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `envolvido_grupo_item`
--
ALTER TABLE `envolvido_grupo_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe`
--
ALTER TABLE `equipe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe_composicao`
--
ALTER TABLE `equipe_composicao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe_escala`
--
ALTER TABLE `equipe_escala`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe_grupo`
--
ALTER TABLE `equipe_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe_grupo_itens`
--
ALTER TABLE `equipe_grupo_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estrutura`
--
ALTER TABLE `estrutura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estrutura_itens`
--
ALTER TABLE `estrutura_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `faturamento`
--
ALTER TABLE `faturamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamento`
--
ALTER TABLE `fechamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamento_desenho`
--
ALTER TABLE `fechamento_desenho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamento_medicao`
--
ALTER TABLE `fechamento_medicao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamento_status`
--
ALTER TABLE `fechamento_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `fechamento_status_controle`
--
ALTER TABLE `fechamento_status_controle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `filial`
--
ALTER TABLE `filial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `importacao_apontador`
--
ALTER TABLE `importacao_apontador`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_email_conclusao`
--
ALTER TABLE `log_email_conclusao`
  MODIFY `id` int(7) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `material`
--
ALTER TABLE `material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `nao_adesao`
--
ALTER TABLE `nao_adesao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `nupost_base`
--
ALTER TABLE `nupost_base`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `nupost_imagem`
--
ALTER TABLE `nupost_imagem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `nupost_material`
--
ALTER TABLE `nupost_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra`
--
ALTER TABLE `obra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_envolvidos`
--
ALTER TABLE `obra_envolvidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_estrutura`
--
ALTER TABLE `obra_estrutura`
  MODIFY `id` int(7) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_evidencia`
--
ALTER TABLE `obra_evidencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_material`
--
ALTER TABLE `obra_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_mat_devolucao`
--
ALTER TABLE `obra_mat_devolucao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_mat_itens_separacao`
--
ALTER TABLE `obra_mat_itens_separacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_mat_separacao`
--
ALTER TABLE `obra_mat_separacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_prioridade`
--
ALTER TABLE `obra_prioridade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_servico`
--
ALTER TABLE `obra_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_situacao`
--
ALTER TABLE `obra_situacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obra_tipo`
--
ALTER TABLE `obra_tipo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `powerbi`
--
ALTER TABLE `powerbi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `preapr`
--
ALTER TABLE `preapr`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `preapr_registros`
--
ALTER TABLE `preapr_registros`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `processo`
--
ALTER TABLE `processo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `programacao`
--
ALTER TABLE `programacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `programacao_estrutura`
--
ALTER TABLE `programacao_estrutura`
  MODIFY `id` int(7) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `programacao_item_servico`
--
ALTER TABLE `programacao_item_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `programacao_servico`
--
ALTER TABLE `programacao_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `saldo_material`
--
ALTER TABLE `saldo_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `servico`
--
ALTER TABLE `servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `status_cancelamento`
--
ALTER TABLE `status_cancelamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `templatepi`
--
ALTER TABLE `templatepi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario_filial`
--
ALTER TABLE `usuario_filial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario_grupo`
--
ALTER TABLE `usuario_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `utd`
--
ALTER TABLE `utd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `valor_servico`
--
ALTER TABLE `valor_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `veiculo`
--
ALTER TABLE `veiculo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `viabilidade`
--
ALTER TABLE `viabilidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Estrutura para view `v_analise1`
--
DROP TABLE IF EXISTS `v_analise1`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_analise1`  AS SELECT `calendario`.`data` AS `data`, `equipe`.`titulo` AS `equipe`, `supervisor`.`nome` AS `supervisor`, coalesce((select `es`.`meta` from `equipe_escala` `es` where `es`.`equipe` = `equipe`.`id` and `es`.`data` = `calendario`.`data`),0) AS `meta`, coalesce((select sum(`ser`.`quantidade` * `ser`.`valor`) from `programacao_servico` `ser` where `ser`.`equipe` = `equipe`.`id` and `ser`.`data` = `calendario`.`data` and (`ser`.`status` = 'CONCLUIDO' or `ser`.`status` = 'EXECUTADO')),0) AS `produzido` FROM ((`calendario` join `equipe`) join `folha` `supervisor` on(`equipe`.`supervisor` = `supervisor`.`cpf`)) WHERE month(`calendario`.`data`) = month(current_timestamp()) AND year(`calendario`.`data`) = year(current_timestamp()) ORDER BY `calendario`.`data` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_bloqueio_evidencia`
--
DROP TABLE IF EXISTS `v_bloqueio_evidencia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_bloqueio_evidencia`  AS SELECT `programacao_servico`.`obra` AS `obra`, `programacao_servico`.`user_lancamento` AS `user_lancamento`, `programacao_servico`.`servico` AS `servico`, count(distinct `programacao_servico`.`data`) AS `aguard_evidencia`, (select count(1) from (`obra_evidencia` join `programacao_servico` `p` on(`p`.`id` = `obra_evidencia`.`servico`)) where `obra_evidencia`.`tipo` = 'EVIDÊNCIA SERVIÇO' and `p`.`servico` = `programacao_servico`.`servico` and `p`.`obra` = `programacao_servico`.`obra`) AS `evidencias` FROM (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) WHERE `servico`.`filial` = 3350 AND `programacao_servico`.`log_lancamento` > '2023-01-12' AND `servico`.`evidencia` = 'SIM' AND `programacao_servico`.`status` = 'EXECUTADO' AND `programacao_servico`.`obra` > 0 GROUP BY `programacao_servico`.`obra`, `programacao_servico`.`user_lancamento`, `programacao_servico`.`servico` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_calendario_set`
--
DROP TABLE IF EXISTS `v_calendario_set`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_calendario_set`  AS SELECT `calendario`.`data` AS `data`, `calendario`.`util_falta` AS `util_falta`, `calendario`.`util_prod` AS `util_prod`, `calendario`.`dia` AS `dia`, `calendario`.`mes` AS `mes`, `calendario`.`ano` AS `ano`, `calendario`.`dia_semana` AS `dia_semana`, `calendario`.`dia_semanat2` AS `dia_semanat2`, `calendario`.`dia_semanat3` AS `dia_semanat3`, `calendario`.`mes_desc` AS `mes_desc`, `calendario`.`mes_desct2` AS `mes_desct2` FROM `calendario` WHERE `calendario`.`data` >= '2022-01-01' AND `calendario`.`data` <= '2023-12-31' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_coordenador`
--
DROP TABLE IF EXISTS `v_coordenador`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_coordenador`  AS SELECT `equipe`.`coordenador` AS `coordenador`, `folha`.`nome` AS `nome`, `equipe`.`filial` AS `filial` FROM (`equipe` join `folha` on(`folha`.`cpf` = `equipe`.`coordenador`)) GROUP BY `equipe`.`coordenador`, `equipe`.`filial` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_data_serv`
--
DROP TABLE IF EXISTS `v_data_serv`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_data_serv`  AS SELECT `obra`.`id` AS `id_obra`, (select max(`programacao_servico`.`data_execucao`) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) AS `ultimo_servico`, (select min(`programacao_servico`.`data_execucao`) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) AS `primeiro_servico` FROM `obra` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_dias_projecao`
--
DROP TABLE IF EXISTS `v_dias_projecao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_dias_projecao`  AS SELECT `equipe`.`id` AS `equipe`, (select count(1) from `equipe_escala` `c` where `c`.`data` < cast(current_timestamp() as date) + interval -1 day and month(`c`.`data`) = month(current_timestamp()) and year(`c`.`data`) = year(current_timestamp()) and `c`.`equipe` = `equipe`.`id`) AS `corridos`, (select count(1) from `equipe_escala` `c` where `c`.`data` >= cast(current_timestamp() as date) + interval -1 day and month(`c`.`data`) = month(current_timestamp()) and year(`c`.`data`) = year(current_timestamp()) and `c`.`equipe` = `equipe`.`id`) AS `faltantes` FROM `equipe` GROUP BY `equipe`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_equipes_ativas`
--
DROP TABLE IF EXISTS `v_equipes_ativas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_equipes_ativas`  AS SELECT `equipe`.`id` AS `id`, `equipe`.`filial` AS `filial`, `equipe`.`titulo` AS `equipe`, `equipe`.`coordenador` AS `coordenador_id`, `equipe`.`supervisor` AS `supervisor_id`, `equipe`.`encarregado` AS `encarregado`, `equipe`.`processo` AS `processo`, `equipe`.`atividade` AS `atividade_id`, `alojamento`.`lat` AS `lat`, `alojamento`.`lon` AS `lon`, `atividade`.`titulo` AS `atividade`, `coordenador`.`nome` AS `coordenador`, `supervisor`.`nome` AS `supervisor` FROM ((((`equipe` left join `alojamento` on(`alojamento`.`id` = `equipe`.`alojamento`)) join `folha` `coordenador` on(`coordenador`.`cpf` = `equipe`.`coordenador`)) join `folha` `supervisor` on(`supervisor`.`cpf` = `equipe`.`supervisor`)) join `atividade` on(`equipe`.`atividade` = `atividade`.`id`)) WHERE `equipe`.`status` = 'ATIVO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_escala_setembro`
--
DROP TABLE IF EXISTS `v_escala_setembro`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_escala_setembro`  AS SELECT `equipe`.`id` AS `id`, `equipe`.`filial` AS `filial`, `equipe`.`id` AS `equipe`, `calendario`.`data` AS `data`, `calendario`.`dia` AS `dia`, `calendario`.`mes` AS `mes`, `calendario`.`ano` AS `ano`, (select coalesce(sum(`e`.`meta`),0) from `equipe_escala` `e` where `e`.`equipe` = `equipe`.`id` and `e`.`data` = `calendario`.`data`) AS `meta` FROM (`calendario` join `equipe`) WHERE `equipe`.`status` = 'ATIVO' AND `calendario`.`data` >= '2022-12-01' AND `calendario`.`data` <= '2023-12-31' ORDER BY `equipe`.`id` ASC, `calendario`.`data` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_fechamento_medicao`
--
DROP TABLE IF EXISTS `v_fechamento_medicao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_fechamento_medicao`  AS SELECT `f`.`id` AS `id`, `f`.`obra` AS `obra`, `f`.`valor` AS `valor`, `f`.`data` AS `data`, `f`.`comentario` AS `comentario`, `f`.`log` AS `log`, `f`.`usuario` AS `usuario`, `obra`.`filial` AS `filial` FROM (`fechamento_medicao` `f` join `obra` on(`obra`.`id` = `f`.`obra`)) WHERE `f`.`data` <> '00-00-0000' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_floriano_diario`
--
DROP TABLE IF EXISTS `v_floriano_diario`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_floriano_diario`  AS SELECT `obra_situacao`.`id` AS `id`, `obra_situacao`.`tipo` AS `tipo`, `obra_situacao`.`situacao` AS `situacao`, `obra_situacao`.`log` AS `log`, cast(`obra_situacao`.`log` as date) AS `dia`, `obra_situacao`.`comentario` AS `comentario`, `usuario`.`nome` AS `nome` FROM (`obra_situacao` join `usuario` on(`usuario`.`id` = `obra_situacao`.`user`)) WHERE `obra_situacao`.`obra` = 34609 GROUP BY cast(`obra_situacao`.`log` as date), `obra_situacao`.`situacao`, `obra_situacao`.`comentario`, `obra_situacao`.`tipo` ORDER BY `obra_situacao`.`id` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_floriano_documentos`
--
DROP TABLE IF EXISTS `v_floriano_documentos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_floriano_documentos`  AS SELECT `obra_evidencia`.`tipo` AS `tipo`, `obra_evidencia`.`comentario` AS `comentario`, concat('http://igob.dinamo.srv.br/',`obra_evidencia`.`endereco`) AS `endereco` FROM `obra_evidencia` WHERE `obra_evidencia`.`obra` = 34609 ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_floriano_estruturas`
--
DROP TABLE IF EXISTS `v_floriano_estruturas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_floriano_estruturas`  AS SELECT DISTINCT `servico`.`grupo` AS `grupo_servico`, coalesce(`locacao`.`quantidade_locacao`,0) AS `quantidade_locacao`, coalesce(`supressao`.`quantidade_supressao`,0) AS `quantidade_supressao`, coalesce(`escavacao`.`quantidade_escavacao`,0) AS `quantidade_escavacao`, coalesce(`dist_estrutura`.`quantidade_dist_estrutura`,0) AS `quantidade_dist_estrutura`, coalesce(`implantacao`.`quantidade_implantacao`,0) AS `quantidade_implantacao`, coalesce(`aterramento`.`quantidade_aterramento`,0) AS `quantidade_aterramento`, coalesce(`lanc_cabo`.`quantidade_lanc_cabo`,0) AS `quantidade_lanc_cabo`, coalesce(`nivelamento`.`quantidade_nivelamento`,0) AS `quantidade_nivelamento`, coalesce(`pintura`.`quantidade_pintura`,0) AS `quantidade_pintura`, coalesce(`comissionamento`.`quantidade_comissionamento`,0) AS `quantidade_comissionamento` FROM ((((((((((`servico` left join (select sum(`programacao_servico`.`quantidade`) AS `quantidade_locacao`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 447 and `programacao_servico`.`status` = 'EXECUTADO' group by `servico`.`grupo`) `locacao` on(`locacao`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) AS `quantidade_supressao`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 448 and `programacao_servico`.`status` = 'EXECUTADO' group by `servico`.`grupo`) `supressao` on(`supressao`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_escavacao`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 449 and `programacao_servico`.`status` = 'EXECUTADO' group by `servico`.`grupo`) `escavacao` on(`escavacao`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_dist_estrutura`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 450 group by `servico`.`grupo`) `dist_estrutura` on(`dist_estrutura`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_implantacao`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 451 group by `servico`.`grupo`) `implantacao` on(`implantacao`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_aterramento`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 452 group by `servico`.`grupo`) `aterramento` on(`aterramento`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_lanc_cabo`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 453 group by `servico`.`grupo`) `lanc_cabo` on(`lanc_cabo`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_nivelamento`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 454 group by `servico`.`grupo`) `nivelamento` on(`nivelamento`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_pintura`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 455 group by `servico`.`grupo`) `pintura` on(`pintura`.`grupo` = `servico`.`grupo`)) left join (select sum(`programacao_servico`.`quantidade`) and `programacao_servico`.`status` = 'EXECUTADO' and `programacao_servico`.`status` = 'EXECUTADO' AS `quantidade_comissionamento`,`servico`.`grupo` AS `grupo` from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id`)) where `programacao_servico`.`equipe` = 456 group by `servico`.`grupo`) `comissionamento` on(`comissionamento`.`grupo` = `servico`.`grupo`)) WHERE `servico`.`filial` = 3270 ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_floriano_programacao`
--
DROP TABLE IF EXISTS `v_floriano_programacao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_floriano_programacao`  AS SELECT `equipe`.`titulo` AS `titulo`, `calendario`.`data` AS `data`, cast(current_timestamp() as date) AS `hoje`, CASE WHEN `calendario`.`data` < cast(current_timestamp() as date) THEN coalesce(`p`.`programado`,0) ELSE 0 END AS `acumulado`, coalesce(`p`.`programado`,0) AS `programado`, coalesce(`e`.`executado`,0) AS `executado`, (select max(`programacao`.`data`) from `programacao` where `programacao`.`obra` = 34609 and `programacao`.`equipe` = `equipe`.`id` and `programacao`.`tipo` in ('PROGRAMAÇÃO','REPROGRAMADO','PROGRAMAÇÃO ATENDIDA','PROGRAMAÇÃO ATENDIDA PARC','PROGRAMAÇÃO NÃO ATENDIDA')) AS `maior_prog`, (select min(`programacao`.`data`) from `programacao` where `programacao`.`obra` = 34609 and `programacao`.`equipe` = `equipe`.`id` and `programacao`.`tipo` in ('PROGRAMAÇÃO','REPROGRAMADO','PROGRAMAÇÃO ATENDIDA','PROGRAMAÇÃO ATENDIDA PARC','PROGRAMAÇÃO NÃO ATENDIDA')) AS `menor_prog` FROM (((`calendario` join `equipe`) left join (select `programacao`.`data` AS `data`,`programacao`.`equipe` AS `equipe`,sum(`programacao`.`postes` + `programacao`.`cavas`) AS `programado` from `programacao` where `programacao`.`tipo` in ('PROGRAMAÇÃO','REPROGRAMADO','PROGRAMAÇÃO ATENDIDA','PROGRAMAÇÃO ATENDIDA PARC','PROGRAMAÇÃO NÃO ATENDIDA') group by `programacao`.`equipe`,`programacao`.`data`) `p` on(`p`.`data` = `calendario`.`data` and `p`.`equipe` = `equipe`.`id`)) left join (select `programacao_servico`.`data` AS `data`,`programacao_servico`.`equipe` AS `equipe`,sum(`programacao_servico`.`quantidade`) AS `executado` from `programacao_servico` where `programacao_servico`.`status` = 'EXECUTADO' group by `programacao_servico`.`equipe`,`programacao_servico`.`data`) `e` on(`e`.`data` = `calendario`.`data` and `e`.`equipe` = `equipe`.`id`)) WHERE `equipe`.`filial` = 3270 AND `calendario`.`data` between '2023-09-01' and '2024-12-31' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_indic_financeiro`
--
DROP TABLE IF EXISTS `v_indic_financeiro`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_indic_financeiro`  AS SELECT `equipe`.`id` AS `equipe`, `equipe`.`filial` AS `filial`, `calendario`.`data` AS `data`, cast(current_timestamp() as date) AS `hoje`, (select coalesce(sum(`programacao`.`financeiro`),0) from `programacao` where `programacao`.`equipe` = `equipe`.`id` and `programacao`.`data` = `calendario`.`data` and `programacao`.`tipo` in ('PROGRAMAÇÃO','PROGRAMAÇÃO ATENDIDA','REPROGRAMADO')) AS `programado`, (select coalesce(sum(`equipe_escala`.`meta`),0) from `equipe_escala` where `equipe_escala`.`data` = `calendario`.`data` and `equipe_escala`.`equipe` = `equipe`.`id`) AS `meta`, (select coalesce(sum(`programacao_servico`.`valor` * `programacao_servico`.`fatorcorrecao` * `programacao_servico`.`quantidade`),0) from `programacao_servico` where `programacao_servico`.`equipe` = `equipe`.`id` and `programacao_servico`.`data` = `calendario`.`data` and `programacao_servico`.`status` in ('EXECUTADO','CONFIRMADO')) AS `produzido`, (select coalesce(sum(case when `hh`.`turno` = 'DIA TODO' then 8 else 4 end),0) from `programacao` `hh` where `hh`.`equipe` = `equipe`.`id` and `hh`.`data` = `calendario`.`data` and `hh`.`tipo` not in ('PROGRAMAÇÃO CANCELADA','PARA REPROGRAMAÇÃO')) AS `homem_hora_prog`, (select coalesce(sum(`programacao`.`obra`),0) from `programacao` where `programacao`.`equipe` = `equipe`.`id` and `programacao`.`data` = `calendario`.`data` order by `programacao`.`id` desc limit 1) AS `obra_programada`, (select `v_obras_fechamento`.`viabilidade` from `v_obras_fechamento` where `v_obras_fechamento`.`obra_id` = `obra_programada`) AS `viabilidade`, (select `v_obras_fechamento`.`data_finalizacao` from `v_obras_fechamento` where `v_obras_fechamento`.`obra_id` = `obra_programada`) AS `data_finalizacao` FROM (`calendario` join `equipe` on(`equipe`.`status` = 'ATIVO')) WHERE year(`calendario`.`data`) >= 2023 AND month(`calendario`.`data`) >= month(current_timestamp() + interval -1 month) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_indic_retorno_prazo`
--
DROP TABLE IF EXISTS `v_indic_retorno_prazo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_indic_retorno_prazo`  AS SELECT `mat`.`solicitacao` AS `solicitacao`, `mat`.`programacao` AS `programacao`, `programacao`.`equipe` AS `equipe`, `equipe`.`filial` AS `filial`, `mat`.`status` AS `status`, cast(`mat`.`log_aprovacao` as date) AS `data_retorno`, cast(`mat`.`log` as date) AS `data_solicitacao`, to_days(cast(`mat`.`log_aprovacao` as date)) - to_days(cast(`mat`.`log` as date)) AS `dias_dif` FROM ((`obra_mat_separacao` `mat` join `programacao` on(`programacao`.`id` = `mat`.`programacao`)) join `equipe` on(`programacao`.`equipe` = `equipe`.`id`)) WHERE `programacao`.`data` >= '2022-05-01' AND `mat`.`status` <> 'SOLICITADO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_indic_solicit_mat`
--
DROP TABLE IF EXISTS `v_indic_solicit_mat`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_indic_solicit_mat`  AS SELECT `mat`.`solicitacao` AS `solicitacao`, `mat`.`programacao` AS `programacao`, `programacao`.`equipe` AS `equipe`, `equipe`.`filial` AS `filial`, cast(`mat`.`log` as date) AS `data_solicitacao`, `programacao`.`data` AS `data_programacao`, to_days(`programacao`.`data`) - to_days(cast(`mat`.`log` as date)) AS `dias_dif` FROM ((`obra_mat_separacao` `mat` join `programacao` on(`programacao`.`id` = `mat`.`programacao`)) join `equipe` on(`programacao`.`equipe` = `equipe`.`id`)) WHERE `programacao`.`data` >= '2022-05-01' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_looker_equipesMeta`
--
DROP TABLE IF EXISTS `v_looker_equipesMeta`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_looker_equipesMeta`  AS SELECT `equipe`.`titulo` AS `titulo`, `atividade`.`titulo` AS `atividade`, `processo`.`titulo` AS `processo`, `atividade`.`meta` AS `meta`, `filial`.`titulo` AS `filial` FROM (((`equipe` join `atividade` on(`atividade`.`id` = `equipe`.`atividade`)) join `processo` on(`processo`.`id` = `equipe`.`processo`)) join `filial` on(`equipe`.`filial` = `filial`.`id`)) WHERE `equipe`.`status` = 'ATIVO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_looker_fechamentoEmedicao`
--
DROP TABLE IF EXISTS `v_looker_fechamentoEmedicao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_looker_fechamentoEmedicao`  AS SELECT count(1) AS `quantidade`, '2001-01-01' AS `data`, `vof`.`filial` AS `filial`, `vof`.`tipo` AS `tipo`, 'EM ANÁLISE' AS `situacao`, sum(`vof`.`executado`) AS `valor_executado`, 0 AS `medido` FROM `v_obras_fechamento` AS `vof` WHERE `vof`.`data_entrada` >= '2023-01-01' AND `vof`.`situacao` = 'CONCLUIDA' AND (`vof`.`valor_medido` is null OR `vof`.`valor_medido` = 0) GROUP BY `vof`.`filial`, `vof`.`tipo`union select count(1) AS `quantidade`,`fechamento_medicao`.`data` AS `data`,`obra`.`filial` AS `filial`,`obra`.`tipo` AS `tipo`,'MEDIÇÃO' AS `situacao`,0 AS `executado`,sum(`fechamento_medicao`.`valor`) AS `medido` from (`fechamento_medicao` join `obra` on(`obra`.`id` = `fechamento_medicao`.`obra`)) where `fechamento_medicao`.`data` >= '2023-01-01' group by `obra`.`filial`,`obra`.`tipo`,`fechamento_medicao`.`data`  ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_looker_obras`
--
DROP TABLE IF EXISTS `v_looker_obras`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_looker_obras`  AS SELECT `obra`.`id` AS `id`, `obra`.`filial` AS `filial`, `filial`.`titulo` AS `filial_titulo`, `obra`.`codigo` AS `codigo`, `obra`.`descricao` AS `descricao`, `obra`.`municipio` AS `municipio`, `obra`.`utd` AS `utd`, `obra`.`tipo` AS `tipo`, `obra`.`data_entrada` AS `data_entrada`, `obra`.`data_finalizacao` AS `data_finalizacao`, `obra`.`data_finalizacao`- cast(current_timestamp() as date) AS `dias_vencimento`, `obra`.`status` AS `status`, `obra`.`situacao` AS `situacao`, `obra`.`situacao_fechamento` AS `situacao_fechamento`, `usuario`.`nome` AS `responsavel`, (select count(1) from `viabilidade` where `viabilidade`.`obra` = `obra`.`id`) AS `viabilidade`, `obra`.`valor_servico` AS `valor_orcado`, (select coalesce(sum(`programacao_servico`.`quantidade` * `programacao_servico`.`valor`),0) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id` and `programacao_servico`.`status` = 'EXECUTADO') AS `valor_executado`, coalesce(`m`.`medicao`,0) AS `medicao`, `obra`.`lat` AS `lat`, `obra`.`lon` AS `lon`, sum(`obra`.`poste_distribuicao` + `obra`.`poste_transmissao`) AS `postes_orc`, (select coalesce(sum(`programacao_servico`.`quantidade`),0) from (`programacao_servico` join `servico` on(`programacao_servico`.`servico` = `servico`.`id` and `servico`.`grupo` = 'INSTALAR POSTE')) where `programacao_servico`.`obra` = `obra`.`id` and `programacao_servico`.`status` = 'EXECUTADO') AS `postes_instalados`, (select max(`programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id`) AS `primeira_prog`, (select max(`programacao`.`data`) from `programacao` where `programacao`.`obra` = `obra`.`id`) AS `ultima_prog`, (select max(`programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id` and `programacao_servico`.`status` = 'EXECUTADO') AS `ultimo_lanc`, `obra`.`nota` AS `nota`, `obra`.`pep` AS `pep` FROM (((`obra` left join `usuario` on(`obra`.`responsavel` = `usuario`.`id`)) left join `filial` on(`filial`.`id` = `obra`.`filial`)) left join (select `fechamento_medicao`.`obra` AS `obra`,sum(`fechamento_medicao`.`valor`) AS `medicao` from `fechamento_medicao` group by `fechamento_medicao`.`obra`) `m` on(`m`.`obra` = `obra`.`id`)) WHERE `obra`.`status` = 'ATIVO' AND `obra`.`data_entrada` >= '2023-01-01' AND `obra`.`situacao` <> 'OCULTADA' AND (`obra`.`tipo` <> 'CANCELADA' OR `obra`.`tipo` <> 'DEVOLVIDA') GROUP BY `obra`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_looker_programacaoEquipe`
--
DROP TABLE IF EXISTS `v_looker_programacaoEquipe`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_looker_programacaoEquipe`  AS SELECT `calendario`.`data` AS `data`, `equipe`.`filial` AS `filial`, `equipe`.`titulo` AS `titulo`, coalesce(`equipe_escala`.`meta`,0) AS `meta`, `atividade`.`meta` AS `meta_global`, `atividade`.`titulo` AS `atividade`, `processo`.`titulo` AS `processo`, coalesce(`p`.`programacoes`,0) AS `programacoes`, coalesce(`p`.`postes`,0) AS `postes`, coalesce(`p`.`financeiro`,0) AS `financeiro_programado`, coalesce(`s`.`poste_instalado`,0) AS `poste_instalado` FROM ((((((`calendario` join `equipe`) join `processo` on(`equipe`.`processo` = `processo`.`id`)) join `atividade` on(`equipe`.`atividade` = `atividade`.`id`)) left join `equipe_escala` on(`equipe_escala`.`data` = `calendario`.`data` and `equipe_escala`.`equipe` = `equipe`.`id`)) left join (select `programacao`.`equipe` AS `equipe`,`programacao`.`data` AS `data`,count(1) AS `programacoes`,sum(`programacao`.`financeiro`) AS `financeiro`,sum(`programacao`.`postes`) AS `postes`,sum(`programacao`.`cavas`) AS `cavas`,sum(`programacao`.`equipamentos`) AS `equipamentos`,sum(`programacao`.`vaos_cabo`) AS `vaos_cabo`,sum(`programacao`.`clientes`) AS `clientes`,sum(`programacao`.`base`) AS `base`,sum(`programacao`.`estruturas`) AS `estruturas` from `programacao` where `programacao`.`tipo` <> 'PROGRAMAÇÃO CANCELADA' group by `programacao`.`equipe`,`programacao`.`data`) `p` on(`p`.`data` = `calendario`.`data` and `p`.`equipe` = `equipe`.`id`)) left join (select `programacao_servico`.`equipe` AS `equipe`,`programacao_servico`.`data` AS `data`,sum(case when `servico`.`grupo` = 'INSTALAR POSTE' then `programacao_servico`.`quantidade` else 0 end) AS `poste_instalado` from (`programacao_servico` join `servico` on(`servico`.`id` = `programacao_servico`.`servico`)) where `programacao_servico`.`status` = 'CONFIRMADO' or `programacao_servico`.`status` = 'EXECUTADO' group by `programacao_servico`.`equipe`,`programacao_servico`.`data`) `s` on(`s`.`equipe` = `equipe`.`id` and `s`.`data` = `calendario`.`data`)) WHERE `calendario`.`data` between current_timestamp() + interval -15 day and current_timestamp() + interval 15 day AND `equipe`.`status` = 'ATIVO' ORDER BY `equipe`.`processo` ASC, `equipe`.`atividade` ASC, `equipe`.`titulo` ASC, `calendario`.`data` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_mapa`
--
DROP TABLE IF EXISTS `v_mapa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_mapa`  AS SELECT `obra`.`id` AS `id_obra`, '' AS `id_equipe`, `obra`.`descricao` AS `descricao`, `obra`.`lat` AS `lat`, `obra`.`lon` AS `lon`, 'OBRA' AS `tipo` FROM `obra`union select '' AS `id_obra`,`equipe`.`id` AS `id_equipe`,`equipe`.`titulo` AS `titulo`,`alojamento`.`lat` AS `lat`,`alojamento`.`lon` AS `lon`,'EQUIPE' AS `tipo` from (`equipe` left join `alojamento` on(`alojamento`.`id` = `equipe`.`alojamento`))  ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_momento`
--
DROP TABLE IF EXISTS `v_momento`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_momento`  AS SELECT current_timestamp() + interval -3 hour AS `atualizacao` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_obras_evidencia`
--
DROP TABLE IF EXISTS `v_obras_evidencia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_obras_evidencia`  AS SELECT `obra`.`id` AS `id`, `obra`.`codigo` AS `codigo`, `obra`.`filial` AS `filial`, `obra`.`descricao` AS `descricao`, `obra`.`municipio` AS `municipio`, `obra`.`tipo` AS `tipo`, `obra`.`situacao` AS `situacao`, `obra`.`situacao_fechamento` AS `situacao_fechamento`, `obra`.`data_entrada` AS `data_entrada`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'EVIDÊNCIA ASBUILT') AS `asbuilt`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'DOCUMENTO') AS `documento`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'OBRA CONCLUIDA') AS `obra_concluida`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'EVIDÊNCIA SERVIÇO') AS `servico`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'EVIDÊNCIA VIABILIDADE') AS `viabilidade`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'LEVANTAMENTO') AS `levantamento`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'BASE CONCRETADA') AS `base`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'CAVA') AS `cava`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'SUCATA') AS `sucata`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'POSTE') AS `poste`, (select count(1) from `obra_evidencia` where `obra_evidencia`.`obra` = `obra`.`id` and `obra_evidencia`.`tipo` = 'TRANSFORMADOR') AS `transformador` FROM `obra` WHERE `obra`.`status` = 'ATIVO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_obras_fechamento`
--
DROP TABLE IF EXISTS `v_obras_fechamento`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_obras_fechamento`  AS SELECT `obra`.`filial` AS `filial`, `obra`.`id` AS `obra_id`, `obra`.`codigo` AS `codigo`, `obra`.`pep` AS `pep`, `obra`.`data_entrada` AS `data_entrada`, timestampdiff(DAY,cast(current_timestamp() as date),`obra`.`data_entrada` + interval 10 day) AS `dias_vencimento_projeto`, `obra`.`data_entrada`+ interval 10 day AS `data_limite_projeto`, `obra`.`data_finalizacao` AS `data_finalizacao`, timestampdiff(DAY,cast(current_timestamp() as date),`obra`.`data_finalizacao`) AS `dias_venciamento`, `obra`.`descricao` AS `descricao`, `obra`.`municipio` AS `municipio`, `obra`.`utd` AS `utd`, `obra`.`tipo` AS `tipo`, `obra`.`prioridade` AS `prioridade`, `obra`.`poste_transmissao`+ `obra`.`poste_distribuicao` AS `postes`, `obra`.`situacao` AS `situacao`, `obra`.`situacao_cliente` AS `situacao_cliente`, `obra`.`situacao_fechamento` AS `situacao_fechamento`, `obra`.`desenho_fechamento` AS `desenho_fechamento`, `obra`.`valor_servico` AS `valor_servico`, `obra`.`observacao` AS `observacao`, `responsavel`.`nome` AS `responsavel`, `resp_fechamento`.`nome` AS `responsavel_fechamento`, `projetista`.`nome` AS `projetista`, `fechamento`.`data_envio_pasta` AS `data_envio_pasta`, `fechamento`.`data_fechamento` AS `data_fechamento`, (select max(`fechamento_medicao`.`data`) from `fechamento_medicao` where `fechamento_medicao`.`obra` = `obra`.`id`) AS `data_postagem`, (select sum(`fechamento_medicao`.`valor`) from `fechamento_medicao` where `fechamento_medicao`.`obra` = `obra`.`id`) AS `valor_postagem`, `fechamento`.`data_solicitacao_termo` AS `data_solicitacao_termo`, `fechamento`.`data_aprovacao_termo` AS `data_aprovacao_termo`, `fechamento`.`pagamento` AS `pagamento`, `fechamento`.`postagem` AS `postagem`, (select sum(`fechamento_medicao`.`valor`) from `fechamento_medicao` where `fechamento_medicao`.`obra` = `obra`.`id`) AS `valor_medido`, `fechamento`.`data_pagamento` AS `data_pagamento`, `fechamento`.`valor_pagamento` AS `valor_pagamento`, `fechamento`.`status_pasta` AS `status_pasta`, `fechamento`.`status_aprovacao` AS `status_aprovacao`, `fechamento`.`status_materiais` AS `status_materiais`, `fechamento`.`material_data_envio` AS `material_data_envio`, `fechamento`.`material_data_tratativa` AS `material_data_tratativa`, (select coalesce(sum(`faturamento`.`valor`),0) from `faturamento` where `faturamento`.`obra` = `obra`.`id`) AS `valor_faturado`, (select sum(`programacao_servico`.`quantidade` * `programacao_servico`.`valor` * `programacao_servico`.`fatorcorrecao`) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) AS `executado`, (select max(`programacao`.`data`) from `programacao` where `programacao`.`obra` = `obra`.`id`) AS `ultima_programacao`, (select min(`programacao`.`data`) from `programacao` where `programacao`.`obra` = `obra`.`id`) AS `primeira_programacao`, (select max(`programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `obra`.`id`) AS `ultimo_lanc`, (select cast(`obra_situacao`.`log` as date) from `obra_situacao` where `obra_situacao`.`obra` = `obra`.`id` order by `obra_situacao`.`id` desc limit 1) AS `ultima_movimentacao`, `obra`.`lat` AS `lat`, `obra`.`lon` AS `lon`, CASE WHEN `v_quant_viabilidade`.`quant_viabiliade` > 0 THEN 'VIABILIZADA' ELSE 'NÃO VIABILIZADA' END AS `viabilidade`, (select max(`programacao`.`data`) from `programacao` where `programacao`.`obra` = `obra`.`id` and `programacao`.`previsao_finalizacao` = 'S') AS `finalizacao` FROM (((((`obra` left join `fechamento` on(`fechamento`.`obra` = `obra`.`id`)) left join `usuario` `responsavel` on(`responsavel`.`id` = `obra`.`responsavel`)) left join `usuario` `resp_fechamento` on(`resp_fechamento`.`id` = `obra`.`responsavel_fechamento`)) left join `usuario` `projetista` on(`projetista`.`id` = `obra`.`projetista`)) left join `v_quant_viabilidade` on(`obra`.`id` = `v_quant_viabilidade`.`id`)) WHERE `obra`.`status` = 'ATIVO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_obra_evolucao`
--
DROP TABLE IF EXISTS `v_obra_evolucao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_obra_evolucao`  AS SELECT `item`.`id` AS `id`, `item`.`filial` AS `filial`, `item`.`codigo` AS `codigo`, `item`.`descricao` AS `descricao`, `item`.`situacao` AS `situacao`, `item`.`asbuilt` AS `asbuilt`, `item`.`responsavel` AS `responsavel`, (select coalesce(sum(`programacao_servico`.`quantidade` * `servico`.`dia`),0) from (`programacao_servico` join `servico` on(`servico`.`id` = `programacao_servico`.`servico`)) where (`programacao_servico`.`status` = 'CONFIRMADO' or `programacao_servico`.`status` = 'EXECUTADO') and `programacao_servico`.`obra` = `item`.`id`) AS `executado`, (select coalesce(sum(`obra_servico`.`quantidade` * `servico`.`dia`),0) from ((`obra_servico` join `obra` on(`obra`.`id` = `obra_servico`.`obra`)) join `servico` on(`obra_servico`.`servico` = `servico`.`codigo` and `servico`.`filial` = `obra`.`filial`)) where `obra_servico`.`obra` = `item`.`id`) AS `orcado`, `item`.`data_entrada` AS `data_entrada`, (select max(`programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `item`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) AS `ultimo_lanc`, (select min(`programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `item`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) AS `primeiro_lanc`, (select count(distinct `programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `item`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) AS `dias_na_obra` FROM `obra` AS `item` WHERE `item`.`status` = 'ATIVO' GROUP BY `item`.`id` ORDER BY (select max(`programacao_servico`.`data`) from `programacao_servico` where `programacao_servico`.`obra` = `item`.`id` and (`programacao_servico`.`status` = 'EXECUTADO' or `programacao_servico`.`status` = 'CONFIRMADO')) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_obra_situacao`
--
DROP TABLE IF EXISTS `v_obra_situacao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_obra_situacao`  AS SELECT `obra`.`id` AS `id`, `obra`.`filial` AS `filial`, `obra_situacao`.`tipo` AS `tipo`, `obra_situacao`.`situacao` AS `situacao`, `obra_situacao`.`comentario` AS `comentario`, `usuario`.`nome` AS `nome`, `obra_situacao`.`log` AS `log` FROM ((`obra_situacao` join `obra` on(`obra_situacao`.`obra` = `obra`.`id`)) join `usuario` on(`obra_situacao`.`user` = `usuario`.`id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_pbi_meta_prog_exec`
--
DROP TABLE IF EXISTS `v_pbi_meta_prog_exec`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_pbi_meta_prog_exec`  AS SELECT `calendario`.`data` AS `data`, `equipe`.`filial` AS `filial`, `equipe`.`encarregado` AS `encarregado`, `equipe`.`titulo` AS `titulo`, `atividade`.`titulo` AS `atividade`, coalesce(`prog`.`programado`,0) AS `programado`, coalesce(`escala`.`meta`,0) AS `meta`, coalesce(`executado`.`executado`,0) AS `executado`, `folha`.`nome` AS `supervisor` FROM ((((((`calendario` join `equipe`) left join (select sum(`programacao`.`financeiro`) AS `programado`,`programacao`.`data` AS `data`,`programacao`.`equipe` AS `equipe` from `programacao` where `programacao`.`tipo` <> 'PROGRAMAÇÃO CANCELADA' group by `programacao`.`data`,`programacao`.`equipe`) `prog` on(`prog`.`data` = `calendario`.`data` and `prog`.`equipe` = `equipe`.`id`)) left join (select sum(`programacao_servico`.`quantidade` * `programacao_servico`.`valor`) AS `executado`,`programacao_servico`.`equipe` AS `equipe`,`programacao_servico`.`data` AS `data` from `programacao_servico` where `programacao_servico`.`status` = 'EXECUTADO' group by `programacao_servico`.`data`,`programacao_servico`.`equipe`) `executado` on(`executado`.`data` = `calendario`.`data` and `executado`.`equipe` = `equipe`.`id`)) left join `equipe_escala` `escala` on(`escala`.`equipe` = `equipe`.`id` and `escala`.`data` = `calendario`.`data`)) join `folha` on(`folha`.`cpf` = `equipe`.`supervisor`)) join `atividade` on(`atividade`.`id` = `equipe`.`atividade`)) WHERE `calendario`.`data` between current_timestamp() - interval 35 day and current_timestamp() + interval 35 day AND `equipe`.`status` = 'ATIVO' GROUP BY `calendario`.`data`, `equipe`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_pbi_programacoes`
--
DROP TABLE IF EXISTS `v_pbi_programacoes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_pbi_programacoes`  AS SELECT `programacao`.`id` AS `id`, `equipe`.`filial` AS `filial`, `programacao`.`data` AS `data`, `programacao`.`desligamento` AS `desligamento`, `programacao`.`si` AS `si`, `programacao`.`si_status` AS `si_status`, `programacao`.`tipo` AS `tipo`, `programacao`.`log_programacao` AS `log_programacao`, `programacao`.`observacao` AS `observacao`, `programacao`.`informacoes` AS `informacoes`, `programacao`.`justificativa_cancelamento` AS `justificativa_cancelamento`, `programacao`.`postes` AS `postes`, `programacao`.`cavas` AS `cavas`, `programacao`.`equipamentos` AS `equipamentos`, `programacao`.`vaos_cabo` AS `vaos_cabo`, `programacao`.`clientes` AS `clientes`, `programacao`.`base` AS `base`, `programacao`.`estruturas` AS `estruturas`, `programacao`.`financeiro` AS `financeiro`, `programacao`.`turno` AS `turno`, `equipe`.`titulo` AS `equipe`, `obra`.`descricao` AS `obra_descricao`, `obra`.`codigo` AS `obra_codigo`, `usuario`.`nome` AS `programador`, `atividade`.`titulo` AS `atividade`, `obra`.`tipo` AS `obra_tipo`, `folha`.`nome` AS `supervisor` FROM (((((`programacao` join `equipe` on(`equipe`.`id` = `programacao`.`equipe`)) join `atividade` on(`atividade`.`id` = `equipe`.`atividade`)) join `obra` on(`programacao`.`obra` = `obra`.`id`)) join `usuario` on(`programacao`.`programador` = `usuario`.`id`)) join `folha` on(`folha`.`cpf` = `equipe`.`supervisor`)) WHERE `programacao`.`data` between current_timestamp() - interval 30 day and current_timestamp() + interval 30 day ORDER BY `programacao`.`id` DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_programado`
--
DROP TABLE IF EXISTS `v_programado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_programado`  AS SELECT `programacao`.`id` AS `id`, `programacao`.`data` AS `data`, `programacao`.`equipe` AS `equipe`, sum(`programacao`.`financeiro`) AS `valor_total` FROM `programacao` WHERE `programacao`.`tipo` in ('PROGRAMAÇÃO','REPROGRAMADO','PROGRAMAÇÃO ATENDIDA','PROGRAMAÇÃO ATENDIDA PARC','PROGRAMAÇÃO NÃO ATENDIDA') AND `programacao`.`data` >= '2022-08-01' GROUP BY `programacao`.`data`, `programacao`.`equipe` ORDER BY `programacao`.`data` ASC, `programacao`.`equipe` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_quant_viabilidade`
--
DROP TABLE IF EXISTS `v_quant_viabilidade`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_quant_viabilidade`  AS SELECT `obra`.`id` AS `id`, (select count(1) from `viabilidade` where `viabilidade`.`obra` = `obra`.`id`) AS `quant_viabiliade` FROM `obra` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_servicos`
--
DROP TABLE IF EXISTS `v_servicos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_servicos`  AS SELECT `pro`.`data_execucao` AS `data`, month(`pro`.`data`) AS `mes`, year(`pro`.`data`) AS `ano`, `equipe`.`filial` AS `filial`, `pro`.`equipe` AS `equipe`, `equipe`.`atividade` AS `atividade`, `equipe`.`processo` AS `processo`, `pro`.`os` AS `os`, `pro`.`si` AS `si`, `pro`.`ocorrencia` AS `ocorrencia`, `obra`.`id` AS `obra_id`, `obra`.`codigo` AS `obra_codigo`, `obra`.`descricao` AS `obra_descricao`, `servico`.`codigo` AS `servico_codigo`, `servico`.`descricao` AS `descricao`, `servico`.`grupo` AS `grupo_servico`, round(`pro`.`quantidade`,1) AS `quantidade`, `pro`.`valor` AS `valor`, `pro`.`fatorcorrecao` AS `fatorcorrecao`, `pro`.`quantidade`* `pro`.`valor` * `pro`.`fatorcorrecao` AS `valor_total`, `equipe`.`coordenador` AS `coordenador`, `equipe`.`supervisor` AS `supervisor`, `pro`.`comentario_exec` AS `comentario_exec` FROM (((`programacao_servico` `pro` left join `obra` on(`obra`.`id` = `pro`.`obra`)) join `servico` on(`servico`.`id` = `pro`.`servico`)) join `equipe` on(`equipe`.`id` = `pro`.`equipe`)) WHERE (`pro`.`status` = 'EXECUTADO' OR `pro`.`status` = 'CONFIRMADO') AND `equipe`.`status` = 'ATIVO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_supervisor`
--
DROP TABLE IF EXISTS `v_supervisor`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_supervisor`  AS SELECT `equipe`.`supervisor` AS `supervisor`, `folha`.`nome` AS `nome`, `equipe`.`filial` AS `filial` FROM (`equipe` join `folha` on(`folha`.`cpf` = `equipe`.`supervisor`)) GROUP BY `equipe`.`supervisor`, `equipe`.`filial` ORDER BY `folha`.`nome` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_usuario_fechamento`
--
DROP TABLE IF EXISTS `v_usuario_fechamento`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_usuario_fechamento`  AS SELECT `usuario`.`id` AS `id`, `usuario`.`nome` AS `nome`, `usuario_filial`.`filial` AS `filial`, `usuario`.`meta_fechamento` AS `meta_fechamento` FROM (`usuario` join `usuario_filial` on(`usuario`.`id` = `usuario_filial`.`usuario`)) GROUP BY `usuario`.`id` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_wpp_resumo_programacao`
--
DROP TABLE IF EXISTS `v_wpp_resumo_programacao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_wpp_resumo_programacao`  AS SELECT `filial`.`id` AS `filial`, `filial`.`titulo` AS `titulo`, `programacao`.`data` AS `data`, sum(`programacao`.`postes`) AS `postes`, sum(`programacao`.`cavas`) AS `cavas`, sum(`programacao`.`equipamentos`) AS `equipamentos`, sum(`programacao`.`vaos_cabo`) AS `vaos_cabo`, sum(`programacao`.`clientes`) AS `clientes`, sum(case when `programacao`.`previsao_finalizacao` = 'S' then 1 else 0 end) AS `previsao_finalizacao` FROM ((`programacao` join `equipe` on(`equipe`.`id` = `programacao`.`equipe`)) join `filial` on(`filial`.`id` = `equipe`.`filial`)) WHERE `programacao`.`tipo` = 'PROGRAMAÇÃO' AND `programacao`.`data` = curdate() + interval 1 day GROUP BY `equipe`.`filial` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_wpp_resumo_programacao_semana`
--
DROP TABLE IF EXISTS `v_wpp_resumo_programacao_semana`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v_wpp_resumo_programacao_semana`  AS SELECT `filial`.`id` AS `filial`, `filial`.`titulo` AS `titulo`, min(`programacao`.`data`) AS `min_data`, max(`programacao`.`data`) AS `max_data`, sum(`programacao`.`postes`) AS `postes`, sum(`programacao`.`cavas`) AS `cavas`, sum(`programacao`.`equipamentos`) AS `equipamentos`, sum(`programacao`.`vaos_cabo`) AS `vaos_cabo`, sum(`programacao`.`clientes`) AS `clientes`, sum(case when `programacao`.`previsao_finalizacao` = 'S' then 1 else 0 end) AS `previsao_finalizacao` FROM ((`programacao` join `equipe` on(`equipe`.`id` = `programacao`.`equipe`)) join `filial` on(`filial`.`id` = `equipe`.`filial`)) WHERE `programacao`.`tipo` = 'PROGRAMAÇÃO' AND `programacao`.`data` between curdate() + interval 1 day and curdate() + interval 7 day GROUP BY `equipe`.`filial` ;

-- --------------------------------------------------------

--
-- Estrutura para view `v__equipe_na_obra`
--
DROP TABLE IF EXISTS `v__equipe_na_obra`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v__equipe_na_obra`  AS SELECT `programacao_servico`.`id` AS `id`, `equipe`.`filial` AS `filial`, `programacao_servico`.`equipe` AS `equipe`, `programacao_servico`.`data` AS `data`, `programacao_servico`.`obra` AS `obra`, (select `programacao`.`turno` from `programacao` where `programacao`.`data` = `programacao_servico`.`data` and `programacao`.`equipe` = `equipe`.`id` and `programacao`.`obra` = `programacao_servico`.`obra` limit 1) AS `turno`, (select `equipe_escala`.`meta` from `equipe_escala` where `equipe_escala`.`equipe` = `equipe`.`id` and `equipe_escala`.`data` = `programacao_servico`.`data`) AS `meta`, (select `equipe_escala`.`meta` from `equipe_escala` where `equipe_escala`.`equipe` = `equipe`.`id` and `equipe_escala`.`data` = `programacao_servico`.`data`) / (select count(1) from `programacao_servico` `p` where `p`.`equipe` = `programacao_servico`.`equipe` and `p`.`data` = `programacao_servico`.`data` and `p`.`status` = 'EXECUTADO' group by `p`.`equipe`,`p`.`data`) AS `meta_obra`, (select count(1) from `programacao_servico` `p` where `p`.`equipe` = `programacao_servico`.`equipe` and `p`.`data` = `programacao_servico`.`data` and `p`.`status` = 'EXECUTADO' group by `p`.`equipe`,`p`.`data`) AS `acoes` FROM (`programacao_servico` join `equipe` on(`programacao_servico`.`equipe` = `equipe`.`id`)) WHERE `programacao_servico`.`obra` > 0 AND `programacao_servico`.`data` > '2023-02-01' AND `programacao_servico`.`status` = 'EXECUTADO' AND `equipe`.`filial` = 3150 GROUP BY `programacao_servico`.`equipe`, `programacao_servico`.`data`, `programacao_servico`.`obra` ORDER BY `programacao_servico`.`data` ASC, `programacao_servico`.`equipe` ASC, `programacao_servico`.`obra` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v__evidencia`
--
DROP TABLE IF EXISTS `v__evidencia`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v__evidencia`  AS SELECT `obra_evidencia`.`obra` AS `id`, `obra`.`filial` AS `filial`, `obra_evidencia`.`obra` AS `obra`, `obra_evidencia`.`tipo` AS `tipo`, concat('http://igob.dinamo.srv.br/',`obra_evidencia`.`endereco`) AS `endereco`, `obra_evidencia`.`extensao` AS `extensao` FROM (`obra_evidencia` join `obra` on(`obra`.`id` = `obra_evidencia`.`obra`)) WHERE `obra_evidencia`.`extensao` in ('jpg','jpeg','png','PNG') ;

-- --------------------------------------------------------

--
-- Estrutura para view `v__itens_material`
--
DROP TABLE IF EXISTS `v__itens_material`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v__itens_material`  AS SELECT `programacao`.`filial` AS `filial`, `programacao`.`equipe` AS `equipe`, `programacao`.`obra` AS `obra`, `material`.`descricao` AS `descricao`, `material`.`unidade` AS `unidade`, `programacao`.`data` AS `data`, `obra_mat_itens_separacao`.`quantidade` AS `quantidade`, `obra_mat_itens_separacao`.`separado` AS `separado`, `obra_mat_itens_separacao`.`quantidade`- `obra_mat_itens_separacao`.`separado` AS `falta`, `programacao`.`financeiro` AS `financeiro` FROM (((`obra_mat_itens_separacao` join `programacao` on(`programacao`.`id` = `obra_mat_itens_separacao`.`programacao`)) join `material` on(`material`.`codigo` = `obra_mat_itens_separacao`.`codigo` and `programacao`.`filial` = `material`.`filial`)) join `obra_mat_separacao` on(`obra_mat_separacao`.`solicitacao` = `obra_mat_itens_separacao`.`solicitacao`)) WHERE `programacao`.`data` > '2023-02-01' AND `obra_mat_separacao`.`status` <> 'SOLICITADO' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v__material_obra`
--
DROP TABLE IF EXISTS `v__material_obra`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v__material_obra`  AS SELECT `obra`.`filial` AS `filial`, `obra`.`id` AS `obra`, `material`.`unidade` AS `unidade`, `material`.`descricao` AS `descricao`, `obra_material`.`quantidade` AS `quantidade`, (select coalesce(sum(`saldo_material`.`quantidade`),0) from `saldo_material` where `saldo_material`.`codigo` = `obra_material`.`codigo` and `saldo_material`.`filial` = `obra`.`filial`) AS `estoque`, (select sum(`obra_mat_itens_separacao`.`separado` - `obra_mat_itens_separacao`.`devolvido`) from (((`obra_mat_itens_separacao` join `obra_mat_separacao` on(`obra_mat_separacao`.`solicitacao` = `obra_mat_itens_separacao`.`solicitacao`)) join `programacao` on(`programacao`.`id` = `obra_mat_separacao`.`programacao`)) join `obra` `o` on(`o`.`id` = `programacao`.`obra`)) where `programacao`.`data` >= cast(current_timestamp() as date) and `o`.`filial` = `obra`.`filial` and `obra_mat_itens_separacao`.`codigo` = `obra_material`.`codigo` group by `obra_mat_itens_separacao`.`codigo`) AS `comprometido` FROM (((`obra_material` join `obra` on(`obra`.`id` = `obra_material`.`obra`)) join `material` on(`material`.`codigo` = `obra_material`.`codigo` and `material`.`filial` = `obra`.`filial`)) join `usuario` on(`usuario`.`id` = `obra_material`.`user`)) WHERE `obra_material`.`status` = 'ATIVO' GROUP BY `obra`.`id`, `material`.`id` ORDER BY `obra`.`id` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v__programacao`
--
DROP TABLE IF EXISTS `v__programacao`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v__programacao`  AS SELECT `programacao`.`id` AS `id`, `programacao`.`filial` AS `filial`, `programacao`.`equipe` AS `equipe`, `programacao`.`data` AS `data`, `programacao`.`tipo` AS `tipo`, `programacao`.`comentario_retorno` AS `comentario_retorno`, `programacao`.`turno` AS `turno`, (select `equipe_escala`.`meta` from `equipe_escala` where `equipe_escala`.`equipe` = `programacao`.`equipe` and `equipe_escala`.`data` = `programacao`.`data`) AS `custo` FROM `programacao` WHERE `programacao`.`data` >= '2023-02-01' ;

-- --------------------------------------------------------

--
-- Estrutura para view `v__status_separacao_mat`
--
DROP TABLE IF EXISTS `v__status_separacao_mat`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u251646645_igob`@`%` SQL SECURITY DEFINER VIEW `v__status_separacao_mat`  AS SELECT `programacao`.`filial` AS `filial`, `obra_mat_separacao`.`obra` AS `obra`, `programacao`.`equipe` AS `equipe`, `programacao`.`data` AS `data`, `obra_mat_separacao`.`status` AS `status` FROM (`obra_mat_separacao` join `programacao` on(`programacao`.`id` = `obra_mat_separacao`.`programacao`)) WHERE `programacao`.`data` > '2023-02-01' ;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `programacao_item_servico`
--
ALTER TABLE `programacao_item_servico`
  ADD CONSTRAINT `fk_programacao_item_programacao` FOREIGN KEY (`programacao_id`) REFERENCES `programacao` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_programacao_item_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
