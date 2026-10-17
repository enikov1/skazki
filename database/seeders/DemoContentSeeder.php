<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\AudioPlaybackState;
use App\Models\ColoringCategory;
use App\Models\ColoringPage;
use App\Models\ColoringProgress;
use App\Models\ColoringSubmission;
use App\Models\ColoringWorkComment;
use App\Models\ColoringWorkLike;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\InteractiveGame;
use App\Models\Role;
use App\Models\Story;
use App\Models\StoryQuizAttempt;
use App\Models\StoryQuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DemoContentSeeder extends Seeder
{
    private string $demoPassword;

    private bool $demoLoginEnabled;

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('DemoContentSeeder доступен только в local/testing.');
        }

        $configuredPassword = (string) env('DEMO_USER_PASSWORD', '');
        $this->demoLoginEnabled = $configuredPassword !== '';
        $this->demoPassword = $this->demoLoginEnabled ? $configuredPassword : Str::password(48);

        $this->call(RolePermissionSeeder::class);
        foreach (['Народные сказки', 'Приключения', 'Животные', 'Семейное чтение', 'Развитие', 'Тест'] as $genre) {
            Genre::firstOrCreate(['name' => $genre]);
        }

        $this->demoUser('demo-admin@example.test', 'Демо Администратор', 'admin');
        $editor = $this->demoUser('demo-editor@example.test', 'Демо Редактор', 'editor');
        $moderator = $this->demoUser('demo-moderator@example.test', 'Демо Модератор', 'moderator');
        $reader = $this->demoUser('demo-reader@example.test', 'Демо Читатель', 'user');

        $stories = $this->seedStories($editor);
        $articles = $this->seedArticles($editor);
        $this->seedComments($stories, $articles, $reader, $moderator);
        $this->seedAudioAndQuizzes($stories, $reader);
        $pages = $this->seedColorings();
        $this->seedColoringActivity($pages, $reader, $moderator);
        $this->seedInteractiveGames();

        $this->command?->info('Демо-контент создан. Вход для демо-пользователей '.($this->demoLoginEnabled ? 'включён переменной DEMO_USER_PASSWORD.' : 'отключён. Чтобы включить его локально, задайте DEMO_USER_PASSWORD в .env.'));
    }

    private function demoUser(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make($this->demoPassword),
            'email_verified_at' => now(),
            'password_login_enabled' => $this->demoLoginEnabled,
        ]);
        $user->roles()->syncWithoutDetaching([Role::where('slug', $role)->value('id')]);

        return $user;
    }

    /** @return array<string, Story> */
    private function seedStories(User $editor): array
    {
        $rows = [
            ['title' => 'Колобок', 'slug' => 'kolobok', 'author' => 'Демо-контент', 'genre' => 'Народные сказки', 'summary' => 'Круглый путешественник встречает новых знакомых и учится быть осторожным.', 'body' => 'На солнечном окошке остыл румяный колобок. Ему захотелось увидеть лес, и он отправился в путь. По дороге герой встретил зайца, волка и медведя. Каждый просил спеть песенку, но колобок решил не хвастаться и позвал лесных жителей путешествовать вместе.', 'age_min' => 3, 'age_max' => 7, 'reading_minutes' => 4, 'cover_path' => 'assets/covers/kolobok.jpg', 'audio_path' => 'assets/audio/kolobok.mp3'],
            ['title' => 'Три поросёнка', 'slug' => 'tri-porosenka', 'author' => 'Демо-контент', 'genre' => 'Животные', 'summary' => 'Три брата строят дома и узнают, как помогают труд и взаимовыручка.', 'body' => 'Три поросёнка решили построить себе дома. Один выбрал солому, другой — крепкие ветки, а третий не спешил и сложил стены из кирпича. Когда поднялся сильный ветер, братья собрались в самом надёжном доме и вместе придумали, как помочь соседям.', 'age_min' => 3, 'age_max' => 7, 'reading_minutes' => 6, 'cover_path' => 'assets/covers/three-pigs.jpg'],
            ['title' => 'Красная Шапочка', 'slug' => 'krasnaya-shapochka', 'author' => 'Демо-контент', 'genre' => 'Приключения', 'summary' => 'Девочка идёт через лес к бабушке и вспоминает правила безопасной прогулки.', 'body' => 'Красная Шапочка несла бабушке корзинку с пирогами. Перед дорогой мама напомнила идти по тропинке и не задерживаться. В лесу девочка встретила незнакомца, но вспомнила мамины слова и попросила помощи у лесника.', 'age_min' => 4, 'age_max' => 8, 'reading_minutes' => 5, 'cover_path' => 'assets/covers/red-riding-hood.jpg'],
            ['title' => 'Дюймовочка', 'slug' => 'dyujmovochka', 'author' => 'Демо-контент', 'genre' => 'Приключения', 'summary' => 'Маленькая героиня находит друзей и свой уютный дом.', 'body' => 'Крошечная девочка путешествовала по большому саду. На пути ей встречались разные соседи, и не каждый понимал её мечты. Дюймовочка помогла замёрзшей ласточке, а та показала ей дорогу к месту, где можно жить свободно и дружно.', 'age_min' => 5, 'age_max' => 9, 'reading_minutes' => 7, 'cover_path' => 'assets/covers/thumbelina.jpg'],
            ['title' => 'Сивка-Бурка', 'slug' => 'sivka-burka', 'author' => 'Демо-контент', 'genre' => 'Народные сказки', 'summary' => 'Иван выполняет обещание и с помощью верного коня справляется с испытанием.', 'body' => 'Иван берёг отцовское поле и не оставлял работу на полпути. За заботу ему помогал волшебный конь Сивка-Бурка. Вместе они преодолели высокую стену, а Иван понял, что смелость лучше всего работает рядом с добрым сердцем.', 'age_min' => 5, 'age_max' => 9, 'reading_minutes' => 8, 'cover_path' => 'assets/covers/sivka-burka.jpg'],
            ['title' => 'Кошкин дом', 'slug' => 'koshkin-dom', 'author' => 'Демо-контент', 'genre' => 'Животные', 'summary' => 'Соседи помогают кошке после большой неприятности.', 'body' => 'В уютном доме кошки однажды случилась беда. Пока взрослые искали решение, котята предложили позвать соседей. Вместе зверята нашли безопасное место и построили новый дом, где каждому нашёлся тёплый уголок.', 'age_min' => 3, 'age_max' => 7, 'reading_minutes' => 5, 'cover_path' => 'assets/covers/koshkin-dom.jpg'],
            ['title' => 'Лиса и скалочка', 'slug' => 'lisa-i-skalochka', 'author' => 'Демо-контент', 'genre' => 'Народные сказки', 'summary' => 'Хитрая путешественница ищет ночлег, а хозяева учатся задавать вопросы.', 'body' => 'Лиса нашла у дороги блестящую скалочку и попросилась переночевать в доме. Утром она рассказала хозяевам необычную историю. Они не стали спорить, а спокойно проверили факты — и быстро разобрались, что произошло.', 'age_min' => 4, 'age_max' => 8, 'reading_minutes' => 4, 'cover_path' => 'assets/covers/fox-rolling-pin.jpg'],
            ['title' => 'Айболит', 'slug' => 'aybolit', 'author' => 'Демо-контент', 'genre' => 'Животные', 'summary' => 'Добрый доктор отправляется на помощь зверям, которым нездоровится.', 'body' => 'Доктор Айболит получил письмо от зверей из далёкой страны. Он собрал аптечку и отправился в путь. В дороге доктор не торопился, внимательно слушал каждого пациента и помогал всем, кто нуждался в заботе.', 'age_min' => 4, 'age_max' => 8, 'reading_minutes' => 6, 'cover_path' => 'assets/covers/aybolit.jpg'],
            ['title' => 'Репка', 'slug' => 'repka', 'author' => 'Демо-контент', 'genre' => 'Народные сказки', 'summary' => 'Большое дело получается, когда каждый в семье помогает.', 'body' => 'Посадил дед репку, и выросла она большая-пребольшая. Позвал дед бабушку, внучку, собаку и кошку. Все взялись за дело дружно, а мышка напомнила: даже маленькая помощь бывает очень важной.', 'age_min' => 3, 'age_max' => 6, 'reading_minutes' => 3, 'cover_path' => 'assets/covers/repka.jpg'],
            ['title' => 'Маша и медведь', 'slug' => 'masha-i-medved', 'author' => 'Демо-контент', 'genre' => 'Приключения', 'summary' => 'Маша находит дорогу домой благодаря смекалке и поддержке друзей.', 'body' => 'Маша заблудилась в лесу и увидела домик медведя. Она придумала, как передать родным весточку, а лесные птицы помогли найти знакомую тропу. Медведь тоже понял, что гостя лучше проводить домой.', 'age_min' => 4, 'age_max' => 8, 'reading_minutes' => 5, 'cover_path' => 'assets/covers/masha.jpg'],
            ['title' => 'Мойдодыр', 'slug' => 'moydodyr', 'author' => 'Демо-контент', 'genre' => 'Развитие', 'summary' => 'Весёлая история напоминает о привычках чистоты и заботе о себе.', 'body' => 'Однажды щётки и полотенца отправились искать хозяина, который забыл умыться. Они показали ему, что чистота помогает чувствовать себя бодро. С тех пор утро начиналось с воды, полотенца и хорошего настроения.', 'age_min' => 3, 'age_max' => 7, 'reading_minutes' => 4, 'cover_path' => 'assets/covers/moidodyr.jpg'],
        ];

        $stories = [];
        foreach ($rows as $index => $row) {
            $published = $index < 9;
            $story = Story::updateOrCreate(['slug' => $row['slug']], $row + [
                'status' => $published ? 'published' : 'draft',
                'published_at' => $published ? now()->subDays($index + 1) : null,
                'created_by' => $editor->id,
                'updated_by' => $editor->id,
                'source_name' => 'Демо-материал для локальной разработки',
                'rights_note' => 'Вымышленный тестовый текст. Не использовать как редакционную публикацию.',
                'tags' => ['демо', mb_strtolower($row['genre'])],
                'seo_title' => $row['title'].' — демо-каталог',
                'seo_description' => $row['summary'],
            ]);
            $stories[$row['slug']] = $story;
        }

        return $stories;
    }

    /** @return array<string, Article> */
    private function seedArticles(User $editor): array
    {
        $rows = [
            ['title' => 'Как читать сказки вместе', 'slug' => 'kak-chitat-skazki-vmeste', 'category' => 'Семейное чтение', 'summary' => 'Несколько простых идей для тёплого семейного чтения.', 'body' => 'Выберите удобное время и короткую историю. Остановитесь, если ребёнок хочет обсудить героя. Не превращайте чтение в проверку: важнее совместное внимание и удовольствие от разговора.', 'reading_minutes' => 4, 'cover_path' => 'assets/illustrations/family-reading.webp'],
            ['title' => 'Спокойный вечерний ритуал', 'slug' => 'spokoyny-vecherniy-ritual', 'category' => 'Семейное чтение', 'summary' => 'Как сделать переход ко сну предсказуемым и спокойным.', 'body' => 'Повторяющийся ритуал помогает завершить день: приглушите свет, выберите одну короткую историю и оставьте время на объятия. Подстраивайте длительность под настроение и возраст ребёнка.', 'reading_minutes' => 3, 'cover_path' => 'assets/illustrations/family-reading.webp'],
            ['title' => 'Игры для внимания без экрана', 'slug' => 'igry-dlya-vnimaniya', 'category' => 'Развитие', 'summary' => 'Небольшие игры-наблюдения, которые легко придумать дома.', 'body' => 'Попросите найти предметы одного цвета, вспомнить последовательность звуков или продолжить рассказ по картинке. Начинайте с короткого раунда и меняйте правила вместе с ребёнком.', 'reading_minutes' => 5, 'cover_path' => 'assets/illustrations/logic-puzzles.webp'],
            ['title' => 'Как выбрать историю по возрасту', 'slug' => 'kak-vybrat-istoriyu', 'category' => 'Развитие', 'summary' => 'Ориентируйтесь на интерес ребёнка и удобную длину текста.', 'body' => 'Возрастные рекомендации помогают начать поиск, но не заменяют наблюдение. Кому-то нравятся повторяющиеся фразы, а кому-то — новые слова и длинные приключения. Позвольте ребёнку выбирать.', 'reading_minutes' => 4, 'cover_path' => 'assets/illustrations/story-workshop.webp'],
            ['title' => 'Почему полезно возвращаться к любимой сказке', 'slug' => 'vozvrashchatsya-k-lyubimoy-skazke', 'category' => 'Семейное чтение', 'summary' => 'Повторное чтение помогает замечать новые детали и чувства героев.', 'body' => 'Знакомая история даёт ощущение предсказуемости. При повторном чтении можно обсудить поступки героев, вспомнить любимый момент и придумать другой финал.', 'reading_minutes' => 3, 'cover_path' => 'assets/illustrations/family-reading.webp'],
        ];

        $articles = [];
        foreach ($rows as $index => $row) {
            $published = $index < 4;
            $article = Article::updateOrCreate(['slug' => $row['slug']], $row + [
                'status' => $published ? 'published' : 'review',
                'published_at' => $published ? now()->subDays($index + 1) : null,
                'created_by' => $editor->id,
                'updated_by' => $editor->id,
                'seo_title' => $row['title'],
                'seo_description' => $row['summary'],
            ]);
            $articles[$row['slug']] = $article;
        }

        return $articles;
    }

    /** @param array<string, Story> $stories @param array<string, Article> $articles */
    private function seedComments(array $stories, array $articles, User $reader, User $moderator): void
    {
        $story = $stories['kolobok'];
        Comment::updateOrCreate(['story_id' => $story->id, 'user_id' => $reader->id, 'body' => 'Ребёнку понравились встречи героя с лесными зверями.'], [
            'status' => 'approved', 'likes_count' => 0, 'dislikes_count' => 0, 'moderated_by' => $moderator->id, 'moderated_at' => now()->subHours(2),
        ]);
        Comment::updateOrCreate(['story_id' => $stories['tri-porosenka']->id, 'user_id' => $reader->id, 'body' => 'Хорошо бы добавить ещё одну иллюстрацию.'], [
            'status' => 'pending', 'likes_count' => 0, 'dislikes_count' => 0, 'moderated_by' => null, 'moderated_at' => null,
        ]);

        $article = $articles['kak-chitat-skazki-vmeste'];
        ArticleComment::updateOrCreate(['article_id' => $article->id, 'user_id' => $reader->id, 'body' => 'Попробовали читать после ужина — получилось спокойно и приятно.'], [
            'status' => 'approved', 'likes_count' => 0, 'dislikes_count' => 0, 'moderated_by' => $moderator->id, 'moderated_at' => now()->subHour(),
        ]);
        ArticleComment::updateOrCreate(['article_id' => $articles['igry-dlya-vnimaniya']->id, 'user_id' => $reader->id, 'body' => 'Добавьте, пожалуйста, ещё примеры для прогулки.'], [
            'status' => 'pending', 'likes_count' => 0, 'dislikes_count' => 0, 'moderated_by' => null, 'moderated_at' => null,
        ]);
        $story->ratings()->updateOrCreate(['user_id' => $reader->id], ['rating' => 5]);
        $article->ratings()->updateOrCreate(['user_id' => $reader->id], ['rating' => 4]);
    }

    /** @param array<string, Story> $stories */
    private function seedAudioAndQuizzes(array $stories, User $reader): void
    {
        $audioStories = collect([$stories['kolobok'], $stories['tri-porosenka'], $stories['krasnaya-shapochka']]);
        foreach ($audioStories as $position => $story) {
            if ($story->slug !== 'kolobok') {
                $story->forceFill(['audio_path' => 'assets/audio/kolobok.mp3'])->save();
            }
            AudioPlaybackState::updateOrCreate(['user_id' => $reader->id, 'story_id' => $story->id], [
                'position_seconds' => 48 + $position * 25,
                'queue_position' => $position,
                'updated_at' => now()->subMinutes($position + 1),
            ]);
        }

        foreach ([$stories['kolobok'], $stories['tri-porosenka'], $stories['krasnaya-shapochka']] as $story) {
            $questions = [
                ['prompt' => 'Куда отправился герой?', 'options' => ['В лес', 'На вокзал', 'На море', 'В школу'], 'correct_option' => 0],
                ['prompt' => 'Что помогло справиться с испытанием?', 'options' => ['Дружба', 'Случайный билет', 'Новый телефон', 'Фонарик'], 'correct_option' => 0],
            ];
            foreach ($questions as $position => $question) {
                StoryQuizQuestion::updateOrCreate(['story_id' => $story->id, 'position' => $position], $question);
            }
            StoryQuizAttempt::firstOrCreate([
                'story_id' => $story->id,
                'user_id' => $reader->id,
                'score' => 1,
                'question_count' => 2,
            ], ['answers' => [0, 2]]);
        }

        $reader->readingHistories()->updateOrCreate(['story_id' => $stories['kolobok']->id], ['last_read_at' => now()->subMinutes(12), 'paragraph_position' => 2]);
        $reader->readingHistories()->updateOrCreate(['story_id' => $stories['tri-porosenka']->id], ['last_read_at' => now()->subDays(2), 'paragraph_position' => 0]);
    }

    private function seedInteractiveGames(): void
    {
        InteractiveGame::updateOrCreate(['slug' => 'lesnaya-progulka-demo'], [
            'type' => 'differences',
            'title' => 'Лесная прогулка',
            'description' => 'Сравните две картинки и найдите три изменения.',
            'image_a' => 'assets/games/differences-a.svg',
            'image_b' => 'assets/games/differences-b.svg',
            'data' => ['differences' => [
                ['x' => 0.8125, 'y' => 0.2083, 'radius' => 0.06, 'label' => 'У солнышка появилось лицо'],
                ['x' => 0.505, 'y' => 0.627, 'radius' => 0.045, 'label' => 'Изменился цвет окошка'],
                ['x' => 0.65, 'y' => 0.592, 'radius' => 0.045, 'label' => 'На дереве не хватает яблока'],
            ]],
            'is_published' => true,
        ]);
        InteractiveGame::updateOrCreate(['slug' => 'mozaika-lesnaya-polyana-demo'], [
            'type' => 'mosaics',
            'title' => 'Мозаика: лесная поляна',
            'description' => 'Соберите сказочную картинку из девяти частей.',
            'image_a' => 'assets/games/differences-a.svg',
            'image_b' => null,
            'data' => ['rows' => 3, 'columns' => 3, 'shuffle' => true],
            'is_published' => true,
        ]);
    }

    /** @return array<string, ColoringPage> */
    private function seedColorings(): array
    {
        $rows = [
            ['category' => 'Сказочные герои', 'category_slug' => 'skazochnye-geroi-demo', 'description' => 'Знакомые герои для первых раскрасок.', 'cover' => 'assets/covers/kolobok.jpg', 'pages' => [['Лисёнок', 'lisenok-demo', 'fox-crane.png'], ['Колобок', 'kolobok-demo', 'kolobok.png'], ['Красная шапочка', 'krasnaya-shapochka-demo', 'little-red.png']]],
            ['category' => 'Домашние истории', 'category_slug' => 'domashnie-istorii-demo', 'description' => 'Уютные сюжеты о доме и семье.', 'cover' => 'assets/covers/koshkin-dom.jpg', 'pages' => [['Кошкин дом', 'koshkin-dom-demo', 'cat-house.png'], ['Маша и медведь', 'masha-demo', 'masha.png'], ['Три поросёнка', 'tri-porosenka-demo', 'three-pigs.png']]],
            ['category' => 'Лесные друзья', 'category_slug' => 'lesnye-druzya-demo', 'description' => 'Животные и приключения в лесу.', 'cover' => 'assets/covers/fox-and-crane.jpg', 'pages' => [['Лиса и журавль', 'lisa-i-zhuravl-demo', 'fox-crane.png'], ['Айболит', 'aybolit-demo', 'aybolit.png'], ['Сивка-Бурка', 'sivka-burka-demo', 'sivka.png']]],
            ['category' => 'Добрые сказки', 'category_slug' => 'dobrye-skazki-demo', 'description' => 'Короткие рисунки для спокойного отдыха.', 'cover' => 'assets/covers/repka.jpg', 'pages' => [['Репка', 'repka-demo', 'repka.png'], ['Мойдодыр', 'moydodyr-demo', 'moydodyr.png'], ['Дюймовочка', 'dyujmovochka-demo', 'thumbelina.png']]],
        ];

        $pages = [];
        foreach ($rows as $row) {
            $category = ColoringCategory::updateOrCreate(['slug' => $row['category_slug']], [
                'name' => $row['category'], 'description' => $row['description'], 'cover_path' => $row['cover'], 'is_published' => true,
            ]);
            foreach ($row['pages'] as [$title, $slug, $image]) {
                $pages[$slug] = $category->pages()->updateOrCreate(['slug' => $slug], [
                    'title' => $title, 'preview_path' => 'assets/colorings/'.$image, 'outline_path' => 'assets/colorings/'.$image, 'is_published' => true,
                ]);
            }
        }

        return $pages;
    }

    /** @param array<string, ColoringPage> $pages */
    private function seedColoringActivity(array $pages, User $reader, User $moderator): void
    {
        foreach (array_slice($pages, 0, 5, true) as $page) {
            $page->favorites()->updateOrCreate(['user_id' => $reader->id]);
            $page->ratings()->updateOrCreate(['user_id' => $reader->id], ['rating' => 5]);
        }
        ColoringProgress::updateOrCreate(['user_id' => $reader->id, 'coloring_page_id' => $pages['kolobok-demo']->id], [
            'strokes' => [['color' => '#f37451', 'size' => 10, 'points' => [[0.2, 0.25], [0.4, 0.4], [0.6, 0.3]]]],
        ]);

        foreach ([
            ['slug' => 'demo-approved-fox', 'page' => $pages['lisenok-demo'], 'title' => 'Лисёнок на прогулке', 'source' => 'fox-crane.png', 'status' => 'approved'],
            ['slug' => 'demo-pending-star', 'page' => $pages['repka-demo'], 'title' => 'Звёздочка для проверки', 'source' => 'simple.png', 'status' => 'pending'],
        ] as $row) {
            $path = 'coloring-submissions/'.$row['slug'].'.png';
            $source = public_path('assets/colorings/'.$row['source']);
            if (is_file($source) && ! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, file_get_contents($source));
            }
            $submission = ColoringSubmission::updateOrCreate(['slug' => $row['slug']], [
                'user_id' => $reader->id,
                'coloring_page_id' => $row['page']->id,
                'title' => $row['title'],
                'file_path' => $path,
                'status' => $row['status'],
                'guardian_consent_at' => now()->subDay(),
                'moderated_by' => $row['status'] === 'approved' ? $moderator->id : null,
                'moderated_at' => $row['status'] === 'approved' ? now()->subHours(3) : null,
            ]);
            ColoringWorkLike::firstOrCreate(['user_id' => $moderator->id, 'coloring_submission_id' => $submission->id]);
            ColoringWorkComment::updateOrCreate([
                'user_id' => $moderator->id,
                'coloring_submission_id' => $submission->id,
                'body' => 'Демо-комментарий к работе.',
            ], [
                'status' => $row['status'] === 'approved' ? 'approved' : 'pending',
                'moderated_by' => $row['status'] === 'approved' ? $moderator->id : null,
                'moderated_at' => $row['status'] === 'approved' ? now()->subHours(2) : null,
            ]);
        }

        $reader->consentEvents()->firstOrCreate([
            'consent_type' => 'child_art_publication',
            'policy_version' => 'demo-content-v1',
            'source' => 'demo-seeder',
        ], ['action' => 'accepted', 'recorded_at' => now()->subDay()]);
    }
}
