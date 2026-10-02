<?php

namespace App\Support;

/**
 * PURPOSE: Inline form action (POST) rendered inside a dashboard inbox
 * item, e.g. accepting/declining a review invitation or approving
 * galley proofs without opening the object page.
 */
readonly class InboxAction
{
    public function __construct(
        public string $label,
        public string $url,
        public string $style = 'primary',
        public ?string $confirm = null,
    ) {}
}
