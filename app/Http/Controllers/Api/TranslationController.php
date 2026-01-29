<?php

namespace App\Http\Controllers\Api;

use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranslationController extends Controller
{
    public function index(Request $request)
    {

        try {

            return Cache::remember(
                'translations:' . md5(request()->fullUrl()),
                15,
                function () use ($request) {
                    $query = Translation::query()
                        ->select([
                            'translations.id',
                            'translations.key',
                            'translations.content',
                            'locales.code as locale',
                        ])
                        ->join('locales', 'locales.id', '=', 'translations.locale_id');

                    // Search by key
                    if ($request->filled('q')) {
                        $query->where('translations.key', 'like', $request->q . '%');
                    }

                    // Filter by locale
                    if ($request->filled('locale')) {
                        $query->where('locales.code', $request->locale);
                    }

                    // Filter by tags
                    if ($request->filled('tags')) {
                        $tags = explode(',', $request->tags);

                        $query
                            ->join('translation_tag', 'translation_tag.translation_id', '=', 'translations.id')
                            ->join('tags', 'tags.id', '=', 'translation_tag.tag_id')
                            ->whereIn('tags.name', $tags)
                            ->distinct();
                    }

                    return $query
                        ->orderBy('translations.id')
                        ->cursorPaginate($request->get('per_page', 25));
                }
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching translations.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $limit = (int) $request->get('limit', 500);

        $query = Translation::query()
            ->select([
                'translations.id as translation_id',
                'translations.key',
                'translations.content',
                'locales.code as locale',
            ])
            ->join('locales', 'locales.id', '=', 'translations.locale_id');

        // Search by key
        if ($request->filled('q')) {
            $query->where('translations.key', 'like', $request->q . '%');
        }

        // Filter by locale
        if ($request->filled('locale')) {
            $query->where('locales.code', $request->locale);
        }

        // Filter by tags
        if ($request->filled('tags')) {
            $tags = explode(',', $request->tags);

            $query
                ->join('translation_tag', 'translation_tag.translation_id', '=', 'translations.id')
                ->join('tags', 'tags.id', '=', 'translation_tag.tag_id')
                ->whereIn('tags.name', $tags)
                ->distinct();
        }

        // JSON export
        if ($format === 'json') {
            return response()->json(
                $query->limit($limit)->get()
            );
        }

        // CSV export
        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['key', 'locale', 'content', 'tags']);

            $query->orderBy('translations.id')
                ->chunkById(500, function ($rows) use ($handle) {
                    $rows->load('tags:name');

                    foreach ($rows as $t) {
                        fputcsv($handle, [
                            $t->key,
                            $t->locale,
                            $t->content,
                            $t->tags->pluck('name')->implode('|'),
                        ]);
                    }
                }, 'translation_id');

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=translations.csv',
        ]);
    }
}
