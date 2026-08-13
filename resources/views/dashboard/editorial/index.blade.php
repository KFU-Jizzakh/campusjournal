<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.editorial_heading') }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm p-4 rounded-lg">{{ session('warning') }}</div>
        @endif

        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('editorial.index') }}" class="px-3 py-1.5 rounded-full {{ !$status ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ __('dashboard.filter_all') }} {{ $counts->total }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'submitted']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'submitted' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                {{ __('dashboard.filter_new') }} {{ $counts->submitted }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'in_review']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'in_review' ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' }}">
                {{ __('dashboard.filter_in_review') }} {{ $counts->in_review }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'revision']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'revision' ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-700 hover:bg-orange-100' }}">
                {{ __('dashboard.filter_revision') }} {{ $counts->revision }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'accepted']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'accepted' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 hover:bg-green-100' }}">
                {{ __('dashboard.filter_accepted') }} {{ $counts->accepted }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'copyediting']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'copyediting' ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}">
                {{ __('dashboard.filter_copyediting') }} {{ $counts->copyediting }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'production']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'production' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}">
                {{ __('dashboard.filter_production') }} {{ $counts->production }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'rejected']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'rejected' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                {{ __('dashboard.filter_rejected') }} {{ $counts->rejected }}
            </a>
            <a href="{{ route('editorial.index', ['status' => 'published']) }}" class="px-3 py-1.5 rounded-full {{ $status === 'published' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                {{ __('dashboard.filter_published') }} {{ $counts->published }}
            </a>
        </div>

        <div class="bg-white rounded-lg border border-gray-200">
            @if($articles->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">{{ __('dashboard.no_submissions') }}</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.title_col') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('article.main_author') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.status_col') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.editor_col') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.date_col') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($articles as $article)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ Str::limit($article->title, 50) }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $article->submitter?->full_name }}</td>
                            <td class="px-5 py-3">
                                <x-status-badge :color="$article->status->color()" :label="$article->status->label()" />
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $article->editor?->full_name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-400 text-xs">{{ $article->submitted_at?->format('d.m.Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('editorial.show', $article) }}" class="text-primary hover:underline">{{ __('common.open') }}</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-5 py-3 border-t border-gray-100">{{ $articles->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
