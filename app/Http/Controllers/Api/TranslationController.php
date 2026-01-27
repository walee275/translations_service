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
        $limit  = (int) $request->get('limit', 1000);

        $query = Translation::query()
            ->select([
                'translations.id',
                'translations.key',
                'translations.content',
                'translations.locale_id',
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
            $tagNames = explode(',', $request->tags);
            $query->whereExists(function ($subQuery) use ($tagNames) {
                $subQuery->select(DB::raw(1))
                    ->from('translation_tag')
                    ->join('tags', 'tags.id', '=', 'translation_tag.tag_id')
                    ->whereColumn('translation_tag.translation_id', 'translations.id')
                    ->whereIn('tags.name', $tagNames);
            });
        }

        $query->orderBy('translations.id');

        if ($format === 'json') {
            $results = $query->cursorPaginate($limit);


            $translationIds = $results->pluck('id');

            $tagsMap = DB::table('translation_tag')
                ->join('tags', 'tags.id', '=', 'translation_tag.tag_id')
                ->whereIn('translation_tag.translation_id', $translationIds)
                ->select('translation_tag.translation_id', 'tags.name')
                ->get()
                ->groupBy('translation_id')
                ->map(function ($tags) {
                    return $tags->pluck('name')->implode('|');
                });

            // Attach tags
            $results->getCollection()->transform(function ($item) use ($tagsMap) {
                $item->tags = $tagsMap->get($item->id, '');
                return $item;
            });

            return response()->json($results);
        }

        // ----------------
        // CSV STREAMING
        // ----------------
        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['key', 'locale', 'content', 'tags']);

            // Cache tags lookup
            $tagsCache = [];

            $query->chunkById(500, function ($rows) use ($handle, &$tagsCache) {
                $ids = $rows->pluck('id');

                // Bulk load tags for this chunk
                $chunkTags = DB::table('translation_tag')
                    ->join('tags', 'tags.id', '=', 'translation_tag.tag_id')
                    ->whereIn('translation_tag.translation_id', $ids)
                    ->select('translation_tag.translation_id', 'tags.name')
                    ->get()
                    ->groupBy('translation_id')
                    ->map(fn($tags) => $tags->pluck('name')->implode('|'));

                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->key,
                        $row->locale,
                        $row->content,
                        $chunkTags->get($row->id, ''),
                    ]);
                }
            }, 'translations.id');

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=translations.csv',
        ]);
    }
}
