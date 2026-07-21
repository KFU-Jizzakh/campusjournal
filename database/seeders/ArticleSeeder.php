<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('email', 'author@globalcampus.local')->first();
        $author2 = User::where('email', 'author2@globalcampus.local')->first();
        $section = User::where('email', 'section@globalcampus.local')->first();
        $eic = User::where('email', 'galimov@globalcampus.local')->first();

        $categories = Category::orderBy('sort_order')->get();
        $issue = Issue::where('number', 1)->where('year', 2026)->first();

        // Получаем Author-профили
        $authorProfile = Author::firstOrCreate(
            ['email' => 'author@globalcampus.local'],
            [
                'user_id' => $author?->id,
                'full_name' => 'Автор Тестовый',
                'organization' => 'Филиал КФУ в г. Джизаке',
            ]
        );

        $author2Profile = Author::firstOrCreate(
            ['email' => 'author2@globalcampus.local'],
            [
                'user_id' => $author2?->id,
                'full_name' => 'Исследователь Тестовый',
                'organization' => 'Филиал КФУ в г. Джизаке',
            ]
        );

        // Дополнительные авторы для соавторства (created by AuthorSeeder)
        $galimov = Author::where('full_name', 'Галимов Алмаз Мирзанурович')->firstOrFail();
        $kolesnkova = Author::where('full_name', 'Колесникова Галина Ивановна')->firstOrFail();
        $shagaeva = Author::where('full_name', 'Шагаева Алия Юнусовна')->firstOrFail();
        $muhamedova = Author::where('full_name', 'Мухамедова Дилноза Рахимовна')->firstOrFail();
        $karimov = Author::where('full_name', 'Каримов Бахтиёр Шавкатович')->firstOrFail();
        $petrova = Author::where('full_name', 'Петрова Елена Сергеевна')->firstOrFail();
        $tashmatov = Author::where('full_name', 'Ташматов Абдулла Ильясович')->firstOrFail();
        $hasanova = Author::where('full_name', 'Хасанова Нилуфар Бахромовна')->firstOrFail();
        $sidorov = Author::where('full_name', 'Сидоров Андрей Владимирович')->firstOrFail();

        $articles = [
            // 1. draft
            [
                'title' => 'Черновик: Роль русского языка в подготовке инженерных кадров',
                'abstract_ru' => 'В статье рассматривается роль русского языка как инструмента профессиональной подготовки инженеров в зарубежных филиалах российских вузов. Анализируются проблемы перехода от РКИ к языку специальности.',
                'abstract_en' => 'The article examines the role of the Russian language as an instrument for professional training of engineers at foreign branches of Russian universities.',
                'status' => 'draft',
                'submitted_by' => $author?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$authorProfile, $karimov],
            ],
            // 2. submitted (без SE)
            [
                'title' => 'Опыт адаптации учебных программ по физике для узбекских студентов',
                'abstract_ru' => 'Описывается методика адаптации курса общей физики для студентов Филиала КФУ в Джизаке. Рассмотрены приёмы визуализации, двуязычные глоссарии и контроль понимания.',
                'abstract_en' => 'The study describes the methodology for adapting the general physics course for students of the KFU branch in Jizzakh.',
                'status' => 'submitted',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(10),
                'category_id' => $categories->where('name', 'Методическая копилка')->first()?->id,
                'authors' => [$authorProfile, $sidorov],
            ],
            // 3. submitted (SE назначен)
            [
                'title' => 'Кросс-культурная коммуникация в условиях филиала: вызовы и решения',
                'abstract_ru' => 'Статья посвящена анализу барьеров межкультурной коммуникации, возникающих в образовательном процессе филиала российского вуза за рубежом. Предложены практические рекомендации.',
                'abstract_en' => 'The article analyses intercultural communication barriers arising in the educational process at foreign branches of Russian universities.',
                'status' => 'submitted',
                'submitted_by' => $author2?->id,
                'submitted_at' => now()->subDays(7),
                'editor_id' => $section?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$author2Profile, $hasanova, $muhamedova],
            ],
            // 4. in_review
            [
                'title' => 'Олимпиадное движение как инструмент мотивации к изучению русского языка',
                'abstract_ru' => 'Рассматривается опыт организации предметных олимпиад на русском языке в филиалах. Показано влияние олимпиад на мотивацию студентов и качество владения языком.',
                'abstract_en' => 'The paper examines the experience of organizing subject olympiads in Russian at branch campuses and their impact on student motivation.',
                'status' => 'in_review',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(20),
                'editor_id' => $section?->id,
                'category_id' => $categories->where('name', 'Русский язык в мире науки')->first()?->id,
                'authors' => [$authorProfile, $shagaeva],
            ],
            // 5. revision
            [
                'title' => 'Интервью с директором Филиала КФУ в Джизаке о стратегии развития',
                'abstract_ru' => 'Интервью раскрывает стратегические приоритеты развития Филиала КФУ в Джизаке: новые программы, сотрудничество с местным бизнесом, цифровая трансформация.',
                'abstract_en' => 'The interview reveals strategic development priorities of the KFU branch in Jizzakh.',
                'status' => 'revision',
                'submitted_by' => $author2?->id,
                'submitted_at' => now()->subDays(30),
                'editor_id' => $section?->id,
                'decision' => 'revision',
                'decision_comments' => 'Необходимо дополнить материал конкретными цифрами и показателями, а также уточнить источники данных о трудоустройстве выпускников.',
                'decided_at' => now()->subDays(5),
                'decided_by' => $section?->id,
                'category_id' => $categories->where('name', 'Практика филиала')->first()?->id,
                'authors' => [$author2Profile],
            ],
            // 6. accepted
            [
                'title' => 'Методика обучения академическому письму на русском языке для иностранных студентов',
                'abstract_ru' => 'Представлена авторская методика поэтапного обучения академическому письму: от реферата к научной статье. Включены результаты апробации в Филиале КФУ.',
                'abstract_en' => 'The article presents an original methodology for teaching academic writing in Russian to international students.',
                'status' => 'accepted',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(45),
                'editor_id' => $section?->id,
                'decision' => 'accept',
                'decision_comments' => 'Статья соответствует требованиям и рекомендуется к публикации в ближайшем выпуске.',
                'decided_at' => now()->subDays(3),
                'decided_by' => $section?->id,
                'category_id' => $categories->where('name', 'Методическая копилка')->first()?->id,
                'authors' => [$authorProfile, $petrova],
            ],
            // 7. rejected
            [
                'title' => 'Сравнительный анализ учебных планов филиалов российских вузов',
                'abstract_ru' => 'Проведён формальный анализ учебных планов трёх филиалов. Выявлены различия в подходах к распределению часов и содержанию курсов.',
                'abstract_en' => 'A formal analysis of curricula from three branches of Russian universities is presented.',
                'status' => 'rejected',
                'submitted_by' => $author2?->id,
                'submitted_at' => now()->subDays(40),
                'editor_id' => $section?->id,
                'decision' => 'reject',
                'decision_comments' => 'Рецензенты отметили существенные методологические недостатки и отсутствие новизны. Рекомендуется переработать исследование.',
                'decided_at' => now()->subDays(10),
                'decided_by' => $section?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$author2Profile, $tashmatov],
            ],
            // 8. published
            [
                'title' => 'Русский язык как язык науки: исторический контекст и современные вызовы',
                'abstract_ru' => 'Обзорная статья прослеживает эволюцию русского языка как языка научной коммуникации от XVIII века до наших дней. Анализируются вызовы глобализации и пути сохранения русскоязычного научного дискурса.',
                'abstract_en' => 'The review article traces the evolution of the Russian language as a language of scientific communication from the 18th century to the present day.',
                'body' => '<h2>Введение</h2><p>Русский язык на протяжении более двух столетий являлся одним из ключевых языков мировой научной коммуникации. Начиная с трудов М.В. Ломоносова, русскоязычная наука внесла значительный вклад в развитие математики, физики, химии, биологии и гуманитарных дисциплин.</p><h2>Исторический контекст</h2><p>В XVIII–XIX веках русский научный язык формировался под влиянием латинской и немецкой академических традиций. К середине XX века русский стал вторым по значимости языком научных публикаций в мире, уступая лишь английскому.</p><h2>Современные вызовы</h2><p>Глобализация и доминирование английского языка в научной коммуникации ставят перед русскоязычным академическим сообществом ряд вызовов: снижение доли публикаций на русском языке в международных базах данных, необходимость билингвального обучения, сохранение научной терминологии.</p><h2>Заключение</h2><p>Несмотря на вызовы, русский язык сохраняет свой потенциал как язык науки, особенно в сфере образования и научного сотрудничества на постсоветском пространстве и в странах-партнёрах.</p>',
                'status' => 'published',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(60),
                'published_at' => now()->subDays(1),
                'editor_id' => $section?->id,
                'issue_id' => $issue?->id,
                'doi' => '10.12345/gcru.2026.1.001',
                'keywords' => ['русский язык', 'язык науки', 'научная коммуникация', 'глобализация', 'история науки'],
                'decision' => 'accept',
                'decision_comments' => 'Отличная обзорная статья, рекомендуется к публикации.',
                'decided_at' => now()->subDays(15),
                'decided_by' => $eic?->id,
                'copyedited_at' => now()->subDays(10),
                'copyedited_by' => $section?->id,
                'production_at' => now()->subDays(3),
                'production_by' => $eic?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$authorProfile, $galimov, $kolesnkova],
            ],
            // 9. published — вторая статья в выпуске
            [
                'title' => 'Цифровые технологии в преподавании РКИ: опыт филиала КФУ',
                'abstract_ru' => 'Рассматривается применение цифровых образовательных платформ и инструментов в преподавании русского языка как иностранного. Представлен опыт внедрения LMS Moodle, интерактивных тренажёров и видеоконференций в учебный процесс филиала.',
                'abstract_en' => 'The article discusses the use of digital educational platforms and tools in teaching Russian as a foreign language. The experience of implementing LMS Moodle, interactive trainers and video conferencing is presented.',
                'body' => '<h2>Введение</h2><p>Цифровая трансформация образования — один из ключевых трендов последнего десятилетия. Для филиалов российских вузов за рубежом внедрение цифровых технологий имеет особое значение, поскольку позволяет компенсировать ограниченность ресурсов и обеспечить доступ к качественным учебным материалам.</p><h2>Платформы и инструменты</h2><p>В Филиале КФУ в Джизаке активно используются следующие инструменты: LMS Moodle для организации самостоятельной работы, интерактивные тренажёры для отработки грамматических навыков, видеоконференции для проведения совместных занятий с головным вузом.</p><h2>Результаты апробации</h2><p>За два года использования цифровых инструментов уровень владения русским языком у студентов первого курса повысился в среднем на 15%. Студенты отмечают удобство доступа к материалам и возможность индивидуального темпа обучения.</p><h2>Выводы</h2><p>Цифровые технологии являются эффективным дополнением к традиционным методам преподавания РКИ и позволяют значительно повысить качество языковой подготовки студентов филиала.</p>',
                'status' => 'published',
                'submitted_by' => $author2?->id,
                'submitted_at' => now()->subDays(55),
                'published_at' => now()->subDays(1),
                'editor_id' => $section?->id,
                'issue_id' => $issue?->id,
                'doi' => '10.12345/gcru.2026.1.002',
                'keywords' => ['цифровые технологии', 'РКИ', 'Moodle', 'дистанционное обучение', 'образовательные платформы'],
                'decision' => 'accept',
                'decision_comments' => 'Актуальная тема, практико-ориентированный подход. Рекомендуется к публикации.',
                'decided_at' => now()->subDays(14),
                'decided_by' => $eic?->id,
                'copyedited_at' => now()->subDays(8),
                'copyedited_by' => $section?->id,
                'production_at' => now()->subDays(2),
                'production_by' => $eic?->id,
                'category_id' => $categories->where('name', 'Методическая копилка')->first()?->id,
                'authors' => [$author2Profile, $karimov, $hasanova],
            ],
            // 10. published — третья статья
            [
                'title' => 'Формирование межкультурной компетенции студентов в условиях академической мобильности',
                'abstract_ru' => 'Исследуются подходы к формированию межкультурной компетенции у студентов, обучающихся по программам академической мобильности. Описана модель тренинга, апробированная в филиале КФУ.',
                'abstract_en' => 'The study explores approaches to developing intercultural competence among students in academic mobility programs.',
                'body' => '<h2>Введение</h2><p>Академическая мобильность предполагает не только перемещение студентов между образовательными учреждениями, но и их погружение в иную культурную среду. Формирование межкультурной компетенции становится необходимым условием успешного обучения.</p><h2>Модель тренинга</h2><p>Разработанная модель включает три блока: когнитивный (знания о культурных различиях), аффективный (эмпатия и толерантность) и поведенческий (навыки адаптации). Тренинг рассчитан на 36 академических часов и включает ролевые игры, анализ кейсов и рефлексивные задания.</p><h2>Результаты апробации</h2><p>Апробация модели в Филиале КФУ показала статистически значимое повышение уровня межкультурной компетенции участников по всем трём компонентам. Наибольший прогресс зафиксирован в поведенческом блоке.</p><h2>Заключение</h2><p>Предложенная модель тренинга может быть рекомендована для внедрения в программы подготовки студентов к академической мобильности.</p>',
                'status' => 'published',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(50),
                'published_at' => now()->subDays(1),
                'editor_id' => $section?->id,
                'issue_id' => $issue?->id,
                'doi' => '10.12345/gcru.2026.1.003',
                'keywords' => ['межкультурная компетенция', 'академическая мобильность', 'тренинг', 'адаптация', 'филиал КФУ'],
                'decision' => 'accept',
                'decision_comments' => 'Качественное исследование с практическими результатами.',
                'decided_at' => now()->subDays(12),
                'decided_by' => $section?->id,
                'copyedited_at' => now()->subDays(7),
                'copyedited_by' => $section?->id,
                'production_at' => now()->subDays(2),
                'production_by' => $section?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$authorProfile, $shagaeva, $muhamedova],
            ],
            // 11. submitted — новая подача от автора
            [
                'title' => 'Роль студенческих научных кружков в адаптации иностранных студентов',
                'abstract_ru' => 'Анализируется влияние студенческих научных объединений на процесс социокультурной и академической адаптации иностранных студентов в российских вузах и их филиалах.',
                'abstract_en' => 'The article analyzes the influence of student research groups on the socio-cultural and academic adaptation of international students.',
                'status' => 'submitted',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(3),
                'category_id' => $categories->where('name', 'Студенческий взгляд')->first()?->id,
                'authors' => [$authorProfile],
            ],
            // 12. in_review — ещё одна на рецензировании
            [
                'title' => 'Методы оценки эффективности двуязычного обучения в техническом вузе',
                'abstract_ru' => 'Предлагается система критериев и индикаторов для оценки результативности билингвального образования в инженерных программах. Описана методика мониторинга языковых компетенций студентов.',
                'abstract_en' => 'The paper proposes a system of criteria and indicators for assessing the effectiveness of bilingual education in engineering programs.',
                'status' => 'in_review',
                'submitted_by' => $author2?->id,
                'submitted_at' => now()->subDays(15),
                'editor_id' => $section?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$author2Profile, $petrova, $sidorov],
            ],
            // 13. draft — ещё один черновик
            [
                'title' => 'Рецензия на монографию «Русский язык в научном пространстве Центральной Азии»',
                'abstract_ru' => 'Представлена рецензия на коллективную монографию, посвящённую функционированию русского языка в научно-образовательном пространстве стран Центральной Азии.',
                'status' => 'draft',
                'submitted_by' => $author2?->id,
                'category_id' => $categories->where('name', 'Рецензия на книгу')->first()?->id,
                'authors' => [$author2Profile],
            ],
            // 14. accepted — ожидает публикации
            [
                'title' => 'Психологические аспекты обучения на неродном языке: тревожность и мотивация',
                'abstract_ru' => 'Исследуется взаимосвязь языковой тревожности и учебной мотивации у студентов, обучающихся на русском языке в зарубежных филиалах. Предложены методы снижения тревожности.',
                'abstract_en' => 'The study examines the relationship between language anxiety and academic motivation among students studying in Russian at foreign branches.',
                'status' => 'accepted',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(35),
                'editor_id' => $section?->id,
                'decision' => 'accept',
                'decision_comments' => 'Значимое междисциплинарное исследование. Рекомендуется к публикации во втором номере.',
                'decided_at' => now()->subDays(2),
                'decided_by' => $section?->id,
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$authorProfile, $hasanova],
            ],
            // 15. copyediting — на корректуре
            [
                'title' => 'Инновационные подходы к тестированию языковой компетенции в многонациональных группах',
                'abstract_ru' => 'Предложена адаптивная модель тестирования языковой компетенции, учитывающая культурно-языковой бэкграунд студентов. Описаны результаты пилотного исследования в Филиале КФУ.',
                'abstract_en' => 'An adaptive model of language competence testing is proposed that accounts for the cultural and linguistic background of students.',
                'status' => 'copyediting',
                'submitted_by' => $author2?->id,
                'submitted_at' => now()->subDays(50),
                'editor_id' => $section?->id,
                'decision' => 'accept',
                'decision_comments' => 'Статья представляет практическую ценность. Рекомендуется к публикации после редакторской правки.',
                'decided_at' => now()->subDays(10),
                'decided_by' => $section?->id,
                'copyedited_at' => now()->subDays(3),
                'copyedited_by' => $section?->id,
                'category_id' => $categories->where('name', 'Методическая копилка')->first()?->id,
                'authors' => [$author2Profile, $karimov],
            ],
            // 16. production — в производстве
            [
                'title' => 'Роль наставничества в адаптации молодых преподавателей филиала',
                'abstract_ru' => 'Анализируется программа наставничества для молодых преподавателей Филиала КФУ в Джизаке. Описаны этапы адаптации, формы поддержки и результаты первых двух лет реализации.',
                'abstract_en' => 'The article analyzes the mentoring program for young instructors at the KFU branch in Jizzakh.',
                'status' => 'production',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(55),
                'editor_id' => $section?->id,
                'decision' => 'accept',
                'decision_comments' => 'Актуальная тема, рекомендована к публикации в ближайшем выпуске.',
                'decided_at' => now()->subDays(12),
                'decided_by' => $eic?->id,
                'copyedited_at' => now()->subDays(5),
                'copyedited_by' => $section?->id,
                'production_at' => now()->subDays(2),
                'production_by' => $eic?->id,
                'category_id' => $categories->where('name', 'Практика филиала')->first()?->id,
                'authors' => [$authorProfile, $shagaeva, $petrova],
            ],
            // 17. submitted — double-blind with anonymised manuscript
            [
                'title' => 'Эффективность адаптивных образовательных технологий в инженерном образовании',
                'abstract_ru' => 'Исследуется влияние адаптивных образовательных технологий на успеваемость и мотивацию студентов инженерных специальностей. Приводятся данные трёхлетнего эксперимента в Филиале КФУ.',
                'abstract_en' => 'The study investigates the impact of adaptive educational technologies on the performance and motivation of engineering students.',
                'status' => 'submitted',
                'submitted_by' => $author?->id,
                'submitted_at' => now()->subDays(1),
                'editor_id' => $section?->id,
                'review_type' => 'double_blind',
                'category_id' => $categories->where('name', 'Научная секция')->first()?->id,
                'authors' => [$authorProfile, $tashmatov, $sidorov],
                'blinded' => true,
            ],
        ];

        foreach ($articles as $data) {
            $authorsList = $data['authors'];
            $needsBlindedPdf = $data['blinded'] ?? false;
            unset($data['authors'], $data['blinded']);

            $article = Article::firstOrCreate(
                ['title' => $data['title']],
                $data
            );

            // Привязка авторов с порядком
            foreach ($authorsList as $order => $authorModel) {
                if ($authorModel && ! $article->authors()->where('author_id', $authorModel->id)->exists()) {
                    $article->authors()->attach($authorModel->id, ['order' => $order + 1]);
                }
            }

            // Генерация PDF для опубликованных статей
            if ($article->status === ArticleStatus::Published && ! $article->pdf_path) {
                $pdfPath = 'submissions/'.$article->id.'.pdf';
                Storage::disk('public')->put($pdfPath, $this->generateFakePdf($article));
                $article->update(['pdf_path' => $pdfPath]);
            }

            // Генерация анонимизированного PDF для статей с двойным слепым рецензированием
            if ($needsBlindedPdf && ! $article->blinded_pdf_path) {
                $blindedPath = 'submissions/'.$article->id.'_blinded.pdf';
                Storage::disk('local')->put($blindedPath, $this->generateBlindedFakePdf($article));
                $article->update([
                    'blinded_pdf_path' => $blindedPath,
                    'blinded_at' => now(),
                    'blinded_by' => $section?->id,
                ]);
            }
        }
    }

    private function generateFakePdf(Article $article): string
    {
        $title = $article->title;
        $authors = $article->authors->pluck('full_name')->join(', ');
        $abstract = $article->abstract_ru ?? '';
        $doi = $article->doi ?? '';

        $content = implode("\n", array_filter([
            $title,
            '',
            $authors,
            '',
            $doi ? "DOI: {$doi}" : null,
            '',
            'Аннотация',
            $abstract,
            '',
            'Текст статьи',
            '',
            strip_tags($article->body ?? 'Содержание статьи доступно в электронной версии журнала.'),
        ]));

        return $this->buildMinimalPdf($content);
    }

    private function generateBlindedFakePdf(Article $article): string
    {
        $content = implode("\n", [
            $article->title,
            '',
            '[Анонимизировано для двойного слепого рецензирования]',
            '',
            'Аннотация',
            $article->abstract_ru ?? '',
            '',
            'Текст статьи',
            '',
            '[Содержание статьи скрыто для двойного слепого рецензирования]',
        ]);

        return $this->buildMinimalPdf($content);
    }

    private function buildMinimalPdf(string $content): string
    {
        $stream = $this->pdfEncodeStream($content);
        $streamLen = strlen($stream);

        $objects = [];
        $offsets = [];

        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj";
        $objects[4] = "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";

        $pdf = "%PDF-1.4\n";

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function pdfEncodeStream(string $text): string
    {
        $lines = explode("\n", $text);
        $commands = "BT\n/F1 11 Tf\n";
        $y = 800;

        foreach ($lines as $line) {
            if ($y < 40) {
                break;
            }
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $commands .= "1 0 0 1 40 {$y} Tm\n({$escaped}) Tj\n";
            $y -= 16;
        }

        $commands .= 'ET';

        return $commands;
    }
}
