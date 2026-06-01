# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Credenciamento Ponciano** is a Laravel 10 event credentialing system used at physical events. Staff use it to register employee clock-ins/clock-outs via a web interface on phones, tablets, and desktops. The system supports multiple simultaneous events and separates employees by company.

## Common Commands

```bash
# Clear and rebuild Blade template cache (run after any .blade.php change)
php artisan view:cache

# Run database migrations
php artisan migrate

# Import employees/companies/pontos from Excel spreadsheet
php artisan importar:planilha
php artisan importar:planilha --fresh          # truncates pontos, funcionarios, empresas first
php artisan importar:planilha --arquivo=/path/to/file.xlsx --evento-id=1

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

**Runtime**: PHP via XAMPP on macOS. No npm build step — CSS and JS are plain files in `public/css/app.css` and `public/js/app.js`.

## Architecture

### Request Flow

Every authenticated request passes through `SetEventoAtivo` middleware, which reads `session('evento_ativo_id')` and auto-populates it from `Evento::emAndamento()` if empty. All controllers then filter queries using `->when($eventoId, fn($q) => $q->where('evento_id', $eventoId))` so every page scopes data to the active event.

The active event is changed via the topbar dropdown (`trocarEventoGlobal()` JS → `POST /api/evento/sessao` → `EventoController@selecionarNaSessao` → reload).

### Models & Relationships

```
Evento
  └─ has many Ponto (via evento_id — nullable)

Empresa
  ├─ has many Funcionario (empresa_id nullable — import may leave null)
  └─ has many Ponto       (empresa_id nullable)

Funcionario
  ├─ belongs to Empresa (nullable)
  └─ has many Ponto
     └─ pontoHoje() → hasOne filtered to today

Ponto
  ├─ belongs to Funcionario
  ├─ belongs to Empresa  (nullable)
  ├─ belongs to Evento   (nullable)
  └─ belongs to Usuario  (registrado_por, nullable)
```

`empresa_id` and `data` on `pontos`, and `empresa_id` on `funcionarios`, are **nullable** — this was changed in migration `2026_05_29_121925` to support import of employees not yet linked to a company. Always use `$model->empresa?->nome ?? 'Sem empresa'` and `$ponto->data?->format(...)` in views.

### Key Scopes

- `Ponto::hoje()` → `whereDate('data', today())`
- `Ponto::presentes()` → `where('status', 'presente')`
- `Evento::emAndamento()` → `ativo=1` AND `data_inicio <= today <= data_fim`
- `Funcionario::busca($q)` → searches nome, cpf, funcao_cargo

### Controllers → Routes

| Controller | Route prefix | Key notes |
|---|---|---|
| `DashboardController` | `/dashboard` | Indicators filtered by session event + date param |
| `PontoController` | `/ponto`, `/api/ponto` | `registrar()` splits presentes into ≤24h and >24h without exit |
| `RelatorioController` | `/relatorio`, `/api/relatorio` | Per-company stats + live indicator polling |
| `EventoController` | `/eventos`, `/api/evento` | `selecionarNaSessao()` sets session event |
| `FuncionarioController` | `/funcionarios`, `/api/funcionarios` | Photo upload via `processarFoto()`; autocomplete endpoint |
| `EmpresaController` | `/empresas`, `/api/empresas` | Change audit via `HistoricoEmpresa` |

### Frontend Stack

- **CSS**: `public/css/app.css` — single file with CSS variables (`--azul-primario`, `--verde`, `--vermelho`, `--roxo`, `--cinza-*`). Responsive breakpoints: `992px` (tablet) and `576px` (mobile).
- **JS**: `public/js/app.js` — jQuery 3.7.1 + jQuery Mask 1.14.16. Globals: `showToast(msg, type)`, `confirmar({titulo, mensagem, onConfirm})`.
- **Per-page JS**: via `@push('scripts')` / `@stack('scripts')` in `layouts/app.blade.php`.
- **Per-page CSS**: via `@push('styles')` / `@stack('styles')`.

### jQuery Mask Pre-fill Pattern

jQuery Mask 1.14 clears the input `value` during `.mask()` initialization. The fix used throughout this project:

```js
// In @push('scripts'), after mask init:
const digits = String($('#campo-cpf').data('prefill') || '');
if (digits.length === 11) {
    $('#campo-cpf').unmask().val(digits).mask('000.000.000-00');
}
```

In Blade: use `data-prefill="{{ $cpfDigitos }}"` with no `value` attribute on masked inputs.

### Ponto Status Flow

```
(no record) → entrada registered → status='presente' → saida registered → status='finalizado'
```

`calcularHoras()` on `Ponto` computes `horas_trabalhadas` from `data+entrada` and `data+saida` using Carbon diff. Cross-day exits are handled in `PontoController@saida()` by accepting `saida_manual` (HH:MM) + `data_saida` (dd/mm/yyyy).

### Storage / File Uploads

Photos are stored in `storage/app/public/uploads/funcionarios/`. The public URL uses `asset('storage/uploads/...')`. On production (Hostgator), if `php artisan storage:link` can't run via SSH:
- `GET /fix-storage-link` → diagnostic + symlink creation attempt
- `GET /storage/{path}` → PHP fallback serving directly from `storage/app/public/`

### Import Command

`app/Console/Commands/ImportarPlanilha.php` reads a multi-sheet XLSX:
- Sheet "📋 Empresas" → row 4+ → `Empresa::updateOrCreate`
- Sheet "FUNCIONÁRIOS" (several name variants tried) → row 4+ → `Funcionario::create` (when `--fresh`)
- Sheet "⏱️ Entrada e Saída" → row 4+ → `Ponto::updateOrCreate` keyed on `(funcionario_id, data)`

Column layout is hardcoded by index. `limparCpf()` handles Excel float-to-CPF conversion with `str_pad` to 11 digits. `limparTexto()` forces UTF-8 and strips control characters while preserving accents and apostrophes.

### Authentication

Uses a custom `Usuario` model (not Laravel's default `User`). Password field is `senha`. Configured in `config/auth.php` with `App\Models\Usuario` as the provider model.
