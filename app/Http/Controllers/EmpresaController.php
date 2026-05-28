<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\HistoricoEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaController extends Controller
{
    public function index(Request $request)
    {
        $busca  = $request->input('busca');
        $letra  = $request->input('letra');
        $status = $request->input('status', 'ativo');

        $query = Empresa::withCount('funcionariosAtivos');

        if ($busca)            $query->busca($busca);
        if ($letra)            $query->where('nome', 'like', $letra . '%');
        if ($status === 'ativo')   $query->where('ativo', true);
        elseif ($status === 'inativo') $query->where('ativo', false);

        $empresas = $query->orderBy('nome')->paginate(20)->withQueryString();
        $total    = Empresa::count();

        return view('empresas.index', compact('empresas', 'busca', 'letra', 'status', 'total'));
    }

    public function create()
    {
        return view('empresas.form', ['empresa' => new Empresa(), 'modo' => 'criar']);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'        => 'required|string|max:200|unique:empresas,nome',
            'responsavel' => 'nullable|string|max:150',
            'telefone'    => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:200',
            'observacoes' => 'nullable|string',
        ], $this->mensagens());

        $empresa = Empresa::create($dados);

        return redirect()->route('empresas.show', $empresa)
                         ->with('success', "Empresa \"{$empresa->nome}\" cadastrada com sucesso!");
    }

    public function show(Empresa $empresa)
    {
        $empresa->load(['funcionariosAtivos', 'historico.usuario']);
        $pontos_recentes = $empresa->pontos()
                                   ->with('funcionario')
                                   ->whereDate('data', today())
                                   ->orderBy('entrada')
                                   ->get();
        return view('empresas.show', compact('empresa', 'pontos_recentes'));
    }

    public function edit(Empresa $empresa)
    {
        return view('empresas.form', compact('empresa') + ['modo' => 'editar']);
    }

    public function update(Request $request, Empresa $empresa)
    {
        $dados = $request->validate([
            'nome'        => 'required|string|max:200|unique:empresas,nome,' . $empresa->id,
            'responsavel' => 'nullable|string|max:150',
            'telefone'    => 'nullable|string|max:20',
            'email'       => 'nullable|email|max:200',
            'observacoes' => 'nullable|string',
        ], $this->mensagens());

        $campos = ['nome', 'responsavel', 'telefone', 'email', 'observacoes'];
        foreach ($campos as $campo) {
            $valorAntigo = $empresa->$campo;
            $valorNovo   = $dados[$campo] ?? null;
            if ($valorAntigo !== $valorNovo) {
                HistoricoEmpresa::create([
                    'empresa_id'     => $empresa->id,
                    'usuario_id'     => Auth::id(),
                    'campo_alterado' => $campo,
                    'valor_anterior' => $valorAntigo,
                    'valor_novo'     => $valorNovo,
                ]);
            }
        }

        $empresa->update($dados);

        return redirect()->route('empresas.show', $empresa)
                         ->with('success', "Empresa atualizada com sucesso!");
    }

    public function toggleAtivo(Empresa $empresa)
    {
        if ($empresa->ativo && $empresa->funcionariosAtivos()->count() > 0) {
            return back()->with('error', "Não é possível inativar: empresa possui funcionários ativos.");
        }
        $empresa->update(['ativo' => !$empresa->ativo]);
        $acao = $empresa->ativo ? 'ativada' : 'inativada';
        return back()->with('success', "Empresa {$acao} com sucesso!");
    }

    public function buscar(Request $request)
    {
        $empresas = Empresa::ativas()
                           ->busca($request->input('q', ''))
                           ->orderBy('nome')
                           ->take(10)
                           ->get(['id', 'nome', 'responsavel']);
        return response()->json($empresas);
    }

    private function mensagens(): array
    {
        return [
            'nome.required' => 'O nome da empresa é obrigatório.',
            'nome.unique'   => 'Já existe uma empresa com este nome.',
            'email.email'   => 'E-mail inválido.',
        ];
    }
}
