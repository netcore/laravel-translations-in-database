<?php

use Netcore\Translator\Models\Language;
use Netcore\Translator\Models\Translation;

// In order to stay backwards compatible, we should define this
// helper function only if it is not defined in project itself
if (!function_exists('trans_model')) {
    /**
     * @param          $row
     * @param Language $language
     * @param String $attribute
     * @return String
     */
    function trans_model($row, Language $language, $attribute = null): String
    {
        if (object_get($row, 'id')) {
            if (is_null($attribute)) {
                $model = $row->translateOrNew($language->iso_code);

                if (is_object($model)) {
                    return $model;
                }
            }

            if ($row) {
                return (string)$row->translateOrNew($language->iso_code)->$attribute;
            }
        }

        return '';
    }
}

if (!function_exists('lg')) {

    /**
     * @param $key
     * @param array $replace
     * @param null $locale
     * @param $value
     * @return String
     */
    function lg($key, $replace = [], $locale = null, $value = null): String
    {
        $createTranslations = config('translations.create_translations_on_the_fly', false);
        if ($createTranslations) {

            $translations = cache('translations');
            $languages = languages();
            if (isset($locale) && !$languages->where('iso_code', $locale)->count()) {
                $value = $locale;
            }
            if (!$locale && !$value && !is_array($replace)) {
                $value = $replace;
            }
            $fallbackLanguage = $languages->where('is_fallback', 1)->first();

            if($fallbackLanguage) {
                $fallbackIsoCode = $fallbackLanguage->iso_code;
                $transKey = $fallbackIsoCode . '.' . $key;

                if (!isset($translations[$transKey])) {
                    $translations = [
                        'key' => $key,
                    ];

                    foreach ($languages->pluck('iso_code')->toArray() as $code) {
                        $translations[$code] = $value;
                    }

                    $translation = new Translation();
                    $translation->import()->process([$translations], false);

                    cache()->forget('translations');

                    if (!is_array($replace)) {
                        $replace = [];
                    }
                    $translation = $value;
                    foreach ($replace as $key => $value) {
                        $translation = str_replace(':' . $key, $value, $translation);
                    }

                    return $translation;
                }
            }
        }

        if (!is_array($replace)) {
            $replace = [];
        }

        return trans($key, $replace, $locale);
    }
}