<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $busca       = $request->input('busca');
        $empresa_id  = $request->input('empresa_id');
        $coordenador = $request->input('coordenador');
        $area        = $request->input('area');

        $query = Funcionario::with('empresa')->ativos();

        if ($busca)                $query->busca($busca);
        if ($empresa_id)           $query->where('empresa_id', $empresa_id);
        if ($coordenador === '1')  $query->where('coordenador', true);
        if ($area)                 $query->where('area_acesso', $area);

        $funcionarios = $query->orderBy('nome')->paginate(25)->withQueryString();
        $empresas     = Empresa::ativas()->orderBy('nome')->get(['id', 'nome']);
        $areas        = Funcionario::distinct()->pluck('area_acesso')->sort()->values();

        return view('funcionarios.index', compact(
            'funcionarios', 'empresas', 'busca', 'empresa_id', 'coordenador', 'area', 'areas'
        ));
    }

    public function create()
    {
        $empresas = Empresa::ativas()->orderBy('nome')->get(['id', 'nome']);
        return view('funcionarios.form', [
            'funcionario' => new Funcionario(),
            'empresas'    => $empresas,
            'modo'        => 'criar',
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['foto'] = $this->processarFoto($request);
        $funcionario   = Funcionario::create($dados);

        return redirect()->route('funcionarios.show', $funcionario)
                         ->with('success', "Funcionário \"{$funcionario->nome}\" cadastrado com sucesso!");
    }

    public function show(Funcionario $funcionario)
    {
        $funcionario->load(['empresa', 'pontos' => fn($q) => $q->orderByDesc('data')->take(30)]);
        return view('funcionarios.show', compact('funcionario'));
    }

    public function edit(Funcionario $funcionario)
    {
        $empresas = Empresa::ativas()->orderBy('nome')->get(['id', 'nome']);
        return view('funcionarios.form', compact('funcionario', 'empresas') + ['modo' => 'editar']);
    }

    public function update(Request $request, Funcionario $funcionario)
    {
        $dados = $this->validar($request, $funcionario->id);

        if ($request->hasFile('foto')) {
            if ($funcionario->foto) Storage::disk('public')->delete($funcionario->foto);
            $dados['foto'] = $this->processarFoto($request);
        }

        $funcionario->update($dados);

        return redirect()->route('funcionarios.show', $funcionario)
                         ->with('success', "Funcionário atualizado com sucesso!");
    }

    public function toggleAtivo(Funcionario $funcionario)
    {
        $funcionario->update(['ativo' => !$funcionario->ativo]);
        $acao = $funcionario->ativo ? 'ativado' : 'inativado';
        return back()->with('success', "Funcionário {$acao} com sucesso!");
    }

    public function verificarCpf(Request $request)
    {
        $cpf    = preg_replace('/\D/', '', $request->input('cpf', ''));
        $id     = $request->input('id');
        $existe = Funcionario::where('cpf', $cpf)
                             ->when($id, fn($q) => $q->where('id', '!=', $id))
                             ->exists();
        return response()->json(['existe' => $existe]);
    }

    public function autocomplete(Request $request)
    {
        $funcionarios = Funcionario::with('empresa')
                                   ->ativos()
                                   ->busca($request->input('q', ''))
                                   ->orderBy('nome')
                                   ->take(10)
                                   ->get(['id', 'nome', 'empresa_id', 'funcao_cargo', 'foto', 'coordenador']);

        return response()->json($funcionarios->map(fn($f) => [
            'id'          => $f->id,
            'nome'        => $f->nome,
            'empresa'     => $f->empresa->nome ?? '',
            'funcao'      => $f->funcao_cargo,
            'foto_url'    => $f->foto_url,
            'coordenador' => $f->coordenador,
        ]));
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'empresa_id'   => 'required|exists:empresas,id',
            'nome'         => 'required|string|max:200',
            'cpf'          => 'nullable|string|max:14',
            'telefone'     => 'nullable|string|max:20',
            'foto'         => 'nullable|image|max:2048',
            'funcao_cargo' => 'required|string|max:100',
            'area_acesso'  => 'required|string|max:100',
            'coordenador'  => 'nullable|boolean',
        ], [
            'empresa_id.required'   => 'Selecione uma empresa.',
            'nome.required'         => 'O nome é obrigatório.',
            'funcao_cargo.required' => 'A função/cargo é obrigatória.',
            'area_acesso.required'  => 'Selecione a área de acesso.',
        ]) + ['coordenador' => $request->boolean('coordenador')];
    }

    private function processarFoto(Request $request): ?string
    {
        if (!$request->hasFile('foto')) return null;

        $caminho = 'uploads/funcionarios/' . uniqid() . '.jpg';

        try {
            // Intervention Image v3
            $manager = new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
            $image = $manager->read($request->file('foto'))
                             ->cover(300, 300)
                             ->toJpeg(85);
        } catch (\Throwable $e) {
            // Fallback v2
            $image = \Intervention\Image\Facades\Image::make($request->file('foto'))
                         ->fit(300, 300)
                         ->encode('jpg', 85);
        }

        Storage::disk('public')->put($caminho, $image);
        return $caminho;
    }
}
