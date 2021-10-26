<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chatId');
            $table->foreign('chatId')->references('id')->on('chat_rooms');

            $table->unsignedBigInteger('sender');
            $table->foreign('sender')->references('id')->on('users');

            $table->text('body');
            $table->timestamps();

            $table->unsignedBigInteger('file');
            $table->foreign('file')->references('id')->on('files');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats');
    }
}
