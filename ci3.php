<?php

/**
 * CI3 CLI - CodeIgniter 3 Artisan-like tool
 * Uso: ci3 <comando> [argumentos]
 */

define('CI3_CLI_VERSION', '1.0.0');

// ─────────────────────────────────────────────
// Detecta raiz do projeto CI3
// ─────────────────────────────────────────────
function find_ci3_root(): ?string {
    $dir = getcwd();
    $max = 6;
    while ($max-- > 0) {
        // Detecta pela presença de application/ + system/ ou index.php com CI
        if (
            is_dir($dir . '/application') &&
            (is_dir($dir . '/system') || file_exists($dir . '/index.php'))
        ) {
            $index = @file_get_contents($dir . '/index.php');
            if ($index && (strpos($index, 'CodeIgniter') !== false || strpos($index, 'CI_') !== false || is_dir($dir . '/system'))) {
                return $dir;
            }
        }
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    return null;
}

// ─────────────────────────────────────────────
// Helpers de output
// ─────────────────────────────────────────────
function out(string $msg): void   { echo $msg . PHP_EOL; }
function ok(string $msg): void    { echo "\033[32m✔ $msg\033[0m" . PHP_EOL; }
function err(string $msg): void   { echo "\033[31m✖ $msg\033[0m" . PHP_EOL; }
function warn(string $msg): void  { echo "\033[33m⚠ $msg\033[0m" . PHP_EOL; }
function info(string $msg): void  { echo "\033[36mℹ $msg\033[0m" . PHP_EOL; }
function head(string $msg): void  { echo "\033[1;35m$msg\033[0m" . PHP_EOL; }

// ─────────────────────────────────────────────
// Cria arquivo, criando diretórios se necessário
// ─────────────────────────────────────────────
function write_file(string $path, string $content): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (file_exists($path)) {
        err("Arquivo já existe: $path");
        return false;
    }
    file_put_contents($path, $content);
    ok("Criado: $path");
    return true;
}

// ─────────────────────────────────────────────
// Formata nome: PascalCase, snake_case, etc
// ─────────────────────────────────────────────
function to_pascal(string $name): string {
    return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
}
function to_snake(string $name): string {
    return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
}

// ─────────────────────────────────────────────
// TEMPLATES
// ─────────────────────────────────────────────

function tpl_controller(string $name, bool $with_model = false, bool $rest = false): string {
    $class = to_pascal($name);
    $model_line = $with_model
        ? "\n\t\tpublic function __construct() {\n\t\t\tparent::__construct();\n\t\t\t\$this->load->model('{$class}_model');\n\t\t}\n"
        : '';

    if ($rest) {
        return <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class {$class} extends CI_Controller {
{$model_line}
\tpublic function index()
\t{
\t\t\$this->output
\t\t\t->set_content_type('application/json')
\t\t\t->set_output(json_encode(['status' => 'ok']));
\t}

\tpublic function show(\$id)
\t{
\t\t\$this->output
\t\t\t->set_content_type('application/json')
\t\t\t->set_output(json_encode(['id' => \$id]));
\t}

\tpublic function store()
\t{
\t\t\$data = json_decode(file_get_contents('php://input'), true);
\t\t\$this->output
\t\t\t->set_content_type('application/json')
\t\t\t->set_output(json_encode(['status' => 'created', 'data' => \$data]));
\t}

\tpublic function update(\$id)
\t{
\t\t\$data = json_decode(file_get_contents('php://input'), true);
\t\t\$this->output
\t\t\t->set_content_type('application/json')
\t\t\t->set_output(json_encode(['status' => 'updated', 'id' => \$id]));
\t}

\tpublic function destroy(\$id)
\t{
\t\t\$this->output
\t\t\t->set_content_type('application/json')
\t\t\t->set_output(json_encode(['status' => 'deleted', 'id' => \$id]));
\t}
}
PHP;
    }

    return <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class {$class} extends CI_Controller {
{$model_line}
\tpublic function index()
\t{
\t\t\$this->load->view('{$name}/index');
\t}
}
PHP;
}

