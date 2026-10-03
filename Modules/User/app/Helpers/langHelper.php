<?php

use Modules\Home\app\Models\Language;

if (!function_exists('__t')) {
    function __t($key, $lang = null, $replace = []) {
        $lang = $lang ?? (auth()->user()->language ?? 'en');
        $column = $lang === 'is' ? 'is_language' : 'en_language';
        
        $translation = Language::where('origin_text', $key)->value($column);

        if (!$translation) {
            return $key; // fallback to key
        }

        foreach ($replace as $search => $value) {
            $translation = str_replace(':'.$search, $value, $translation);
        }

        return $translation;
    }
}
