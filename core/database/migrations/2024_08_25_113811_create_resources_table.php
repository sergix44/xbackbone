<?php

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resources', static function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Resource::class, 'parent_id')->nullable()->comment('The parent resource ID, if any.');
            $table->string('type')->comment('The type of resource.');
            $table->foreignIdFor(User::class)->constrained('users')->comment('The user that owns the resource.');
            $table->string('code')->unique()->nullable()->comment('The unique code of the resource.');
            $table->string('name')->nullable()->comment('The name of the resource.');
            $table->boolean('is_private')->default(false)->comment('Whether the resource is hidden.');
            $table->longText('data')->nullable()->comment('The path, content, or URL of the resource.');
            $table->string('extension')->nullable()->comment('The extension of the resource, if any.');
            $table->string('filename')->nullable()->comment('The original filename of the resource.');
            $table->unsignedBigInteger('size')->nullable()->comment('The size of the resource in bytes.');
            $table->string('mime')->nullable()->comment('The MIME type of the resource.');
            $table->string('preview_type')->nullable()->comment('The type of preview for the resource if any.');
            $table->string('preview_extension')->nullable()->comment('The extension of the preview for the resource if any.');
            $table->unsignedBigInteger('views')->default(0)->comment('The number of views of the resource.');
            $table->unsignedBigInteger('downloads')->default(0)->comment('The number of downloads of the resource.');
            $table->string('fingerprint')->nullable()->comment('The fingerprint of the resource.');
            $table->string('password')->nullable()->comment('The password to access the resource.');
            $table->timestamp('published_at')->nullable()->comment('The date and time the resource was published.');
            $table->timestamp('expires_at')->nullable()->comment('The date and time the resource expires.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
