<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\PontoController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\SetEventoAtivo;

Route::get('/fix-storage-link', function () {
    $target = storage_path('app/public');              // /home1/dan05629/storage/app/public
    $link   = '/home1/dan05629/public_html/storage';   // raiz web do Hostgator

    // Remove qualquer symlink ou pasta errada
    if (is_link($link)) {
        unlink($link);
    } elseif (is_dir($link)) {
        rmdir($link); // só funciona se estiver vazia
    }

    if (symlink($target, $link)) {
        return [
            'status'  => '✅ Symlink criado com sucesso!',
            'target'  => $target,
            'link'    => $link,
            'testUrl' => 'https://poncianoproductions.com.br/storage/uploads/',
        ];
    }

    return ['status' => '❌ Falhou ao criar symlink'];
});

// ── AUTENTICAÇÃO ─────────────────────────────────────────────────
Route::get('/',       [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/login',  [LoginController::class, 'showLoginForm']);
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

// ── PROTEGIDAS ───────────────────────────────────────────────────
Route::middleware(['auth', SetEventoAtivo::class])->group(function () {

    // Dashboard
    Route::get('/dashboard',       [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/indicadores', [DashboardController::class, 'indicadores'])->name('api.indicadores');

    // Sessão de evento global
    Route::post('/api/evento/sessao', [EventoController::class, 'selecionarNaSessao'])->name('api.evento.sessao');

    // Empresas
    Route::get('/empresas',                   [EmpresaController::class, 'index'])->name('empresas.index');
    Route::get('/empresas/criar',             [EmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/empresas',                  [EmpresaController::class, 'store'])->name('empresas.store');
    Route::get('/empresas/{empresa}',         [EmpresaController::class, 'show'])->name('empresas.show');
    Route::get('/empresas/{empresa}/editar',  [EmpresaController::class, 'edit'])->name('empresas.edit');
    Route::put('/empresas/{empresa}',         [EmpresaController::class, 'update'])->name('empresas.update');
    Route::patch('/empresas/{empresa}/ativo', [EmpresaController::class, 'toggleAtivo'])->name('empresas.toggle-ativo');
    Route::get('/api/empresas/buscar',        [EmpresaController::class, 'buscar'])->name('api.empresas.buscar');

    // Funcionários
    Route::get('/funcionarios',                       [FuncionarioController::class, 'index'])->name('funcionarios.index');
    Route::get('/funcionarios/criar',                 [FuncionarioController::class, 'create'])->name('funcionarios.create');
    Route::post('/funcionarios',                      [FuncionarioController::class, 'store'])->name('funcionarios.store');
    Route::get('/funcionarios/{funcionario}',         [FuncionarioController::class, 'show'])->name('funcionarios.show');
    Route::get('/funcionarios/{funcionario}/editar',  [FuncionarioController::class, 'edit'])->name('funcionarios.edit');
    Route::put('/funcionarios/{funcionario}',         [FuncionarioController::class, 'update'])->name('funcionarios.update');
    Route::patch('/funcionarios/{funcionario}/ativo', [FuncionarioController::class, 'toggleAtivo'])->name('funcionarios.toggle-ativo');
    Route::get('/api/funcionarios/autocomplete',      [FuncionarioController::class, 'autocomplete'])->name('api.funcionarios.autocomplete');
    Route::get('/api/funcionarios/verificar-cpf',     [FuncionarioController::class, 'verificarCpf'])->name('api.funcionarios.verificar-cpf');

    // Ponto
    Route::get('/ponto',                        [PontoController::class, 'index'])->name('ponto.index');
    Route::get('/ponto/registrar',              [PontoController::class, 'registrar'])->name('ponto.registrar');
    Route::post('/api/ponto/entrada',           [PontoController::class, 'entrada'])->name('api.ponto.entrada');
    Route::post('/api/ponto/saida',             [PontoController::class, 'saida'])->name('api.ponto.saida');
    Route::post('/api/ponto/saida-funcionario', [PontoController::class, 'saidaPorFuncionario'])->name('api.ponto.saida-funcionario');
    Route::put('/api/ponto/{ponto}',            [PontoController::class, 'update'])->name('api.ponto.update');
    Route::delete('/api/ponto/{ponto}',         [PontoController::class, 'destroy'])->name('api.ponto.destroy');
    Route::get('/ponto/historico/{funcionario}',[PontoController::class, 'historico'])->name('ponto.historico');

    // Eventos
    Route::get('/eventos',                   [EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/criar',             [EventoController::class, 'create'])->name('eventos.create');
    Route::post('/eventos',                  [EventoController::class, 'store'])->name('eventos.store');
    Route::get('/eventos/{evento}/editar',   [EventoController::class, 'edit'])->name('eventos.edit');
    Route::put('/eventos/{evento}',          [EventoController::class, 'update'])->name('eventos.update');
    Route::patch('/eventos/{evento}/ativo',  [EventoController::class, 'toggleAtivo'])->name('eventos.toggle-ativo');

    // Relatório
    Route::get('/relatorio',                      [RelatorioController::class, 'index'])->name('relatorio.index');
    Route::get('/api/relatorio/indicadores-vivo', [RelatorioController::class, 'indicadoresAoVivo'])->name('api.relatorio.indicadores');

    // Usuários
    Route::get('/usuarios',                   [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/criar',             [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios',                  [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar',  [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{usuario}',         [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::patch('/usuarios/{usuario}/ativo', [UsuarioController::class, 'toggleAtivo'])->name('usuarios.toggle-ativo');

});
