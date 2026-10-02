<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('profile.reviewer_role_heading') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('profile.reviewer_role_hint') }}</p>
    </header>

    <form method="post" action="{{ route('profile.reviewer-role.update') }}" class="mt-6">
        @csrf
        @method('put')

        <input type="hidden" name="wants_to_review" value="0">

        <label class="flex items-start gap-2 {{ $isReviewer || $reviewerRegistrationOpen ? '' : 'opacity-50' }}">
            <input type="checkbox" name="wants_to_review" value="1"
                class="mt-0.5 rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                {{ $isReviewer ? 'checked' : '' }}
                {{ $isReviewer || $reviewerRegistrationOpen ? '' : 'disabled' }}
                onchange="this.form.submit()">
            <span class="text-sm text-gray-600">{{ __('profile.reviewer_role_toggle') }}</span>
        </label>

        @if(! $reviewerRegistrationOpen && ! $isReviewer)
            <p class="mt-2 text-xs text-gray-400">{{ __('profile.reviewer_role_closed') }}</p>
        @endif
    </form>
</section>
