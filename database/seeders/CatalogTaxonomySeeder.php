<?php

namespace Database\Seeders;

use App\Models\CatalogLandingPage;
use App\Models\CatalogLandingRedirect;
use App\Models\ColoringCategory;
use App\Models\Genre;
use App\Models\Story;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/catalog-taxonomy.json'), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($data): void {
            foreach ($data['genres'] as $genre) {
                Genre::firstOrCreate(['name' => $genre['name']]);
                foreach (['story_genre' => 'Сказки', 'audio_genre' => 'Аудиосказки'] as $type => $prefix) {
                    $this->page(['type' => $type, 'filter_value' => $genre['name']], $prefix.': '.$genre['name'], $genre['name'], $genre['description']);
                }
            }
            foreach ($data['authors'] as $author) {
                $this->page(['type' => 'author', 'filter_value' => $author], 'Произведения: '.$author, $author,
                    '[b]'.$author.'[/b]' ."\n".'Авторская подборка для детского и семейного чтения. Выбирайте произведения по возрасту и интересам ребёнка. Опубликованные тексты и доступные аудиозаписи появляются в каталоге после редакционной проверки.');
            }
            foreach ($data['ages'] as [$min, $max]) {
                foreach (['age' => 'Сказки', 'audio_age' => 'Аудиосказки'] as $type => $prefix) {
                    $title = $prefix.' для детей '.$min.'–'.$max.' лет';
                    $this->page(['type' => $type, 'age_min' => $min, 'age_max' => $max], $title, $min.'-'.$max.'-let',
                        '[b]'.$title.'[/b]' ."\n".'Возраст помогает выбрать подходящую историю, но интересы и опыт каждого ребёнка отличаются. Знакомьтесь с описанием перед чтением или прослушиванием. Делайте паузы для вопросов и обсуждения героев.');
                }
            }
            foreach ($data['coloring_categories'] as $category) {
                // Names identify the collection even if an editor changes its URL.
                if (ColoringCategory::where('name', $category['name'])->exists()) continue;
                $slug = Str::slug($category['name']);
                $base = $slug;
                for ($suffix = 2; ColoringCategory::where('slug', $slug)->exists(); $suffix++) $slug = $base.'-'.$suffix;
                ColoringCategory::create([...$category, 'slug' => $slug, 'is_published' => false]);
            }
        });
        Cache::forget('stories:genres:v1');
        $this->command?->info('Справочники созданы без перезаписи существующих записей. Пустые подборки сохранены как черновики.');
    }

    private function page(array $identity, string $title, string $base, string $description): void
    {
        if (CatalogLandingPage::where($identity)->exists()) return;
        $type = $identity['type'];
        $slug = Str::slug($base);
        $baseSlug = $slug;
        for ($suffix = 2; CatalogLandingPage::where('type', $type)->where('slug', $slug)->exists()
            || CatalogLandingRedirect::where('type', $type)->where('old_slug', $slug)->exists(); $suffix++) $slug = $baseSlug.'-'.$suffix;

        $materials = Story::published()
            ->when(in_array($type, ['story_genre', 'audio_genre'], true), fn ($q) => $q->where('genre', $identity['filter_value']))
            ->when($type === 'author', fn ($q) => $q->where('author', $identity['filter_value']))
            ->when(in_array($type, ['age', 'audio_age'], true), fn ($q) => $q->where('age_min', '<=', $identity['age_max'])->where('age_max', '>=', $identity['age_min']))
            ->when(in_array($type, ['audio_genre', 'audio_age'], true), fn ($q) => $q->whereNotNull('audio_path'));
        CatalogLandingPage::create([...$identity, 'title' => $title, 'slug' => $slug, 'description' => $description,
            'seo_title' => $title, 'seo_description' => Str::limit(strip_tags(preg_replace('/\[\/?b\]/', '', $description)), 155, '…'),
            'status' => $materials->exists() ? 'published' : 'draft']);
    }
}
