<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('addressable'); // Relacionamento polimórfico
            $table->string('name')->nullable()->comment('Nome do endereço');
            $table->string('zip_code', 15)->nullable()->comment('CEP');
            $table->string('street')->nullable()->comment('Rua/Avenida/Logradouro');
            $table->string('number')->nullable()->comment('Número');
            $table->string('complement')->nullable()->comment('Complemento');
            $table->string('district')->nullable()->comment('Bairro');
            $table->string('city')->nullable()->comment('Cidade');
            $table->string('country', 100)->default('Brasil')->nullable()->comment('País');
            $table->string('state', 2)->nullable()->comment('Estado');
            $table->boolean('is_default')->default(false)->comment('Endereço padrão');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
