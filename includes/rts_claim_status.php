<?php

declare(strict_types=1);

const RTS_CLAIM_STATUSES = [
    'for_submission',
    'denied_claims',
    'approved_claims',
    'submitted_claims',
    'on_process',
];

function rts_claim_status_label(string $code): string
{
    return match ($code) {
        'for_submission' => 'For submission',
        'denied_claims' => 'Denied claims',
        'approved_claims' => 'Approved claims',
        'submitted_claims' => 'Submitted claims',
        'on_process' => 'On process',
        default => ucwords(str_replace('_', ' ', $code)),
    };
}

/** @return list<array{value:string,label:string}> */
function rts_claim_status_options(): array
{
    $out = [];
    foreach (RTS_CLAIM_STATUSES as $code) {
        $out[] = ['value' => $code, 'label' => rts_claim_status_label($code)];
    }

    return $out;
}

function rts_claim_status_valid(string $code): bool
{
    return in_array($code, RTS_CLAIM_STATUSES, true);
}
