<?php

namespace App\Traits;

trait PastTenseConverterTrait {

    private function convertToPastTense($word) {
        // If already ends with 'ed', return as is
        if (str_ends_with($word, 'ed')) {
            return $word;
        }

        // Double the final consonant if: ends with consonant + single vowel + consonant (short words)
        // e.g., tag -> tagged, plan -> planned, stop -> stopped
        if (strlen($word) <= 4 && preg_match('/[aeiou][^aeiou]$/', $word)) {
            return $word . substr($word, -1) . 'ed';
        }

        // Remove trailing 'e' before adding 'ed'
        // e.g., like -> liked (not likeed)
        if (str_ends_with($word, 'e')) {
            return $word . 'd';
        }

        // Default: just add 'ed'
        return $word . 'ed';
    }
}
