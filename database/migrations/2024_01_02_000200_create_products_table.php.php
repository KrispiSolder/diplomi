<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('article')->unique();
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->enum('stock_status', ['В наличии', 'Под заказ', 'Закончился'])->default('В наличии');
            $table->string('main_image')->nullable();
            $table->integer('quantity')->default(0);
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
