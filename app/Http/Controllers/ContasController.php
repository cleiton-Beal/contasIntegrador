<?php

namespace App\Http\Controllers;

use App\Http\Helpers\CodeHelper;
use App\Models\Contas;
use App\Models\CpfsAutorizadosConta;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContasController extends Controller
{
    public function CriarConta(Request $request) {
        $validate = Validator::make($request->all(),[
            'Nome'              => 'required|String',
            'CpfCnpj'           => 'required|String',
            'Telefone'          => 'required|String',
            'Senha'             => 'required|String|min:4|max:4',
            'DataNascimento'    => 'Date|required',
            'Email'             => 'email|required',
            'Email_confirmation'=> 'email|required',
            'Logradouro'        => 'String|required',
            'Complemento'       => 'String|required',
            'Bairro'            => 'String|required',
            'Numero'            => 'Integer|required',
            'CEP'               => 'String|required',
            'Cidade'            => 'String|required',
            'RendaMensal'       => 'numeric|required'
        ]);
Log::info($request);

        if ($validate->fails()){
            Log::info( $validate->errors());
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram Erros na validação dos campos enviados',
                'Campos'    => $validate->errors()
            ],400);
        }
        Log::info(1);
        $cpfCnpj = CodeHelper::limpaCNPJandCPF($request->CpfCnpj);
        if (strlen($cpfCnpj) == 11 ? !CodeHelper::CPF($cpfCnpj) : !CodeHelper::CNPJ($cpfCnpj)) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => config('errors.CpfCnpjInvalido')
            ],400);
        }
        Log::info(1);
        if ($contas = Contas::where('CpfCnpj',$request->CpfCnpj)->count() > 0) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Já existe uma conta com esse CPF/CNPJ'
            ],400);
        }
        Log::info(1);
        try {
            $dados = $request->all();
            unset($dados['Email_confirmation']);
            $dados['Senha'] = Hash::make($dados['Senha']);
            $dados['Saldo'] = '100';
            $conta = Contas::create($dados);
            Log::info(1);
            $userAutorizado = CpfsAutorizadosConta::create([
                'cpfCnpjAutorizado' => $request->CpfCnpj,
                'contaId'           => $conta->id ,
            ]);
            Log::info(1);
            $firestore = app('firebase.firestore');
            $databaseFS = $firestore->database();
            $ref = $databaseFS->collection('Users')->document($request->CpfCnpj)->set(['Saldo' =>$dados['Saldo'], 'Name' =>$dados['Nome']]);
            Log::info(1);

            return response()->json([
                'Sucesso' => true,
                'Mensagem' => 'Conta Criada com sucesso!'
            ]);
        }
        catch(Exception $e) {
            Log::alert($e->getMessage());
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Ocorreram erros ao criar usuário, entre em contato com o suporte!'
            ]);
        }
    }

    public function BuscarContasPorUser() {

        try {
            $contas = json_decode(CpfsAutorizadosConta::select('contas.*')->where('cpfCnpjAutorizado',Auth::user()->CpfCnpj)->join('contas','contas.id','=','cpfs_autorizados_contas.contaId')->get());
            foreach($contas as $conta) {
                unset($conta->Senha, $conta->id, $conta->RendaMensal);
            }
            return response()->json([
                'Sucesso'   =>true,
                'Contas'    => $contas
            ]);
        }
        catch(Exception $e) {
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram erros na consulta, entre em contato com o suporte'
            ]);
        }
    }

    public function BuscarContaP2P(Request $request) {
        $validate = Validator::make($request->all(),[
            'CpfCnpj'           => 'required|String',
        ]);

        if ($validate->fails()){
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram Erros na validação dos campos enviados',
                'Campos'    => $validate->errors()
            ],400);
        }
        $contas =Contas::select('Nome', 'CpfCnpj')->where('CpfCnpj', $request->CpfCnpj)->first();
        if ($contas)
            return response()->json(['Sucesso'=> true ,'Conta' => $contas]);
        else
            return response()->json(['Sucesso'=> false ,'Mensagem' => 'Nenhuma conta encontrada!']);
    }






}
