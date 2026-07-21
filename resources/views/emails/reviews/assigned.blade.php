@component('mail::message')
# {{ __('email.review_assigned_heading') }}

{{ __('email.review_assigned_instructions') }}

**{{ __('email.review_assigned_info') }}**
- {{ __('email.review_assigned_date') }} {{ $review->assigned_at?->format('d.m.Y') ?? '—' }}
- {{ __('email.review_assigned_deadline') }} {{ $review->response_due_at?->format('d.m.Y') ?? '—' }}
- {{ __('email.review_assigned_duration') }} {{ $review->review_due_at?->format('d.m.Y') ?? '—' }}

@component('mail::button', ['url' => route('reviews.index')])
{{ __('email.review_assigned_button') }}
@endcomponent

{{ __('email.regards') }},<br>
{{ config('app.name') }}
@endcomponent
