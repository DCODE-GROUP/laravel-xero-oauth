<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xero_tokens', function (Blueprint $table) {
            $table->increments('id');

            if (! empty(config('laravel-xero-oauth.multi_tenant_model'))) {
                $table->foreignIdFor(config('laravel-xero-oauth.multi_tenant_model'), 'tenant_id')
                    ->nullable()
                    ->constrained();
            }

            $table->text('id_token');
            $table->string('token_type')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('scope')->nullable();
            $table->string('current_tenant_id')->nullable();
            $table->integer('expires')->nullable();
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
        Schema::dropIfExists('xero_tokens');
    }
};