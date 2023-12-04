<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            // El id de cliente no es autoincremental y es unsignedBigInteger
            $table->unsignedBigInteger('client_id')->primary();

            // Otros campos
            $table->string('razon_social');
            $table->string('telefono');
            $table->string('email');

            // Campos de timestamp
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
        Schema::dropIfExists('clients');
    }
};

