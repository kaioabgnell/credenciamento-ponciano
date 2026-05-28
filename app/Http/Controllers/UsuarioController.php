<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $busca = $request->input('busca');
        $query = Usuario::query();

        if ($busca) {
            $query->where(fn($q) =>
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('email', 'like', "%{$busca}%")
                  ->orWhere('cargo', 'like', "%{$busca}%")
            );
        }

        $usuarios = $query->orderBy('nome')->paginate(20)->withQueryString();
        return view('usuarios.index', compact('usuarios', 'busca'));
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new Usuario(), 'modo' => 'criar']);
    }

    public function store(Request $request)
    {
        $dados          = $this->validar($request);
        $dados['senha'] = Hash::make($dados['senha']);
        $dados['foto']  = $this->processarFoto($request);
        $usuario        = Usuario::create($dados);

        return redirect()->route('usuarios.index')
                         ->with('success', "Usuário \"{$usuario->nome}\" criado com sucesso!");
    }

    public function edit(Usuario $usuario)
    {
        return view('usuarios.form', compact('usuario') + ['modo' => 'editar']);
    }

    public function update(Request $request, Usuario $usuario)
    {
        $dados = $this->validar($request, $usuario->id, true);

        if (!empty($dados['senha'])) {
            $dados['senha'] = Hash::make($dados['senha']);
        } else {
            unset($dados['senha']);
        }

        if ($request->hasFile('foto')) {
            if ($usuario->foto) Storage::disk('public')->delete($usuario->foto);
            $dados['foto'] = $this->processarFoto($request);
        }

        $usuario->update($dados);

        return redirect()->route('usuarios.index')
                         ->with('success', "Usuário atualizado com sucesso!");
    }

    public function toggleAtivo(Usuario $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'Você não pode desativar sua própria conta.');
        }
        $usuario->update(['ativo' => !$usuario->ativo]);
        return back()->with('success', 'Status do usuário atualizado.');
    }

    private function validar(Request $request, ?int $ignorarId = null, bool $editando = false): array
    {
        $senhaRule = $editando ? 'nullable|min:8|confirmed' : 'required|min:8|confirmed';

        return $request->validate([
            'nome'              => 'required|string|max:150',
            'cpf'               => 'required|string|max:14|unique:usuarios,cpf,' . $ignorarId,
            'email'             => 'required|email|unique:usuarios,email,' . $ignorarId,
            'data_nascimento'   => 'required|date',
            'foto'              => 'nullable|image|max:2048',
            'senha'             => $senhaRule,
            'senha_confirmation'=> 'nullable',
            'telefone1'         => 'required|string|max:20',
            'telefone2'         => 'nullable|string|max:20',
            'cargo'             => 'required|string|max:100',
        ], [
            'nome.required'            => 'O nome é obrigatório.',
            'cpf.required'             => 'O CPF é obrigatório.',
            'cpf.unique'               => 'CPF já cadastrado.',
            'email.required'           => 'O e-mail é obrigatório.',
            'email.unique'             => 'E-mail já cadastrado.',
            'data_nascimento.required' => 'A data de nascimento é obrigatória.',
            'senha.required'           => 'A senha é obrigatória.',
            'senha.min'                => 'A senha deve ter no mínimo 8 caracteres.',
            'senha.confirmed'          => 'As senhas não coincidem.',
            'telefone1.required'       => 'O telefone é obrigatório.',
            'cargo.required'           => 'O cargo é obrigatório.',
        ]);
    }

    private function processarFoto(Request $request): ?string
    {
        if (!$request->hasFile('foto')) return null;

        $caminho = 'uploads/usuarios/' . uniqid() . '.jpg';

        try {
            $manager = new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );
            $image = $manager->read($request->file('foto'))
                             ->cover(300, 300)
                             ->toJpeg(85);
        } catch (\Throwable $e) {
            $image = \Intervention\Image\Facades\Image::make($request->file('foto'))
                         ->fit(300, 300)
                         ->encode('jpg', 85);
        }

        Storage::disk('public')->put($caminho, $image);
        return $caminho;
    }
}
