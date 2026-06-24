# CI3 CLI

> Ferramenta de linha de comando para **CodeIgniter 3**, inspirada no `php artisan` do Laravel.  
> Gere controllers, models, views, libraries, helpers e migrations diretamente pelo terminal.

---

## Sumário

- [Requisitos](#requisitos)
- [Instalação](#instalação)
  - [Linux / macOS](#linux--macos)
  - [Windows](#windows)
- [Detecção automática do projeto](#detecção-automática-do-projeto)
- [Comandos](#comandos)
  - [make:controller](#makecontroller)
  - [make:model](#makemodel)
  - [make:view](#makeview)
  - [make:resource](#makeresource)
  - [make:library](#makelibrary)
  - [make:helper](#makehelper)
  - [make:migration](#makemigration)
  - [list:routes](#listroutes)
  - [info](#info)
- [Exemplos práticos](#exemplos-práticos)
- [Estrutura gerada](#estrutura-gerada)
- [Solução de problemas](#solução-de-problemas)

---

## Requisitos

| Requisito | Versão mínima |
|-----------|---------------|
| PHP       | 7.4+          |
| CodeIgniter | 3.x         |

PHP precisa estar disponível no terminal (`php -v` deve funcionar).

---

## Instalação

### Linux / macOS

**1. Baixe o arquivo `ci3`**

**2. Mova para um diretório no PATH e dê permissão de execução:**

```bash
sudo mv ci3 /usr/local/bin/ci3
sudo chmod +x /usr/local/bin/ci3
```

Ou sem sudo, usando `~/.local/bin/`:

```bash
mkdir -p ~/.local/bin
mv ci3 ~/.local/bin/ci3
chmod +x ~/.local/bin/ci3

# Adiciona ao PATH se ainda não estiver
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc && source ~/.bashrc
```

**3. Verifique:**

```bash
ci3 help
```

---

### Windows

São necessários **dois arquivos**: `ci3.bat` e `ci3.php`.

**1. Crie uma pasta para os scripts globais**, por exemplo:

```
C:\tools\ci3\
```

**2. Coloque os dois arquivos nessa pasta:**

```
C:\tools\ci3\ci3.bat
C:\tools\ci3\ci3.php
```

**3. Adicione a pasta ao PATH do sistema:**

- Abra o menu Iniciar e pesquise **"Variáveis de ambiente"**
- Em *Variáveis do sistema* → selecione `Path` → clique em **Editar**
- Clique em **Novo** e adicione: `C:\tools\ci3`
- Clique em **OK** em todas as janelas abertas
- **Feche e reabra o terminal**

**4. Verifique:**

```cmd
ci3 help
```

> **Atenção:** O PHP também precisa estar no PATH. Se usar XAMPP, adicione `C:\xampp\php` ao PATH da mesma forma. Verifique com `php -v`.

---

## Detecção automática do projeto

O CI3 CLI **não precisa ser executado na raiz do projeto**. Ele sobe automaticamente na árvore de diretórios até encontrar uma pasta que contenha `application/` junto com `system/` ou um `index.php` com assinatura do CodeIgniter.

```
/var/www/
└── meu-projeto/          ← raiz detectada aqui
    ├── application/
    ├── system/
    └── index.php

# Você pode estar aqui e funciona:
$ cd /var/www/meu-projeto/application/controllers
$ ci3 make:controller Produto
```

Se o projeto não for encontrado, o erro abaixo é exibido:

```
✖ Projeto CodeIgniter 3 não encontrado.
⚠ Execute ci3 dentro da pasta do projeto (que contenha application/ e system/ ou index.php).
```

---

## Comandos

### `make:controller`

Cria um controller em `application/controllers/`.

```bash
ci3 make:controller <Nome> [opções]
```

| Opção | Descrição |
|-------|-----------|
| `--rest` | Gera métodos REST: `index`, `show`, `store`, `update`, `destroy` |
| `-m` ou `--model` | Cria o Model correspondente junto |
| `-v` ou `--views` | Cria as views `index`, `create` e `edit` junto |
| `-r` ou `--resource` | Equivalente a `--rest` + `--views` |

**Suporta subpastas** usando `/` no nome:

```bash
ci3 make:controller Admin/Usuario
# Cria: application/controllers/Admin/Usuario.php
```

---

### `make:model`

Cria um model em `application/models/` com CRUD básico (`get_all`, `get_by_id`, `insert`, `update`, `delete`).

```bash
ci3 make:model <Nome> [--table=nome_tabela]
```

| Opção | Descrição |
|-------|-----------|
| `--table=nome` | Define o nome da tabela manualmente. Padrão: nome em snake_case + `s` |

**Exemplo:**

```bash
ci3 make:model Produto --table=tbl_produtos
```

---

### `make:view`

Cria uma view em `application/views/`. Suporta subpastas.

```bash
ci3 make:view <caminho/da/view>
```

**Exemplos:**

```bash
ci3 make:view produto/index
ci3 make:view admin/usuario/index
```

---

### `make:resource`

Gera **Controller + Model + Views** de uma só vez. Ideal para começar um CRUD completo.

```bash
ci3 make:resource <Nome>
```

O que é criado:

```
application/
├── controllers/Produto.php       ← com métodos REST
├── models/Produto_model.php      ← com CRUD básico
└── views/produto/
    ├── index.php
    ├── create.php
    └── edit.php
```

---

### `make:library`

Cria uma library em `application/libraries/`.

```bash
ci3 make:library <Nome>
```

A classe gerada já obtém a instância do CI via `get_instance()`.

---

### `make:helper`

Cria um helper em `application/helpers/`.

```bash
ci3 make:helper <nome>
# Cria: application/helpers/nome_helper.php
```

O arquivo gerado usa `function_exists()` para evitar redeclaração, seguindo o padrão do CI3.

---

### `make:migration`

Cria uma migration numerada automaticamente em `application/migrations/`.

```bash
ci3 make:migration <NomeDaMigration>
```

A numeração é calculada com base nas migrations existentes (`001_`, `002_`, etc.).

**Exemplo:**

```bash
ci3 make:migration CreateProdutosTable
# Cria: application/migrations/001_create_produtos_table.php
```

O arquivo gerado inclui os métodos `up()` e `down()` com estrutura básica usando `$this->dbforge`.

---

### `list:routes`

Lista todos os controllers encontrados no projeto com seus caminhos de URL correspondentes.

```bash
ci3 list:routes
```

---

### `info`

Exibe informações do projeto detectado: raiz, versão do CodeIgniter e versão do PHP.

```bash
ci3 info
```

---

## Exemplos práticos

```bash
# Controller básico
ci3 make:controller Produto

# Controller REST com model
ci3 make:controller Produto --rest -m

# Controller com subpasta e views
ci3 make:controller Admin/Usuario -v

# CRUD completo de uma vez
ci3 make:resource Pedido

# Model com tabela customizada
ci3 make:model NfeEmitida --table=nfe_emitidas

# View com subpasta
ci3 make:view nfe/detalhe

# Library
ci3 make:library DanfseGenerator

# Helper
ci3 make:helper formatacao_fiscal

# Migration
ci3 make:migration CreateNfeEmitidaTable

# Ver controllers do projeto
ci3 list:routes

# Info do projeto
ci3 info
```

---

## Estrutura gerada

Um `ci3 make:resource Produto` produz:

```
application/
├── controllers/
│   └── Produto.php
│       └── métodos: index, show, store, update, destroy
│
├── models/
│   └── Produto_model.php
│       └── métodos: get_all, get_by_id, insert, update, delete
│       └── $table = 'produtos'
│
└── views/
    └── produto/
        ├── index.php
        ├── create.php
        └── edit.php
```

Arquivos que **já existem não são sobrescritos** — o CI3 CLI exibe um aviso e pula o arquivo.

---

## Solução de problemas

**`command not found: ci3`**  
O diretório onde o arquivo está não está no PATH. Revise a seção de instalação.

**`php: command not found`**  
O PHP não está no PATH do sistema. Adicione o diretório do executável PHP ao PATH.

**Projeto CI3 não encontrado**  
O script sobe no máximo 6 níveis de diretório. Certifique-se de estar dentro da pasta do projeto e que ela contém `application/` e `system/` (ou `index.php` com assinatura do CodeIgniter).

**Arquivo já existe**  
O CI3 CLI nunca sobrescreve arquivos. Delete o arquivo existente manualmente antes de regerar.

---

## Licença

MIT — use, modifique e distribua à vontade.