<?php

namespace App\Http\Controllers;

use App\Http\Helpers\CodeHelper;
use App\Models\Boletos;
use App\Models\Contas;
use App\Models\CpfsAutorizadosConta;
use App\Models\Extrato;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransacaoController extends Controller
{

    public function CadastrarP2P(Request $request) {
        $validate = Validator::make($request->all(),[
            'CpfCnpjAccount'    => 'required',
            'SenhaAccount'      => 'required',
            'ContaDestino'      => 'required',
            'Valor'             => 'numeric|required'
        ]);
        if ($validate->fails()){
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram Erros na validação dos campos enviados',
                'Campos'    => $validate->errors()
            ],400);
        }

        $request->CpfCnpjAccount = CodeHelper::limpaCNPJandCPF($request->CpfCnpjAccount);
        $request->ContaDestino = CodeHelper::limpaCNPJandCPF($request->ContaDestino);

        $contaOrigem = Contas::where('CpfCnpj',$request->CpfCnpjAccount)->firstorFail();
        if (!$contaOrigem || !Hash::check($request->SenhaAccount, $contaOrigem->Senha)) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Credenciais inválidas'
            ]);
        }

        if ($contaOrigem->Saldo <= $request->Valor) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Saldo insuficiente'
            ]);
        }

        $contaDestino = Contas::where('CpfCnpj',$request->ContaDestino)->firstOrFail();
        if (!$contaDestino){
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Conta Destino inexistente!'
            ]);
        }

        if ($contaDestino->id ==$contaOrigem->id ) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'A conta Origem é a mesma que a destino!'
            ]);
        }

        try {
            $contaOrigem->Saldo -= $request->Valor;
            $contaDestino->Saldo += $request->Valor;
            $idTransacao = Str::uuid();
            $extratoOrigem = Extrato::create([
                'idTransacao'   => $idTransacao,
                'conta'         => $contaOrigem->id,
                'valor'         => $request->Valor,
                'nomeOrigem'    => $contaOrigem->Nome,
                'tipoTransacao' => 'P2P Enviado',
                'nomeDestino'   => $contaDestino->Nome,
                'contaOrigem'   => $contaOrigem->CpfCnpj,
                'contaDestino'  => $contaDestino->CpfCnpj,
                'Status'        => 'pendente',
            ]);


            $extratoDestino = Extrato::create([
                'idTransacao'   => $idTransacao ,
                'conta'         => $contaDestino->id,
                'valor'         => $request->Valor,
                'nomeOrigem'    => $contaOrigem->Nome,
                'tipoTransacao' => 'P2P Recebido',
                'nomeDestino'   => $contaDestino->Nome,
                'contaOrigem'   => $contaOrigem->CpfCnpj,
                'contaDestino'  => $contaDestino->CpfCnpj,
                'Status'        => 'pendente',
            ]);

            $contaDestino->save();
            $contaOrigem->save();

            $extratoOrigem->Status = 'Concluido';
            $extratoDestino->Status = 'concluido';

            $extratoDestino->save();
            $extratoOrigem->save();
            $extratoOrigem->dataTransacao = date('d/m/Y H:i:s', strtotime($extratoOrigem->created_at));

            $firestore = app('firebase.firestore');
            $databaseFS = $firestore->database();
            $refOrigem = $databaseFS->collection('Users')->document($contaOrigem->CpfCnpj);
            $refDestino = $databaseFS->collection('Users')->document($contaDestino->CpfCnpj);
            $refOrigem->update([
                ['path' => "Saldo", 'value' => "$contaOrigem->Saldo"]
            ]);
            $refDestino->update([
                ['path' => "Saldo", 'value' =>"$contaDestino->Saldo"]
            ]);
            return response()->json([
                'Sucesso' => true,
                'Mensagem' => 'P2P Realizado com Sucesso!',
                'Extrato' => $extratoOrigem
            ]);

        }
        catch(Exception $e) {
            Log::alert($e->getMessage());
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Ocorreram erros ao realizar transferência'
            ]);
        }
    }

    public function BuscarExtrato($account) {
        $account = CpfsAutorizadosConta::where('cpfCnpjAutorizado',Auth::user()->CpfCnpj)->join('contas','contas.id','=','cpfs_autorizados_contas.contaId')->where('contas.CpfCnpj', $account)->first();

        if (!$account) {
            return response()->json(['Sucesso' => false, 'Mensagem' => 'Você não tem acesso a essa conta!']);
        }
        else
        $extrato = Extrato::where('conta', $account->id)->orderBy('created_at','desc')->get();
        foreach ($extrato as $e) {
            $e->dataTransacao = date('d/m/Y H:i:s', strtotime($e->created_at));
        }
        return $extrato;

    }

    public function CadastrarPagamentoBoleto(Request $request) {
        $validate = Validator::make($request->all(),[
            'CpfCnpjAccount'    => 'required',
            'SenhaAccount'      => 'required',
            'BarCode'           => 'required',
        ]);

        if ($validate->fails()){
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram Erros na validação dos campos enviados',
                'Campos'    => $validate->errors()
            ],400);
        }

        $request->CpfCnpjAccount = CodeHelper::limpaCNPJandCPF($request->CpfCnpjAccount);
        $contaOrigem = Contas::where('CpfCnpj',$request->CpfCnpjAccount)->firstorFail();
        if (!$contaOrigem || !Hash::check($request->SenhaAccount, $contaOrigem->Senha)) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Credenciais inválidas'
            ]);
        }

        $boleto = Boletos::where('CodBar', $request->BarCode)->first();
        if (!$boleto ||$boleto->status == 'PAGO') {
            return response()->json(['Sucesso' => false, 'Mensagem' => 'Boleto já Pago']);
        }

        $contaDestino = Contas::where('CpfCnpj', $boleto->contaDestino)->first();
        if ($contaOrigem->Saldo < $boleto->Valor) {
            return response()->json(['Sucesso' => false, 'Mensagem' => 'Saldo Insuficiente']);
        }

        $idTransacao = Str::uuid();
        $extratoDestino = Extrato::create([
            'idTransacao'   => $idTransacao ,
            'conta'         => $contaDestino->id,
            'valor'         => $boleto->Valor,
            'nomeOrigem'    => $contaDestino->Nome,
            'tipoTransacao' => 'Boleto',
            'nomeDestino'   => $contaOrigem->Nome,
            'contaOrigem'   => $contaDestino->CpfCnpj,
            'contaDestino'  => $contaOrigem->CpfCnpj,
            'Status'        => 'pendente',
        ]);

        $extratoOrigem = Extrato::create([
            'idTransacao'   => $idTransacao ,
            'conta'         => $contaOrigem->id,
            'valor'         => $boleto->Valor,
            'nomeOrigem'    => $contaOrigem->Nome,
            'tipoTransacao' => 'Pagamento de Boleto',
            'nomeDestino'   => $contaDestino->Nome,
            'contaOrigem'   => $contaOrigem->CpfCnpj,
            'contaDestino'  => $contaDestino->CpfCnpj,
            'Status'        => 'pendente',
        ]);
        try {

            $contaOrigem->Saldo -= $boleto->Valor;
            $contaOrigem->save();
            $contaDestino->Saldo += $boleto->Valor;
            $contaDestino->save();
            $boleto->status = 'PAGO';
            $boleto->dataPagamento = Date('Y-m-d H:i:s');
            $boleto->save();
            $extratoOrigem->Status = 'Concluido';
            $extratoDestino->Status = 'concluido';

            $extratoDestino->save();
            $extratoOrigem->save();
            $extratoOrigem->dataTransacao = date('d/m/Y H:i:s', strtotime($extratoOrigem->created_at));

            $firestore = app('firebase.firestore');
            $databaseFS = $firestore->database();
            $refOrigem = $databaseFS->collection('Users')->document($contaOrigem->CpfCnpj);
            $refDestino = $databaseFS->collection('Users')->document($contaDestino->CpfCnpj);
            $refOrigem->update([
                ['path' => "Saldo", 'value' => "$contaOrigem->Saldo"]
            ]);
            $refDestino->update([
                ['path' => "Saldo", 'value' =>"$contaDestino->Saldo"]
            ]);

            return response()->json(['Sucesso' => true,'Extrato'=> $extratoOrigem, 'Mensagem' => "Pagamento no valor de $boleto->Valor pago com sucesso!"]);
        }
        catch(Exception $e) {
            Log::alert($e);
            return response()->json(['Sucesso' => false, 'Mensagem' => 'Ocorreram erros ao Pagar o boleto!' ]);
        }
    }

    public function GerarBoleto(Request $request) {
        $validate = Validator::make($request->all(),[
            'CpfCnpjAccount'    => 'required',
            'SenhaAccount'      => 'required',
            'Valor'             => 'required|numeric',
            'Descricao'         => 'required',
        ]);
        Log::info($request->all());

        if ($validate->fails()){
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram Erros na validação dos campos enviados',
                'Campos'    => $validate->errors()
            ],400);
        }

        $request->CpfCnpjAccount = CodeHelper::limpaCNPJandCPF($request->CpfCnpjAccount);
        $contaOrigem = Contas::where('CpfCnpj',$request->CpfCnpjAccount)->firstorFail();
        if (!$contaOrigem || !Hash::check($request->SenhaAccount, $contaOrigem->Senha)) {
            return response()->json([
                'Sucesso' => false,
                'Mensagem' => 'Credenciais inválidas'
            ]);
        }

        try {
           $random = "0";
           for ($i=0; $i < 47; $i++) {
                $number =rand(0,9);
                $random = $random."$number";
           }
            $boleto = Boletos::create([
                'CodBar' => $random,
                'Valor' => $request->Valor,
                'Descricao' => $request->Descricao,
                'contaDestino' =>$contaOrigem->CpfCnpj,
                'dataPagamento' => null,
                'dataCriacao' => Date('Y-m-d H:i:s'),
                'status' => 'Gerado'
            ]);
            if($boleto) {
                return response(['Sucesso' => true, 'Mensagem' => 'O boleto foi gerado com sucesso!', 'CodBar' => $boleto->CodBar]);
            }
            else {
                return response()->json(['Sucesso' => false, 'Mensagem' => 'Ocorreram erros ao gerar o Boleto']);
            }

        }
        catch(Exception $e) {
            Log::info($e->getMessage());
            return response()->json(['Sucesso' => false, 'Mensagem' => 'Ocorreram erros ao gerar o Boleto']);
        }


    }

    public function BuscarBoleto(Request $request) {
        $validate = Validator::make($request->all(),[
            'CodeBar'         => 'required',
        ]);
        Log::info($request->all());

        if ($validate->fails()){
            return response()->json([
                'Sucesso'   => false,
                'Mensagem'  => 'Ocorreram Erros na validação dos campos enviados',
                'Campos'    => $validate->errors()
            ],400);
        }

        $boleto = Boletos::where('CodBar', $request->CodeBar)->where('status', 'Gerado')->first();
        if (!empty($boleto)) {
            return response()->json(['Sucesso' => true, 'Boleto' => $boleto]);
        } else {
            return response()->json(['Sucesso' => false, 'Mensagem' => 'Não encontrado nenhum Boleto']);
        }
    }
}
