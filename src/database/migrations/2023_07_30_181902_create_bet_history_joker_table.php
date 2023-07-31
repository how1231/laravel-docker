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
        Schema::create('bet_history_joker', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('currency');
            $table->string('action');
            $table->bigInteger('transaction_id')->unique();
            $table->string('bet_id')->unique();
            $table->string('round_id');
            $table->string('provider_appid')->nullable();
            $table->string('provider_hash')->nullable();
            $table->string('provider_username')->nullable();
            $table->string('provider_timestamp')->nullable();
            $table->string('provider_id')->nullable();
            $table->double('provider_amount', 8, 2)->nullable();
            $table->string('provider_gamecode')->nullable();
            $table->string('provider_roundid')->nullable();
            $table->string('provider_description')->nullable();
            $table->string('provider_type')->nullable();
            $table->string('provider_betid')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->index(['bet_id']);
            $table->index(['action', 'round_id']);
            $table->index(['bet_id', 'round_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bet_history_joker');
    }
};
