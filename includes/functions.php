<?php
// ===== توابع کمکی =====

function getLanguageDir($langCode) {
    global $languages;
    return $languages[$langCode]['dir'] ?? 'rtl';
}

function getActiveLanguages() {
    global $languages;
    return array_filter($languages, function($lang) {
        return $lang['active'] ?? false;
    });
}

function formatRate($rate, $unit = 'rial') {
    if ($unit === 'toman') {
        $rate = $rate / 10000;
    }
    return number_format($rate);
}

function getCurrencySymbol($code) {
    $symbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'CHF' => '₣',
        'CAD' => 'C$', 'AED' => 'د.إ', 'TRY' => '₺', 'JPY' => '¥',
        'CNY' => '¥', 'AUD' => 'A$', 'SAR' => '﷼', 'RUB' => '₽'
    ];
    return $symbols[$code] ?? $code;
}

function isRTL($langCode) {
    global $languages;
    return ($languages[$langCode]['dir'] ?? 'rtl') === 'rtl';
}

function getLanguageFlag($langCode) {
    global $languages;
    return $languages[$langCode]['flag'] ?? '🏳️';
}
?>