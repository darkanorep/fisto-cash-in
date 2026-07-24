<?php

namespace App\Supports;

final class EmployeeId
{
    public static function build(string $prefix, string $number): string
    {
        return sprintf('%s-%s', trim($prefix), trim($number));
    }

    /**
     * Parses a route segment like "EMP-001" into [prefix, number].
     * Assumes the number itself never contains a hyphen.
     */
    public static function parse(string $employeeId): array
    {
        [$prefix, $number] = array_pad(explode('-', $employeeId, 2), 2, null);

        return [$prefix, $number];
    }
}
