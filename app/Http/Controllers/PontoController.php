<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Funcionario;
use App\Models\Ponto;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PontoController extends Controller
{
    public function index(Request $request)
    {
        $data       = $request->input('data', today()->format('Y-m-d'));
        $empresa_id = $request->input('empresa_id');
        $status     = $request->input('status');
        $busca      = $request->input('busca');
        // Usa evento da URL; se não informado, usa o evento ativo da sessão
        $evento_id  = $request->input('evento_id', session('evento_ativo_id'));

        $query = Ponto::with(['funcionario.empresa', 'evento'])->whereDate('data', $data);

        if ($empresa_id) $query->where('empresa_id', $empresa_id);
        if ($status)     $query->where('status', $status);
        if ($busca)      $query->whereHas('funcionario', fn($q) => $q->busca($busca));
        if ($evento_id)  $query->where('evento_id', $evento_id);

        $pontos = $query->orderBy('entrada')->paginate(30)->withQueryString();

        // Resumo filtrado pelo mesmo evento ativo
        $resumo = [
            'presentes'   => Ponto::data($data)->presentes()
                                  ->when($evento_id, fn ($q) => $q->where('evento_id', $evento_id))
                                  ->count(),
            'finalizados' => Ponto::data($data)->finalizados()
                                  ->when($evento_id, fn ($q) => $q->where('evento_id', $evento_id))
                                  ->count(),
            'total'       => Ponto::data($data)
                                  ->when($evento_id, fn ($q) => $q->where('evento_id', $evento_id))
                                  ->count(),
        ];

        $empresas    = \App\Models\Empresa::ativas()->orderBy('nome')->get(['id', 'nome']);
        $eventosLista = Evento::ativos()->orderByDesc('data_inicio')->get(['id', 'nome']);

        return view('ponto.index', compact(
            'pontos', 'data', 'empresa_id', 'evento_id', 'status', 'busca', 'resumo', 'empresas', 'eventosLista'
        ));
    }

    public function registrar()
    {
        $eventoAtivoId = session('evento_ativo_id');
        $limite24h     = now()->subHours(24)->format('Y-m-d H:i:s');

        $base = Ponto::with(['funcionario.empresa', 'evento'])
            ->where('status', 'presente')
            ->when($eventoAtivoId, fn($q) => $q->where('evento_id', $eventoAtivoId));

        // Presentes nas últimas 24 horas
        $presentes = (clone $base)
            ->whereRaw("CONCAT(data, ' ', entrada) >= ?", [$limite24h])
            ->orderByRaw("CONCAT(data, ' ', entrada) DESC")
            ->get();

        // Registros sem saída anteriores às últimas 24 horas
        $semSaida = (clone $base)
            ->whereRaw("CONCAT(data, ' ', entrada) < ?", [$limite24h])
            ->orderByRaw("CONCAT(data, ' ', entrada) DESC")
            ->get();

        $eventoAtivo = $eventoAtivoId ? Evento::find($eventoAtivoId) : null;

        return view('ponto.registrar', compact('presentes', 'semSaida', 'eventoAtivo'));
    }

    public function entrada(Request $request)
    {
        $request->validate([
            'funcionario_id' => 'required|exists:funcionarios,id',
            'entrada_manual' => 'nullable|date_format:H:i',
        ], [
            'entrada_manual.date_format' => 'Horário inválido. Use o formato HH:MM.',
        ]);

        // Sempre usa o evento ativo da sessão
        $request->merge(['evento_id' => session('evento_ativo_id')]);

        $funcionario = Funcionario::with('empresa')->findOrFail($request->funcionario_id);

        $ponto = Ponto::whereDate('data', today())
                      ->where('funcionario_id', $funcionario->id)
                      ->first();

        if ($ponto) {
            if ($ponto->status === 'presente')
                return response()->json(['erro' => 'Funcionário já registrou entrada hoje.'], 422);
            if ($ponto->status === 'finalizado')
                return response()->json(['erro' => 'Jornada já encerrada hoje.'], 422);
        }

        // Horário manual ou automático
        if ($request->filled('entrada_manual')) {
            $horarioEntrada = Carbon::createFromFormat('H:i', $request->entrada_manual);

            if ($horarioEntrada->gt(now())) {
                return response()->json(['erro' => 'O horário de entrada não pode ser no futuro.'], 422);
            }

            $entrada = $horarioEntrada->format('H:i:s');
            $horarioExibir = $horarioEntrada->format('H:i');
        } else {
            $entrada = now()->format('H:i:s');
            $horarioExibir = now()->format('H:i');
        }

        $pontoNovo = Ponto::create([
            'funcionario_id' => $funcionario->id,
            'empresa_id'     => $funcionario->empresa_id,
            'evento_id'      => $request->input('evento_id'),
            'data'           => today(),
            'entrada'        => $entrada,
            'status'         => 'presente',
            'registrado_por' => Auth::id(),
        ]);

        $eventoNome = null;
        if ($request->filled('evento_id')) {
            $eventoNome = Evento::find($request->evento_id)?->nome;
        }

        return response()->json([
            'sucesso'      => true,
            'ponto_id'     => $pontoNovo->id,
            'mensagem'     => "Entrada registrada para {$funcionario->nome}",
            'horario'      => $horarioExibir,
            'evento_nome'  => $eventoNome,
            'funcionario'  => [
                'nome'     => $funcionario->nome,
                'empresa'  => $funcionario->empresa->nome,
                'funcao'   => $funcionario->funcao_cargo,
                'foto_url' => $funcionario->foto_url,
            ],
        ]);
    }

    public function saida(Request $request)
    {
        $request->validate([
            'ponto_id'     => 'required|exists:pontos,id',
            'saida_manual' => 'nullable|date_format:H:i',
            'data_saida'   => 'nullable|string',
        ]);

        $ponto = Ponto::with('funcionario')->findOrFail($request->ponto_id);

        if ($ponto->status !== 'presente')
            return response()->json(['erro' => 'Ponto não está como "presente".'], 422);

        if ($request->filled('saida_manual')) {
            $horaSaida    = $request->saida_manual . ':00';
            $dataSaidaStr = $ponto->data->format('Y-m-d');

            if ($request->filled('data_saida')) {
                try {
                    $dataSaidaStr = Carbon::createFromFormat('d/m/Y', $request->data_saida)->format('Y-m-d');
                } catch (\Exception $e) {}
            }

            $dtEntrada = Carbon::parse($ponto->data->format('Y-m-d') . ' ' . $ponto->entrada);
            $dtSaida   = Carbon::parse($dataSaidaStr . ' ' . $horaSaida);

            if ($dtSaida <= $dtEntrada) {
                return response()->json(['erro' => 'O horário de saída deve ser posterior ao horário de entrada.'], 422);
            }

            $ponto->saida            = $horaSaida;
            $ponto->horas_trabalhadas = gmdate('H:i:s', $dtSaida->diffInSeconds($dtEntrada));
            $ponto->status           = 'finalizado';
            $ponto->save();
        } else {
            $ponto->saida = now()->format('H:i:s');
            $ponto->save();
            $ponto->calcularHoras();
        }

        return response()->json([
            'sucesso'  => true,
            'mensagem' => "Saída registrada para {$ponto->funcionario->nome}",
            'horario'  => substr($ponto->saida, 0, 5),
            'horas'    => $ponto->horas_trabalhadas,
        ]);
    }

    public function saidaPorFuncionario(Request $request)
    {
        $request->validate(['funcionario_id' => 'required|exists:funcionarios,id']);

        $ponto = Ponto::whereDate('data', today())
                      ->where('funcionario_id', $request->funcionario_id)
                      ->where('status', 'presente')
                      ->first();

        if (!$ponto)
            return response()->json(['erro' => 'Nenhuma entrada encontrada para hoje.'], 422);

        $ponto->saida = now()->format('H:i:s');
        $ponto->save();
        $ponto->calcularHoras();

        return response()->json([
            'sucesso'  => true,
            'mensagem' => "Saída registrada. Horas: {$ponto->horas_trabalhadas}",
            'horas'    => $ponto->horas_trabalhadas,
        ]);
    }

    public function update(Request $request, Ponto $ponto)
    {
        $request->validate([
            'data'    => 'required|date',
            'entrada' => 'required|date_format:H:i',
            'saida'   => 'nullable|date_format:H:i|after:entrada',
        ], [
            'data.required'       => 'A data é obrigatória.',
            'entrada.required'    => 'O horário de entrada é obrigatório.',
            'entrada.date_format' => 'Entrada inválida. Use HH:MM.',
            'saida.date_format'   => 'Saída inválida. Use HH:MM.',
            'saida.after'         => 'A saída deve ser após a entrada.',
        ]);

        $entrada = $request->entrada . ':00';
        $saida   = $request->saida ? $request->saida . ':00' : null;

        $ponto->data    = $request->data;
        $ponto->entrada = $entrada;
        $ponto->saida   = $saida;

        // Recalcular horas e status
        if ($saida) {
            $ini  = Carbon::createFromFormat('H:i:s', $entrada);
            $fim  = Carbon::createFromFormat('H:i:s', $saida);
            $diff = $ini->diff($fim);
            $ponto->horas_trabalhadas = $diff->format('%H:%I:%S');
            $ponto->status = 'finalizado';
        } else {
            $ponto->horas_trabalhadas = null;
            $ponto->status = 'presente';
        }

        $ponto->save();

        return response()->json([
            'sucesso'  => true,
            'mensagem' => 'Ponto atualizado com sucesso.',
            'ponto'    => [
                'id'               => $ponto->id,
                'data'             => $ponto->data->format('d/m/Y'),
                'entrada'          => substr($ponto->entrada, 0, 5),
                'saida'            => $ponto->saida ? substr($ponto->saida, 0, 5) : null,
                'horas_trabalhadas'=> $ponto->horas_trabalhadas ? substr($ponto->horas_trabalhadas, 0, 5) : null,
                'status'           => $ponto->status,
                'status_badge'     => $ponto->status_badge,
            ],
        ]);
    }

    public function destroy(Ponto $ponto)
    {
        $ponto->delete();
        return response()->json(['sucesso' => true, 'mensagem' => 'Registro excluído com sucesso.']);
    }

    public function historico(Funcionario $funcionario)
    {
        $pontos = $funcionario->pontos()->with('evento')->orderByDesc('data')->paginate(20);
        return view('ponto.historico', compact('funcionario', 'pontos'));
    }
}