function tpl_model(string $name, ?string $table = null): string {
    $class = to_pascal($name) . '_Model';
    $tbl   = $table ?? to_snake($name) . 's';

    return <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class {$class} extends CI_Model {

\tprotected \$table = '{$tbl}';

\tpublic function __construct()
\t{
\t\tparent::__construct();
\t}

\tpublic function get_all()
\t{
\t\treturn \$this->db->get(\$this->table)->result();
\t}

\tpublic function get_by_id(\$id)
\t{
\t\treturn \$this->db->get_where(\$this->table, ['id' => \$id])->row();
\t}

\tpublic function insert(array \$data)
\t{
\t\t\$this->db->insert(\$this->table, \$data);
\t\treturn \$this->db->insert_id();
\t}

\tpublic function update(\$id, array \$data)
\t{
\t\t\$this->db->where('id', \$id)->update(\$this->table, \$data);
\t\treturn \$this->db->affected_rows();
\t}

\tpublic function delete(\$id)
\t{
\t\t\$this->db->where('id', \$id)->delete(\$this->table);
\t\treturn \$this->db->affected_rows();
\t}
}
PHP;
}

function tpl_view_index(string $name): string {
    $title = ucwords(str_replace(['_', '-'], ' ', $name));
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
</head>
<body>
    <h1>{$title}</h1>
    <p>View: {$name}/index</p>
</body>
</html>
HTML;
}

function tpl_view_form(string $name, string $type): string {
    $title = ucwords(str_replace(['_', '-'], ' ', $name));
    $action = $type === 'create' ? 'Novo' : 'Editar';
    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$action} {$title}</title>
</head>
<body>
    <h1>{$action} {$title}</h1>
    <?php echo form_open(''); ?>
        <!-- campos aqui -->
        <button type="submit">Salvar</button>
    <?php echo form_close(); ?>
</body>
</html>
HTML;
}

function tpl_library(string $name): string {
    $class = to_pascal($name);
    return <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class {$class} {

\tprotected \$CI;

\tpublic function __construct()
\t{
\t\t\$this->CI =& get_instance();
\t}
}
PHP;
}

function tpl_helper(string $name): string {
    $snake = to_snake($name);
    return <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('{$snake}_example')) {
\tfunction {$snake}_example()
\t{
\t\t// lógica aqui
\t}
}
PHP;
}

function tpl_migration(string $name, int $seq): string {
    $class = to_pascal($name);
    $pad   = str_pad($seq, 3, '0', STR_PAD_LEFT);
    return <<<PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_{$class} extends CI_Migration {

\tpublic function up()
\t{
\t\t\$this->dbforge->add_field([
\t\t\t'id' => [
\t\t\t\t'type'           => 'INT',
\t\t\t\t'constraint'     => 11,
\t\t\t\t'unsigned'       => true,
\t\t\t\t'auto_increment' => true,
\t\t\t],
\t\t\t'created_at' => ['type' => 'DATETIME', 'null' => true],
\t\t\t'updated_at' => ['type' => 'DATETIME', 'null' => true],
\t\t]);
\t\t\$this->dbforge->add_key('id', true);
\t\t\$this->dbforge->create_table('example');
\t}

\tpublic function down()
\t{
\t\t\$this->dbforge->drop_table('example');
\t}
}
PHP;
}

// ─────────────────────────────────────────────
// COMANDOS
// ─────────────────────────────────────────────

