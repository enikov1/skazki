<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_landing_redirects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('catalog_landing_page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('old_slug', 180);
            $table->timestamps();
            $table->unique(['type', 'old_slug']);
        });
        $redirects = DB::table('slug_redirects')->where('redirectable_type', \App\Models\CatalogLandingPage::class)->get();
        foreach ($redirects as $redirect) {
            $page = DB::table('catalog_landing_pages')->find($redirect->redirectable_id);
            if ($page) DB::table('catalog_landing_redirects')->insertOrIgnore([
                'catalog_landing_page_id' => $page->id, 'type' => $page->type, 'old_slug' => $redirect->old_slug,
                'created_at' => $redirect->created_at, 'updated_at' => $redirect->updated_at,
            ]);
        }
    }
    public function down(): void { Schema::dropIfExists('catalog_landing_redirects'); }
};
