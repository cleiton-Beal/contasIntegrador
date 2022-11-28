<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtratosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extratos', function (Blueprint $table) {
            $table->id();
            $table->String('idTransacao')->nullable(false);
            $table->unsignedBigInteger('conta')->nullable(false);
            $table->foreign('conta')->references('id')->on('contas');
            $table->float('valor')->nullable(false);
            $table->String('nomeOrigem')->nullable(false);
            $table->String('tipoTransacao')->nullable(false);
            $table->String('nomeDestino')->nullable(true);
            $table->string('contaOrigem')->nullable(false);
            $table->string('contaDestino')->nullable(false);
            $table->string('Status')->nullable(false);
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
        Schema::dropIfExists('extratos');
    }
}
