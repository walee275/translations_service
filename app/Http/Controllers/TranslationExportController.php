<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TranslationExportController extends Controller
{
    public function export(string $locale)
    {
        return Cache::remember(
            "translations_export_{$locale}",
            60,
            fn() => Translation::whereHas('locale', fn($q) => $q->where('code', $locale))
                ->pluck('content', 'key')
        );
    }


}
