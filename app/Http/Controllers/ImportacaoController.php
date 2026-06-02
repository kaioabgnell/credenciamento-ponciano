<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\Importacao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportacaoController extends Controller
{
    // ── Listagem ────────────────────────────────────────────────────
    public function index()
    {
        $importacoes = Importacao::with(['empresa', 'usuario'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $modeloUrl = asset('storage/uploads/modelo/CREDENCIAMENTO-PLANILHA-MODELO.xlsx');

        return view('importacoes.index', compact('importacoes', 'modeloUrl'));
    }

    // ── Upload e análise ─────────────────────────────────────────────
    public function upload(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'arquivo.required' => 'Selecione um arquivo para importar.',
            'arquivo.mimes'    => 'Apenas arquivos .xlsx e .xls são aceitos.',
            'arquivo.max'      => 'O arquivo não pode ultrapassar 20 MB.',
        ]);

        $file         = $request->file('arquivo');
        $nomeOriginal = $file->getClientOriginalName();
        $tempPath     = $file->store('importacoes/temp');

        try {
            $fullPath    = storage_path('app/' . $tempPath);
            $spreadsheet = IOFactory::load($fullPath);
            $sheet       = $spreadsheet->getSheet(0);

            // ── Cabeçalho da empresa (linhas 3-6, coluna B = índice 2) ──
            $empresaNome        = $this->celula($sheet, 2, 3);
            $empresaResponsavel = $this->celula($sheet, 2, 4);
            $empresaEmail       = $this->celula($sheet, 2, 5);
            $empresaContato     = $this->celula($sheet, 2, 6);

            if (! $empresaNome) {
                Storage::delete($tempPath);
                return response()->json(['erro' => 'O campo EMPRESA não foi encontrado na planilha. Verifique se está usando a planilha modelo correta.'], 422);
            }

            // ── Conta funcionários (dados a partir da linha 8, col B) ──
            $totalFuncionarios = 0;
            $row = 8;
            while ($this->celula($sheet, 2, $row) !== '') {
                $totalFuncionarios++;
                $row++;
            }

            if ($totalFuncionarios === 0) {
                Storage::delete($tempPath);
                return response()->json(['erro' => 'Nenhum funcionário encontrado na planilha. Preencha a tabela a partir da linha 8.'], 422);
            }

            // Armazena dados na sessão
            session([
                'importacao_temp' => [
                    'path'                => $tempPath,
                    'arquivo_nome'        => $nomeOriginal,
                    'empresa_nome'        => $empresaNome,
                    'empresa_responsavel' => $empresaResponsavel,
                    'empresa_email'       => $empresaEmail,
                    'empresa_contato'     => $empresaContato,
                    'total_funcionarios'  => $totalFuncionarios,
                ],
            ]);

            // Verifica empresa duplicada
            $existente = Empresa::where('nome', $empresaNome)->first();

            return response()->json([
                'empresa_nome'       => $empresaNome,
                'total_funcionarios' => $totalFuncionarios,
                'empresa_duplicada'  => $existente !== null,
                'empresa_existente'  => $existente ? ['id' => $existente->id, 'nome' => $existente->nome] : null,
            ]);

        } catch (\Exception $e) {
            Storage::delete($tempPath);
            return response()->json(['erro' => 'Erro ao ler o arquivo: ' . $e->getMessage()], 422);
        }
    }

    // ── Processamento final ──────────────────────────────────────────
    public function processar(Request $request)
    {
        $request->validate([
            'decisao'             => 'required|in:usar_existente,criar_nova',
            'empresa_existente_id' => 'required_if:decisao,usar_existente|nullable|exists:empresas,id',
        ]);

        $temp = session('importacao_temp');
        if (! $temp) {
            return response()->json(['erro' => 'Sessão expirada. Faça o upload do arquivo novamente.'], 422);
        }

        $fullPath = storage_path('app/' . $temp['path']);

        try {
            // ── Cria ou reutiliza empresa ──────────────────────────────
            if ($request->decisao === 'usar_existente') {
                $empresa = Empresa::findOrFail($request->empresa_existente_id);
                $acaoEmpresa = 'existente';
            } else {
                $empresa = Empresa::create([
                    'nome'        => $temp['empresa_nome'],
                    'responsavel' => $temp['empresa_responsavel'] ?: null,
                    'email'       => filter_var($temp['empresa_email'], FILTER_VALIDATE_EMAIL) ? $temp['empresa_email'] : null,
                    'telefone'    => preg_replace('/\D/', '', $temp['empresa_contato']) ?: null,
                    'ativo'       => true,
                ]);
                $acaoEmpresa = 'nova';
            }
        } catch (\Exception $e) {
            return response()->json(['erro' => 'Erro ao processar empresa: ' . $e->getMessage()], 422);
        }

        // ── Importa funcionários ────────────────────────────────────
        $spreadsheet = IOFactory::load($fullPath);
        $sheet       = $spreadsheet->getSheet(0);

        $importados   = 0;
        $erros        = [];
        $row          = 8;
        $totalLinhas  = 0;

        while (($nome = $this->celula($sheet, 2, $row)) !== '') {
            $totalLinhas++;

            $cpfRaw     = $sheet->getCellByColumnAndRow(3, $row);
            $cpf        = $this->limparCpf($cpfRaw->getValue(), $cpfRaw->getFormattedValue());
            $funcao     = $this->celula($sheet, 4, $row) ?: 'Não informado';
            $dataNasc   = $this->parsarData($sheet->getCellByColumnAndRow(5, $row));

            try {
                Funcionario::create([
                    'nome'            => $nome,
                    'empresa_id'      => $empresa->id,
                    'cpf'             => $cpf,
                    'funcao_cargo'    => $funcao,
                    'data_nascimento' => $dataNasc,
                    'area_acesso'     => 'TODOS',
                    'coordenador'     => false,
                    'ativo'           => true,
                ]);
                $importados++;
            } catch (\Exception $e) {
                $erros[] = "Linha {$row} ({$nome}): " . $e->getMessage();
            }

            $row++;
        }

        // ── Registra importação ─────────────────────────────────────
        Importacao::create([
            'arquivo_nome'       => $temp['arquivo_nome'],
            'empresa_nome'       => $temp['empresa_nome'],
            'empresa_id'         => $empresa->id,
            'empresa_acao'       => $acaoEmpresa,
            'total_funcionarios' => $totalLinhas,
            'importados'         => $importados,
            'com_erros'          => count($erros),
            'detalhes_erros'     => $erros ?: null,
            'usuario_id'         => Auth::id(),
        ]);

        // Limpa sessão e arquivo temporário
        session()->forget('importacao_temp');
        Storage::delete($temp['path']);

        return response()->json([
            'sucesso'    => true,
            'importados' => $importados,
            'com_erros'  => count($erros),
            'erros'      => $erros,
            'empresa'    => ['id' => $empresa->id, 'nome' => $empresa->nome],
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function celula($sheet, int $col, int $row): string
    {
        return trim((string) $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue());
    }

    private function limparCpf(mixed $valor, string $formatado): ?string
    {
        $str = is_numeric($valor) ? (string) (int) floatval($valor) : $formatado;
        $digitos = preg_replace('/\D/', '', $str);
        if (! $digitos) return null;
        if (strlen($digitos) < 11) {
            $digitos = str_pad($digitos, 11, '0', STR_PAD_LEFT);
        }
        return substr($digitos, 0, 11);
    }

    private function parsarData($cell): ?string
    {
        $valor     = $cell->getValue();
        $formatado = $cell->getFormattedValue();

        if (! $valor && ! $formatado) return null;

        // Serial Excel
        if (is_numeric($valor) && $valor > 1) {
            try {
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $valor)
                )->toDateString();
            } catch (\Exception) {}
        }

        // String com data
        $texto = trim($formatado);
        if (! $texto || $texto === '-') return null;

        try {
            return Carbon::parse($texto)->toDateString();
        } catch (\Exception) {}

        return null;
    }
}
