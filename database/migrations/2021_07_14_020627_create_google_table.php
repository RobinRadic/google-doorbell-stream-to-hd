<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoogleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('google', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('application_name');
            $table->text('client_id');
            $table->text('client_secret');
            $table->string('project_id');
            $table->text('authorization_code')->nullable();
            $table->text('access_token')->nullable();
            $table->string('expires_in')->nullable();
            $table->text('refresh_token')->nullable();
            $table->json('scopes')->nullable();
            $table->string('token_type')->default('Bearer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('google');
    }
}
