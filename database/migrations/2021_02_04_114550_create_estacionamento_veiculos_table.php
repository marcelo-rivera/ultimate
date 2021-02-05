<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEstacionamentoVeiculosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estacionamento_veiculos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('placa');
            $table->integer('id_veiculo')->unsigned();
            $table->foreign('id_veiculo')->references('id')->on('veiculos_modelos')->onDelete('cascade');
            $table->integer('id_cor')->unsigned();
            $table->foreign('id_cor')->references('id')->on('cores')->onDelete('cascade');
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
        Schema::dropIfExists('estacionamento_veiculos');
    }
}
