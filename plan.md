# CampusJournal — Handoff Plan (продолжение работы)

## Контекст
- Репозиторий: Laravel 13 / PHP 8.3 / PostgreSQL / Pest 4.4. Правила — в AGENTS.md (обязательно прочитать): доменные исключения (final, `__()`), атрибуты Eloquent, запрет логики/enum/permission-проверок в Blade, тесты обязательны, НЕ коммитить (показывать сообщение коммита после работы).
- Тестовые пользователи (пароль `password1234`): admin@ / galimov@ (EiC) / managing@ / section@ / reviewer@ / author@ / content@ @globalcampus.local.
- Тесты: `php artisan test` (нужен PostgreSQL, см. .env.testing), стиль Pest, `beforeEach seed(RoleSeeder)`, `./vendor/bin/pint`.
- Перед стартом: `git log --oneline -8` и `git status` — сверить состояние (часть WP уже закоммичена пользователем, WP-6 может быть незакоммичен).

## Что УЖЕ сделано (не переделывать)
- Бейджи ролей в навигации + локализация ролей в Filament (`lang/ru/roles.php`, `User::roleLabel/roleBadges`).
- Task-oriented dashboard: `app/Support/InboxItem.php`, `InboxAction.php`, `DashboardInbox.php` (мемокэш `user|family` + `flush()`, `countFor()`, `activeRoleFor()`, `can($user,$perm)` толерантный к незасеянным permissions, `editorialArticles()`), `DashboardWatchlist.php` (deadlines/for), компонент `x-inbox-row`, секции «Дедлайны рецензий»/«На контроле», счётчик задач в nav (view-composer в AppServiceProvider).
- Статистика: `ReviewerStats` (map/single: active/overdue/completed/avg_days/declines_year), `EditorialStats` (funnel/avgDaysToDecision/avgReviewerTurnaround/sectionEditorLoad).
- Статьи: `Article::workflowSteps()` + `x-article-timeline` (на submissions/show и editorial/show), `Article::productionChecklist()` + `x-article-checklist`, `Article::daysInStatus()`, поиск (q) в editorial/index и «Моих статьях».
- Редакторские инструменты: карточки нагрузки в option'ах назначения (editorOptions/reviewerOptions в EditorialController), страница `/dashboard/editorial/stats` (роут ДО `{article}` wildcard), «Сборка выпуска» в DashboardController, Filament `OutboxEventResource` (admin-only, read-only).
- Соавторы: `ArticlePolicy::view` (submitter или pivot-автор не-черновика), `submissions.show` вынесен из permission-группы (policy-gated), `DiscussionPolicy::view` (submitted_by ИЛИ pivot), PDF-гейт в `ArticleController@pdf`, кликабельное «Соавторство» в дашборде.
- WP-1: reviewer self-registration (`reviewer_self_registration` Setting + Toggle SiteSettings, `User::becomeReviewer/stopBeingReviewer` + `ReviewerRoleRemovalBlockedException`, чекбокс регистрации, partial в профиле + `PUT /profile/reviewer-role`) и role switcher (`POST /dashboard/active-role`, сессионный `active_role`, select в dropdown, фильтрация семейств в билдерах — права не сужаются).
- WP-4: `DecisionLetter` (шаблоны accept/revision/reject, data-template + Alpine), bulk-assign (`editorial.bulk-assign-editor`), проверка дубликатов (`findDuplicateTitles`, similar_text ≥ 88 + нормализация + `duplicate_acknowledged`), ссылки авторов в articles/index + SVG-аватары в AuthorSeeder.
- WP-6 (может быть незакоммичен): `Filament/Pages/SystemHealth.php` (admin-only) + view, `CrossrefDeposit::statusLabel/statusColor`, `appendOutputTo` на reviews:send-reminders. Коммит: `feat(admin): system health page with queue, failed jobs and crossref status`.
- Документация: `docs/roles.md` (матрица ролей/доступов/политик + Notes).

## Оставшиеся пакеты (порядок исполнения)

### WP-3 — Assignment intelligence (4 фичи одним пакетом)
1. **interests в профиле**: миграция `profiles.interests` (jsonb); поле в форме профиля (профиль редактируется в dashboard ProfileController + Filament UserResource, секция «Профиль») — массив строк, ввод через запятую, cast 'array'.
2. **Матчинг**: `app/Support/ReviewerMatcher.php` — пересечение `Article.keywords` (cast array, raw текст) × `Profile.interests` (нормализация: lower/trim); бейдж «N совпадений» в `reviewerOptions` (EditorialController); статистика ReviewerStats не ломать (только добавлять).
3. **Оценка рецензий**: миграция `reviews.quality_rating smallint null` + `rated_by` + `rated_at`; endpoint POST rate на editorial/show (только completed-рецензии, workflow scope, по аналогии с decide); `avg_rating` — НОВЫМ ключом в ReviewerStats (docblock+тест обновить); вопрос «видит ли рецензент оценку» — не показывать.
4. **Автоназначение по рубрике**: `categories.section_editor_id` (nullable FK) + select в `CategoryResource`; в `Article::submit()` после создания: `$article->category?->section_editor_id` → выставить editor_id (молча при отсутствии маппинга; assignEditor() вызывать не обязательно — прямое поле, т.к. submit уже в транзакции).
- Тесты: `tests/Feature/AssignmentIntelligenceTest.php` (matcher scoring, rating auth+avg_rating, auto-assign с/без маппинга, интересы сохраняются).