function cmd_make_controller(array $args, string $root): void {
    $name     = $args[0] ?? null;
    $rest     = in_array('--rest', $args);
    $model    = in_array('--model', $args) || in_array('-m', $args);
    $views    = in_array('--views', $args) || in_array('-v', $args);
    $resource = in_array('--resource', $args) || in_array('-r', $args);

    if (!$name) { err('Informe o nome do controller. Ex: ci3 make:controller Usuario'); return; }

    // Suporta subpastas: Admin/Usuario
    $parts    = explode('/', str_replace('\\', '/', $name));
    $base     = array_pop($parts);
    $subdir   = implode('/', $parts);
    $dir      = $root . '/application/controllers/' . ($subdir ? $subdir . '/' : '');
    $file     = $dir . to_pascal($base) . '.php';

    write_file($file, tpl_controller($base, $model, $rest || $resource));

    if ($model) {
        cmd_make_model([$base], $root);
    }

    if ($views || $resource) {
        $vdir = $root . '/application/views/' . to_snake($base) . '/';
        write_file($vdir . 'index.php',  tpl_view_index($base));
        write_file($vdir . 'create.php', tpl_view_form($base, 'create'));
        write_file($vdir . 'edit.php',   tpl_view_form($base, 'edit'));
    }
}

function cmd_make_model(array $args, string $root): void {
    $name  = $args[0] ?? null;
    $table = null;
    foreach ($args as $a) {
        if (strpos($a, '--table=') === 0) $table = substr($a, 8);
    }
    if (!$name) { err('Informe o nome do model. Ex: ci3 make:model Usuario'); return; }

    $parts  = explode('/', str_replace('\\', '/', $name));
    $base   = array_pop($parts);
    $subdir = implode('/', $parts);
    $dir    = $root . '/application/models/' . ($subdir ? $subdir . '/' : '');
    $file   = $dir . to_pascal($base) . '_model.php';

    write_file($file, tpl_model($base, $table));
}

function cmd_make_view(array $args, string $root): void {
    $name = $args[0] ?? null;
    if (!$name) { err('Informe o nome da view. Ex: ci3 make:view usuario/index'); return; }

    $file = $root . '/application/views/' . $name . '.php';
    $base = basename($name);
    write_file($file, tpl_view_index($base));
}

function cmd_make_library(array $args, string $root): void {
    $name = $args[0] ?? null;
    if (!$name) { err('Informe o nome da library. Ex: ci3 make:library Pdf'); return; }

    $file = $root . '/application/libraries/' . to_pascal($name) . '.php';
    write_file($file, tpl_library($name));
}

function cmd_make_helper(array $args, string $root): void {
    $name = $args[0] ?? null;
    if (!$name) { err('Informe o nome do helper. Ex: ci3 make:helper formatacao'); return; }

    $file = $root . '/application/helpers/' . to_snake($name) . '_helper.php';
    write_file($file, tpl_helper($name));
}

function cmd_make_migration(array $args, string $root): void {
    $name = $args[0] ?? null;
    if (!$name) { err('Informe o nome da migration. Ex: ci3 make:migration CreateUsuariosTable'); return; }

    $migDir = $root . '/application/migrations/';
    if (!is_dir($migDir)) mkdir($migDir, 0755, true);

    // Conta migrations existentes para sequência
    $existing = glob($migDir . '*.php');
    $seq      = count($existing) + 1;
    $pad      = str_pad($seq, 3, '0', STR_PAD_LEFT);
    $file     = $migDir . $pad . '_' . to_snake($name) . '.php';

    write_file($file, tpl_migration($name, $seq));
}

function cmd_make_resource(array $args, string $root): void {
    // Gera Controller + Model + Views de uma vez
    $name = $args[0] ?? null;
    if (!$name) { err('Informe o nome. Ex: ci3 make:resource Produto'); return; }

    out('');
    info("Gerando resource completo: $name");
    out('');
    cmd_make_controller([$name, '--resource', '--model'], $root);
}

function cmd_list_routes(string $root): void {
    $dir = $root . '/application/controllers';
    head("\nControllers encontrados em $dir:");
    out('');

    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iter as $file) {
        if ($file->getExtension() !== 'php') continue;
        $rel  = str_replace($dir . '/', '', $file->getPathname());
        $name = str_replace('.php', '', $rel);
        $url  = strtolower($name);
        out("  \033[33m$url\033[0m  →  application/controllers/$rel");
    }
    out('');
}

