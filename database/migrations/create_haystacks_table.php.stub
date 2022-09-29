<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('haystacks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->text('on_then')->nullable();
            $table->text('on_catch')->nullable();
            $table->text('on_finally')->nullable();
            $table->text('on_paused')->nullable();
            $table->text('middleware')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('resume_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->text('options');
        });
    }

    public function down()
    {
        Schema::dropIfExists('haystacks');
    }
};
