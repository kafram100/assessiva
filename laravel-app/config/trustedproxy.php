<?php

$proxies = array_values(array_filter(array_map(
    'trim',
    explode(',', env('TRUSTED_PROXIES', '')),
)));

if (in_array('*', $proxies, true)) {
    throw new InvalidArgumentException('TRUSTED_PROXIES must list explicit proxy addresses or CIDRs.');
}

return [
    'proxies' => $proxies ?: null,
];
