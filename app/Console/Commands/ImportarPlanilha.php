<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\Ponto;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ImportarPlanilha extends Command
{
    protected $signature   = 'importar:planilha
                                {--arquivo= : Caminho para o arquivo .xlsx}
                                {--evento-id=1 : ID do evento a vincular nos pontos}';

    protected $description = 'Importa empresas, funcionários e pontos da planilha TOTUS TUUS 2026';

    private array $log = ['empresas' => 0, 'funcionarios' => 0, 'pontos' => 0, 'erros' => []];

    // ──────────────────────────────────────────────────────────────
    public function handle(): int
    {
        $arquivo  = $this->option('arquivo') ?: base_path('storage/importacao/planilha.xlsx');
        $eventoId = (int) $this->option('evento-id');

        if (! file_exists($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");
            $this->line("Coloque o arquivo em: storage/importacao/planilha.xlsx");
            $this->line("Ou use: php artisan importar:planilha --arquivo=/caminho/planilha.xlsx");
            return 1;
        }

        $this->info("📂 Lendo arquivo: {$arquivo}");
        $this->info("🎪 Evento ID: {$eventoId}");
        $spreadsheet = IOFactory::load($arquivo);

        $this->line('');
        $this->importarEmpresas($spreadsheet);
        $this->importarFuncionarios($spreadsheet);
        $this->importarPontos($spreadsheet, $eventoId);

        $this->line('');
        $this->info('════════════════════════════════');
        $this->info('  IMPORTAÇÃO CONCLUÍDA');
        $this->info('════════════════════════════════');
        $this->line("✅ Empresas importadas:     {$this->log['empresas']}");
        $this->line("✅ Funcionários importados: {$this->log['funcionarios']}");
        $this->line("✅ Registros de ponto:      {$this->log['pontos']}");

        if (! empty($this->log['erros'])) {
            $this->line('');
            $this->warn('⚠ Erros/avisos (' . count($this->log['erros']) . '):');
            foreach ($this->log['erros'] as $erro) {
                $this->line("  · {$erro}");
            }
        }

        return 0;
    }

    // ──────────────────────────────────────────────────────────────
    //  EMPRESAS  (Sheet: "📋 Empresas" — headers row 3, data row 4)
    //  Colunas: A(#) | B(EMPRESA) | C(RESPONSÁVEL) | D(TELEFONE) | E(E-MAIL) | F(OBSERVAÇÕES)
    // ──────────────────────────────────────────────────────────────
    private function importarEmpresas($spreadsheet): void
    {
        $this->info('🏢 Importando Empresas...');

        $sheet = $spreadsheet->getSheetByName('📋 Empresas')
              ?? $spreadsheet->getSheet(0);

        $bar = $this->output->createProgressBar();
        $bar->start();

        foreach ($sheet->getRowIterator(4) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cells as $cell) {
                $data[] = $cell->getValue();
            }

            // B = nome da empresa
            $nome = trim((string) ($data[1] ?? ''));
            if (! $nome || in_array($nome, ['-', ''])) {
                continue;
            }

            try {
                Empresa::updateOrCreate(
                    ['nome' => $nome],
                    [
                        'responsavel' => $this->limpar($data[2] ?? ''),
                        'telefone'    => $this->limparTelefone($data[3] ?? ''),
                        'email'       => $this->limparEmail($data[4] ?? ''),
                        'observacoes' => $this->limpar($data[5] ?? ''),
                        'ativo'       => true,
                    ]
                );
                $this->log['empresas']++;
            } catch (\Exception $e) {
                $this->log['erros'][] = "Empresa '{$nome}': " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(' ');
    }

    // ──────────────────────────────────────────────────────────────
    //  FUNCIONÁRIOS  (Sheet: "FUNCIONÁRIOS" — headers row 3, data row 4)
    //  Colunas: A(#) | B(EMPRESA) | C(NOME COMPLETO) | D(FUNÇÃO/CARGO)
    //           E(COORDENADOR?) | F(CPF) | G(TELEFONE) | H(ÁREA DE ACESSO)
    //  Funcionários sem empresa são importados com empresa_id = null
    // ──────────────────────────────────────────────────────────────
    private function importarFuncionarios($spreadsheet): void
    {
        $this->info('👥 Importando Funcionários...');

        // Tenta pelo nome exato, depois variações comuns, depois índice
        $sheet = $spreadsheet->getSheetByName('FUNCIONÁRIOS')
              ?? $spreadsheet->getSheetByName('Funcionários')
              ?? $spreadsheet->getSheetByName('👥 Funcionários')
              ?? $spreadsheet->getSheetByName('FUNCIONARIOS')
              ?? $spreadsheet->getSheet(1);

        $bar = $this->output->createProgressBar();
        $bar->start();

        foreach ($sheet->getRowIterator(4) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);

            $data = [];
            foreach ($cells as $cell) {
                $data[] = $cell->getFormattedValue();
            }

            // C = nome do funcionário  (obrigatório)
            $nome = trim((string) ($data[2] ?? ''));
            if (! $nome) {
                continue;
            }

            // B = nome da empresa  (opcional — pode ser vazio)
            $nomeEmpresa = trim((string) ($data[1] ?? ''));
            $empresa     = null;

            if ($nomeEmpresa) {
                $empresa = Empresa::where('nome', $nomeEmpresa)->first()
                        ?? Empresa::where('nome', 'like', '%' . $nomeEmpresa . '%')->first();

                if (! $empresa) {
                    $this->log['erros'][] = "Empresa não encontrada para '{$nome}': '{$nomeEmpresa}' — importado sem empresa";
                }
            }

            // F = CPF (pode vir como float no Excel)
            $cpf = $this->limparCpf($data[5] ?? '');

            // G = Telefone (pode vir como float)
            $telefone = $this->limparTelefone($data[6] ?? '');

            try {
                Funcionario::updateOrCreate(
                    ['nome' => $nome, 'empresa_id' => $empresa?->id],
                    [
                        'empresa_id'   => $empresa?->id,
                        'funcao_cargo' => $this->limpar($data[3] ?? '') ?: 'Não informado',
                        'coordenador'  => strtoupper(trim((string) ($data[4] ?? ''))) === 'SIM',
                        'cpf'          => $cpf,
                        'telefone'     => $telefone,
                        'area_acesso'  => strtoupper(trim((string) ($data[7] ?? 'TODOS'))) ?: 'TODOS',
                        'ativo'        => true,
                    ]
                );
                $this->log['funcionarios']++;
            } catch (\Exception $e) {
                $this->log['erros'][] = "Funcionário '{$nome}': " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(' ');
    }

    // ──────────────────────────────────────────────────────────────
    //  PONTOS  (Sheet: "⏱️ Entrada e Saída" — headers row 3, data row 4)
    //  Colunas: A(#) | B(NOME) | C(EMPRESA) | D(FUNÇÃO) | E(COORD) |
    //           F(DATA) | G(btn entrada) | H(ENTRADA) | I(btn saída) |
    //           J(SAÍDA) | K(⏳ HORAS) | L(STATUS)
    // ──────────────────────────────────────────────────────────────
    private function importarPontos($spreadsheet, int $eventoId): void
    {
        $this->info('⏱ Importando Registros de Ponto...');

        $sheet = $spreadsheet->getSheetByName('⏱️ Entrada e Saída')
              ?? $spreadsheet->getSheetByName('Entrada e Saída')
              ?? $spreadsheet->getSheet(2);

        $bar = $this->output->createProgressBar();
        $bar->start();

        foreach ($sheet->getRowIterator(4) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);

            $raw  = [];  // getFormattedValue — para campos texto
            $vals = [];  // getValue         — para datas/números brutos

            foreach ($cells as $i => $cell) {
                $raw[]  = $cell->getFormattedValue();
                $vals[] = $cell->getValue();
            }

            // B = NOME, C = EMPRESA
            $nome    = trim((string) ($raw[1] ?? ''));
            $nomeEmp = trim((string) ($raw[2] ?? ''));

            if (! $nome) {
                continue;
            }

            // ── Buscar funcionário ─────────────────────────────────
            $funcionario = null;

            if ($nomeEmp) {
                $empresa = Empresa::where('nome', $nomeEmp)->first()
                        ?? Empresa::where('nome', 'like', '%' . $nomeEmp . '%')->first();

                if ($empresa) {
                    $funcionario = Funcionario::where('empresa_id', $empresa->id)
                                              ->where('nome', $nome)
                                              ->first()
                                  ?? Funcionario::where('empresa_id', $empresa->id)
                                                ->where('nome', 'like', '%' . $nome . '%')
                                                ->first();
                }
            }

            if (! $funcionario) {
                $funcionario = Funcionario::where('nome', $nome)->first()
                            ?? Funcionario::where('nome', 'like', '%' . $nome . '%')->first();
            }

            if (! $funcionario) {
                $this->log['erros'][] = "Funcionário não encontrado no ponto: '{$nome}'";
                $bar->advance();
                continue;
            }

            // ── DATA (coluna F = índice 5) ─────────────────────────
            $dataRaw  = $vals[5] ?? '';
            $dataFmt  = $raw[5]  ?? '';
            $dataPonto = $this->parsarData($dataRaw, $dataFmt);

            if (! $dataPonto) {
                $this->log['erros'][] = "Data inválida para '{$nome}': '{$dataFmt}'";
                $bar->advance();
                continue;
            }

            // ── ENTRADA (coluna H = índice 7) ─────────────────────
            $entrada = $this->parsarHora($raw[7] ?? '', $vals[7] ?? null);

            // ── SAÍDA (coluna J = índice 9) ───────────────────────
            $saida = $this->parsarHora($raw[9] ?? '', $vals[9] ?? null);

            // ── HORAS TRABALHADAS (coluna K = índice 10) ──────────
            $horas = $this->parsarHorasTrabalhadas($raw[10] ?? '', $vals[10] ?? null, $entrada, $saida, $dataPonto);

            // ── STATUS (coluna L = índice 11) ─────────────────────
            $statusRaw = strtolower((string) ($raw[11] ?? ''));
            if (str_contains($statusRaw, 'finaliz') || ($entrada && $saida)) {
                $status = 'finalizado';
            } elseif ($entrada) {
                $status = 'presente';
            } else {
                $status = 'ausente';
            }

            try {
                Ponto::updateOrCreate(
                    ['funcionario_id' => $funcionario->id, 'data' => $dataPonto],
                    [
                        'empresa_id'        => $funcionario->empresa_id,
                        'evento_id'         => $eventoId,
                        'entrada'           => $entrada,
                        'saida'             => $saida,
                        'horas_trabalhadas' => $horas,
                        'status'            => $status,
                    ]
                );
                $this->log['pontos']++;
            } catch (\Exception $e) {
                $this->log['erros'][] = "Ponto '{$nome}' em {$dataPonto}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line(' ');
    }

    // ──────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────

    /** Normaliza data — aceita número serial Excel, datetime obj ou string */
    private function parsarData(mixed $valor, string $formatado): ?string
    {
        // Tenta o valor formatado primeiro (já como texto)
        if ($formatado && ! is_numeric($formatado)) {
            try {
                return Carbon::parse($formatado)->toDateString();
            } catch (\Exception $e) {}
        }

        // Número serial do Excel (dias desde 1900-01-01)
        if (is_numeric($valor) && $valor > 1) {
            try {
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $valor)
                )->toDateString();
            } catch (\Exception $e) {}
        }

        // String direta
        if ($valor) {
            try {
                return Carbon::parse((string) $valor)->toDateString();
            } catch (\Exception $e) {}
        }

        return null;
    }

    /** Normaliza hora — aceita string HH:MM, número serial Excel ou null */
    private function parsarHora(string $formatado, mixed $valor): ?string
    {
        $v = trim($formatado);
        if (! $v || $v === '-') {
            return null;
        }

        // Formato HH:MM ou HH:MM:SS
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $v)) {
            $partes = explode(':', $v);
            return sprintf('%02d:%02d:%02d', $partes[0], $partes[1], $partes[2] ?? 0);
        }

        // Número serial Excel (fração de dia)
        if (is_numeric($valor)) {
            $segundos = (int) round(floatval($valor) * 86400);
            $h = intdiv($segundos, 3600);
            $m = intdiv($segundos % 3600, 60);
            $s = $segundos % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        return null;
    }

    /** Tenta ler horas trabalhadas da planilha; calcula a partir de entrada/saída se não disponível */
    private function parsarHorasTrabalhadas(
        string  $formatado,
        mixed   $valor,
        ?string $entrada,
        ?string $saida,
        string  $dataPonto
    ): ?string {
        $v = trim($formatado);

        // 1. Número serial (fração de dia — duração)
        if (is_numeric($valor) && floatval($valor) > 0 && floatval($valor) < 1) {
            $segundos = (int) round(floatval($valor) * 86400);
            $h = intdiv($segundos, 3600);
            $m = intdiv($segundos % 3600, 60);
            $s = $segundos % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        // 2. String HH:MM ou HH:MM:SS
        if ($v && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $v)) {
            $partes = explode(':', $v);
            return sprintf('%02d:%02d:%02d', $partes[0], $partes[1], $partes[2] ?? 0);
        }

        // 3. Calcular a partir de entrada e saída
        if ($entrada && $saida) {
            try {
                $ini = Carbon::parse("{$dataPonto} {$entrada}");
                $fim = Carbon::parse("{$dataPonto} {$saida}");
                return $ini->diff($fim)->format('%H:%I:%S');
            } catch (\Exception $e) {}
        }

        return null;
    }

    /** Limpa CPF — lida com floats do Excel (sem zeros à esquerda) */
    private function limparCpf(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        // Remove qualquer não-dígito (pontos, traços) e reconstrói
        $numeros = preg_replace('/\D/', '', (string) $valor);

        // Reconstrói a partir de float (Excel perde zeros à esquerda)
        if (! $numeros && is_numeric($valor)) {
            $numeros = (string) (int) floatval($valor);
        }

        if (! $numeros) {
            return null;
        }

        // Padeia para 11 dígitos se necessário (zeros à esquerda perdidos)
        if (strlen($numeros) < 11) {
            $numeros = str_pad($numeros, 11, '0', STR_PAD_LEFT);
        }

        return strlen($numeros) === 11 ? $numeros : null;
    }

    /** Limpa telefone — lida com floats do Excel */
    private function limparTelefone(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $str = trim((string) $valor);

        // Se vier como float do Excel, converte para inteiro string
        if (is_numeric($valor)) {
            $str = (string) (int) floatval($valor);
        }

        // Remove não-dígitos para verificar se tem conteúdo
        $numeros = preg_replace('/\D/', '', $str);
        if (! $numeros || in_array($str, ['-', ''])) {
            return null;
        }

        return $str;
    }

    private function limpar(mixed $valor): ?string
    {
        $v = trim((string) ($valor ?? ''));
        return in_array($v, ['-', '', 'nan', '-']) ? null : $v;
    }

    private function limparEmail(mixed $valor): ?string
    {
        $v = trim((string) ($valor ?? ''));
        if (! $v || $v === '-' || ! str_contains($v, '@')) {
            return null;
        }
        return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }
}
