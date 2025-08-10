<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $keys = [
            'Home',
            'Language',
            'Dark Mode',
            'Welcome',
            'Missing'
        ];

        $locales = config('app.available_locales');
        foreach ($keys as $key) {
            foreach ($locales as $locale) {
                $file = resource_path("lang/{$locale}.json");
                $value = null;
                if (file_exists($file)) {
                    $json = json_decode(file_get_contents($file), true);
                    $value = $json[$key] ?? null;
                }
                DB::table('translations')->insert([
                    'key' => $key,
                    'locale' => $locale,
                    'value' => $value,
                ]);
            }
        }
    }
}
