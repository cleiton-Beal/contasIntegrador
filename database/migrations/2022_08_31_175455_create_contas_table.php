<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contas', function (Blueprint $table) {
            $table->id();
            $table->String('Nome')->nullable(false);
            $table->String('CpfCnpj')->nullable(false)->unique();
            $table->String('Telefone')->nullable(false);
            $table->String('Senha')->nullable(false);
            $table->String('DataNascimento')->nullable(false);
            $table->String('Email')->nullable(false);
            $table->String('Logradouro')->nullable(false);
            $table->String('Complemento')->nullable(false);
            $table->String('Bairro')->nullable(false);
            $table->String('Numero')->nullable(false);
            $table->String('CEP')->nullable(false);
            $table->String('Cidade')->nullable(false);
            $table->String('RendaMensal')->nullable(false);
            $table->float('Saldo')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contas');
    }
}
