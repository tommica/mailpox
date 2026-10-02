<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('mailpox.database.connection'))->create(config('mailpox.database.table'), function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('message_id')->nullable()->index();
            $table->text('subject')->nullable();
            $table->text('from');
            $table->text('to');
            $table->text('cc');
            $table->text('bcc');
            $table->longText('html')->nullable();
            $table->longText('text')->nullable();
            $table->longText('raw');
            $table->longText('headers');
            $table->boolean('read')->default(false)->index();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::connection(config('mailpox.database.connection'))->create(config('mailpox.database.attachments_table'), function (Blueprint $table): void {
            $table->string('id', 80)->primary();
            $table->string('message_id', 64);
            $table->string('filename');
            $table->string('content_type');
            $table->longText('content');
            $table->unsignedBigInteger('size');
            $table->string('disposition', 32);
            $table->string('content_id')->nullable();
            $table->timestamp('created_at');
            $table->foreign('message_id')->references('id')->on(config('mailpox.database.table'))->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection(config('mailpox.database.connection'))->dropIfExists(config('mailpox.database.attachments_table'));
        Schema::connection(config('mailpox.database.connection'))->dropIfExists(config('mailpox.database.table'));
    }
};
