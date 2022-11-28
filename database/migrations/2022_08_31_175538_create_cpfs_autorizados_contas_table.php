<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCpfsAutorizadosContasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cpfs_autorizados_contas', function (Blueprint $table) {
            $table->id();
            $table->string('cpfCnpjAutorizado')->nullable(false);
            $table->unsignedBigInteger('contaId');
            $table->foreign('contaId')->references('id')->on('contas');
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
        Schema::dropIfExists('cpfs_autorizados_contas');
    }
}
