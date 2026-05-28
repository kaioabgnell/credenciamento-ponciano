<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

class EventoController extends Controller
{
    public function index(Request $request)
    {
        $busca  = $request->input('busca');
        $status = $request->input('status', 'todos');

        $query = Evento::orderByDesc('data_inicio');

        if ($busca) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('nome_organizador', 'like', "%{$busca}%");
            });
        }

        if ($status === 'ativo')    $query->where('ativo', true);
        if ($status === 'inativo')  $query->where('ativo', false);

        $eventos = $query->paginate(20)->withQueryString();

        return view('eventos.index', compact('eventos', 'busca', 'status'));
    }

    public function create()
    {
        return view('eventos.form', ['evento' => new Evento(), 'modo' => 'criar']);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'                => 'required|string|max:200',
            'data_inicio'         => 'required|date',
            'data_fim'            => 'required|date|after_or_equal:data_inicio',
            'nome_organizador'    => 'nullable|string|max:150',
            'telefone_organizador'=> 'nullable|string|max:20',
        ], $this->mensagens());

        $evento = Evento::create($dados);

        return redirect()->route('eventos.index')
                         ->with('success', "Evento \"{$evento->nome}\" cadastrado com sucesso!");
    }

    public function edit(Evento $evento)
    {
        return view('eventos.form', compact('evento') + ['modo' => 'editar']);
    }

    public function update(Request $request, Evento $evento)
    {
        $dados = $request->validate([
            'nome'                => 'required|string|max:200',
            'data_inicio'         => 'required|date',
            'data_fim'            => 'required|date|after_or_equal:data_inicio',
            'nome_organizador'    => 'nullable|string|max:150',
            'telefone_organizador'=> 'nullable|string|max:20',
        ], $this->mensagens());

        $evento->update($dados);

        return redirect()->route('eventos.index')
                         ->with('success', "Evento \"{$evento->nome}\" atualizado com sucesso!");
    }

    /** Salva o evento selecionado na sessão global */
    public function selecionarNaSessao(Request $request)
    {
        $request->validate(['evento_id' => 'required|exists:eventos,id']);

        $evento = Evento::findOrFail($request->evento_id);
        $request->session()->put('evento_ativo_id', $evento->id);
        $request->session()->put('evento_ativo_nome', $evento->nome);

        return response()->json([
            'sucesso'      => true,
            'evento_id'    => $evento->id,
            'evento_nome'  => $evento->nome,
        ]);
    }

    public function toggleAtivo(Evento $evento)
    {
        $evento->update(['ativo' => ! $evento->ativo]);
        $acao = $evento->ativo ? 'ativado' : 'inativado';
        return back()->with('success', "Evento {$acao} com sucesso!");
    }

    private function mensagens(): array
    {
        return [
            'nome.required'               => 'O nome do evento é obrigatório.',
            'data_inicio.required'        => 'A data de início é obrigatória.',
            'data_fim.required'           => 'A data de término é obrigatória.',
            'data_fim.after_or_equal'     => 'A data de término deve ser igual ou após a data de início.',
        ];
    }
}
