@component('mail::message')
@if($isResponseReminder)
# {{ __('email.review_reminder_heading') }}

{{ __('email.review_reminder_instructions') }}

**{{ __('email.review_reminder_deadline') }}**
- {{ __('email.review_reminder_deadline_date') }} {{ $review->response_due_at?->format('d.m.Y') ?? '—' }}
@if($review->isResponseOverdue())
- **{{ __('email.review_reminder_status_overdue') }}**
@else
- {{ __('email.review_reminder_remaining') }} {{ $review->daysUntilResponseDue() }} {{ trans_choice('день|дня|дней', $review->daysUntilResponseDue()) }}
@endif

{{ __('email.review_reminder_action') }}

@component('mail::button', ['url' => route('reviews.index')])
{{ __('email.review_reminder_button') }}
@endcomponent

@else
# {{ __('email.review_due_heading') }}

{{ __('email.review_due_intro') }} **"{{ $review->article->title }}"**.

**{{ __('email.review_due_info') }}**
@if($review->isOverdue())
- **{{ __('email.review_due_overdue') }}** {{ $review->review_due_at?->format('d.m.Y') ?? '—' }}
- {{ __('email.review_due_overdue_by') }} {{ $review->daysOverdue() }} {{ trans_choice('день|дня|дней', $review->daysOverdue()) }}
@else
- {{ __('email.review_due_deadline') }} {{ $review->review_due_at?->format('d.m.Y') ?? '—' }}
- {{ __('email.review_due_remaining') }} {{ $review->daysUntilReviewDue() }} {{ trans_choice('день|дня|дней', $review->daysUntilReviewDue()) }}
@endif

{{ __('email.review_due_action') }}

@component('mail::button', ['url' => route('reviews.show', $review)])
{{ __('email.review_due_button') }}
@endcomponent

@endif

{{ __('email.regards') }},<br>
{{ config('app.name') }}
@endcomponent