function cmd_info(string $root): void {
    $index   = @file_get_contents($root . '/index.php');
    $version = 'desconhecida';
    if (preg_match('/define\s*\(\s*[\'"]CI_VERSION[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $index ?? '', $m)) {
        $version = $m[1];
    }

    head("\n  CodeIgniter 3 CLI v" . CI3_CLI_VERSION);
    out('');
    out("  Raiz do projeto : $root");
    out("  Versão CI       : $version");
    out("  PHP             : " . PHP_VERSION);
    out('');
}

function cmd_help(): void {
    head("\n  CI3 CLI — CodeIgniter 3 Artisan-like tool");
    out('');
    out("  \033[1mUso:\033[0m  ci3 <comando> [nome] [opções]");
    out('');
    out("  \033[1mComandos disponíveis:\033[0m");
    out('');
    out("  \033[33mmake:controller\033[0m  Nome  [--rest] [-m|--model] [-v|--views]");
    out("                   Cria um controller");
    out("                   --rest    Gera métodos index/show/store/update/destroy");
    out("                   -m        Cria o Model junto");
    out("                   -v        Cria as Views (index, create, edit) junto");
    out('');
    out("  \033[33mmake:model\033[0m       Nome  [--table=nome_tabela]");
    out("                   Cria um model com CRUD básico");
    out('');
    out("  \033[33mmake:view\033[0m        nome/subview");
    out("                   Cria uma view (suporta subpastas)");
    out('');
    out("  \033[33mmake:resource\033[0m    Nome");
    out("                   Gera Controller + Model + Views de uma só vez");
    out('');
    out("  \033[33mmake:library\033[0m     Nome");
    out("                   Cria uma library");
    out('');
    out("  \033[33mmake:helper\033[0m      nome");
    out("                   Cria um helper");
    out('');
    out("  \033[33mmake:migration\033[0m   NomeDaMigration");
    out("                   Cria uma migration numerada automaticamente");
    out('');
    out("  \033[33mlist:routes\033[0m");
    out("                   Lista controllers e suas rotas");
    out('');
    out("  \033[33minfo\033[0m");
    out("                   Informações do projeto");
    out('');
    out("  \033[1mExemplos:\033[0m");
    out('');
    out("  ci3 make:controller Produto --rest -m");
    out("  ci3 make:controller Admin/Usuario -v");
    out("  ci3 make:model Produto --table=produtos");
    out("  ci3 make:resource Pedido");
    out("  ci3 make:migration CreateProdutosTable");
    out('');
}

// ─────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────

$command = $argv[1] ?? 'help';
$args    = array_slice($argv, 2);

if (in_array($command, ['help', '--help', '-h'])) {
    cmd_help();
    exit(0);
}

// Localiza raiz do CI3
$root = find_ci3_root();

if (!$root && $command !== 'help') {
    err('Projeto CodeIgniter 3 não encontrado.');
    warn('Execute ci3 dentro da pasta do projeto (que contenha application/ e system/ ou index.php).');
    exit(1);
}

if ($command === 'info') {
    cmd_info($root);
    exit(0);
}

if ($command === 'list:routes') {
    cmd_list_routes($root);
    exit(0);
}

switch ($command) {
    case 'make:controller': cmd_make_controller($args, $root); break;
    case 'make:model':      cmd_make_model($args, $root);      break;
    case 'make:view':       cmd_make_view($args, $root);       break;
    case 'make:resource':   cmd_make_resource($args, $root);   break;
    case 'make:library':    cmd_make_library($args, $root);    break;
    case 'make:helper':     cmd_make_helper($args, $root);     break;
    case 'make:migration':  cmd_make_migration($args, $root);  break;
    default:
        err("Comando desconhecido: $command");
        out('');
        cmd_help();
        exit(1);
}

out('');