### WP-5 — Приглашения соавторов (claim flow)
- Signed route по образцу `VerifyEmailNotification` (`URL::temporarySignedRoute(..., absolute: false)` + prefix app.url — работает за прокси): `GET /invitations/{author}/accept` (middleware `signed` + `auth` + `verified`), TTL 7 дней.
- `Author::claimFor(User $user): void` — доменный метод: pivot-snapshot email == user->email (доказательство владения) + verified email, иначе `AuthorClaimFailedException` (новый, final, lang через `__()`); перелинковка СУЩЕСТВУЮЩЕЙ строки (риск дублей Author — не создавать новую!).
- Миграция `authors.invitation_sent_at` (защита от спама при каждом resubmit).
- Триггер в `SubmissionController@store/update` после `syncAuthors`: pivot-строки соавторов, чей email = существующему verified-пользователю и `Author.user_id IS NULL` и `invitation_sent_at IS NULL/старое` → `InvitationNotification` (queued, русский MailMessage, по образцу VerifyEmailNotification).
- После клейма соавтор автоматически получает весь поток уведомлений (`Article::notifiableUsers()`).
- Тесты: `tests/Feature/CoauthorInvitationTest.php` (подписанный URL, guard email, неверный пользователь 403, throttle повторной отправки, claim ставит user_id без дублей Author).

### WP-2 — Re-review round + response letter (САМЫЙ БОЛЬШОЙ, ПОСЛЕДНИМ — его миграция трогает reviews после WP-3)
Блокеры в текущем коде (из исследования): guard в `Article::assignReviewer()` (~строка 510, `where('status','!=',Declined)`) + **partial unique index** `reviews_article_reviewer_active_unique WHERE status != 'declined'` — повторное назначение завершённого рецензента невозможно на обоих уровнях. `Completed/Declined` — терминальные статусы (`ReviewStatus::canTransitionTo`), раундов нет нигде.
1. **Миграции**: `reviews.round` (smallint default 1); пересоздать index как `(article_id, reviewer_id, round) WHERE status != 'declined'`; `articles.current_round` (smallint default 1); таблица `response_letters` (id, article_id FK, round, body text, file_path nullable, uploaded_by FK, timestamps).
2. **`Article::revise()`**: инкремент `current_round`; ОЧИСТИТЬ `blinded_pdf_path/blinded_at/blinded_by` (иначе double-blind round 2 получит устаревший слепой PDF — риск деанонимизации; существующий guard `needsBlindedPdf()` сработает при назначении).
3. **`assignReviewer()`**: разрешить повторное назначение, если прошлые рецензии этого рецензента завершены и из прежних раундов (`round < current`); `round = current_round` при создании.
4. **`canBeDecided()`/`decide()`**: completed-рецензия только текущего раунда (иначе решение по устаревшему раунду).
5. **Response letter**: при Revision-resubmit (`SubmissionController@update`) обязательное поле `response_letter` (текст textarea + опциональный файл); запись в `response_letters` в той же транзакции, `round = current_round` (новый раунд). Форма edit показывает комментарии рецензентов раунда (decision ещё не стёрт — статус Revision до resubmit).
6. **Views**: editorial/show — бейдж раунда на карточках рецензий (~383-420) + письма по раундам; reviews/show — ссылка на письмо текущего раунда + свой отзыв прошлого раунда; submissions/show — раунды на отзывах. Без логики в Blade (методы модели/контроллеры).
7. **Уведомления**: новое in-app `ReviewReRequested` (рецензенту при повторном назначении); пометка «раунд N» в `ReviewAssignedMailable`/`ReviewAssignedDoubleBlindMailable` (mail-шаблоны `resources/views/emails/reviews/`); `AuthorResubmitted` остаётся редактору.
8. **Документы**: `docs/spec-25-re-review-rounds.md` (по _spec-template.md) + примечание в AGENTS.md (workflow-семантика изменилась).
9. **Риски**: уникальный индекс — главный блокер; `revise()` стирает decision → комментарии раунда теряются для автора после resubmit (письмо собирается до); статистика (EditorialStats/ReviewerStats) включает все раунды — задокументировать как осознанное.
- Тесты: extend EditorialTest (round-2 назначение, decide после round-2 review), SubmissionTest (response letter required; ВНИМАНИЕ: существующие тесты resubmit (~строки 272, 674+, 806+, 880) слать ПОЛНЫЙ payload с response_letter — регрессионная дисциплина: править существующие тесты минимально, логику не менять), новый `tests/Feature/ReReviewRoundTest.php` (index replace, blinded clear, уведомления, письмо, guards).

## Протокол исполнения
- Пакет за раз: реализация → свой тест-файл → `php artisan test --filter=...` → полный `php artisan test` → `./vendor/bin/pint` → отчёт пользователю с паузой.
- После каждого WP — сообщение коммита (Conventional Commits, однострочное, как в git log) и список файлов. КОММИТЫ НЕ ДЕЛАТЬ.
- После ВСЕХ WP: финальный полный прогон + обновить docs/roles.md (если поведение ролей затронуто).
