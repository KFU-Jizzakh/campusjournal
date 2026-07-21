@extends('layouts.public')

@section('title', 'Политика Crossmark — ' . config('app.name'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-6">Политика Crossmark</h1>

            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                <p>
                    Crossmark — это сервис Crossref, который позволяет читателям проверять актуальность
                    и достоверность научных публикаций. Наличие значка Crossmark на странице статьи
                    подтверждает, что издатель поддерживает и обновляет информацию о документе.
                </p>

                <h2 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Как работает Crossmark</h2>
                <p>
                    При нажатии на кнопку Crossmark на странице статьи открывается окно со статусом документа.
                    Crossmark показывает:
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Актуальность публикации (является ли данная версия последней)</li>
                    <li>Наличие исправлений (corrigendum/erratum)</li>
                    <li>Наличие ретрекшна (отзыва статьи)</li>
                    <li>Другую связанную информацию</li>
                </ul>

                <h2 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Обновление метаданных</h2>
                <p>
                    Редакция {{ config('app.name') }} обязуется поддерживать метаданные Crossmark в актуальном
                    состоянии. При внесении исправлений (corrigendum, erratum) или отзыве статьи (retraction)
                    метаданные Crossref обновляются через повторное депонирование DOI с указанием типа обновления.
                </p>

                <h2 class="text-xl font-semibold text-gray-900 mt-6 mb-3">Домены</h2>
                <p>
                    Значок Crossmark доступен на следующих доменах:
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    @if(!empty($domains))
                        @foreach($domains as $domain)
                            <li>{{ $domain }}</li>
                        @endforeach
                    @else
                        <li>{{ parse_url(config('app.url'), PHP_URL_HOST) }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </section>
@endsection
