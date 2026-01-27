<?php

namespace App\Services;

use App\Models\Translation;

class TranslationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }


    public function search(array $filters)
    {
        return Translation::query()
            ->when($filters['key'] ?? null, fn($q, $v) => $q->where('key', 'like', "%{$v}%"))
            ->when($filters['content'] ?? null, fn($q, $v) => $q->where('content', 'like', "%{$v}%"))
            ->when(
                $filters['locale'] ?? null,
                fn($q, $v) =>
                $q->whereHas('locale', fn($q) => $q->where('code', $v))
            )
            ->when(
                $filters['tag'] ?? null,
                fn($q, $v) =>
                $q->whereHas('tags', fn($q) => $q->where('name', $v))
            )
            ->with(['locale', 'tags'])
            ->paginate();
    }
}
