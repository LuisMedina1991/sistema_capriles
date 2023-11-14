<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('details', function (Blueprint $table) {

            $table->id();
            $table->text('description',1000);
            $table->decimal('amount',10,2);
            $table->decimal('previus_balance',10,2);
            $table->decimal('actual_balance',10,2);
            $table->unsignedBigInteger('detailable_id');
            $table->string('detailable_type',45);
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
        Schema::dropIfExists('details');
    }
}
