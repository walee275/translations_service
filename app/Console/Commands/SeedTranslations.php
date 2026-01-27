<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:seed {count=100000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tags = Tag::pluck('id')->toArray();

        Translation::factory()
            ->count($this->argument('count'))
            ->make()
            ->chunk(100)
            ->each(function ($chunk) use ($tags) {

                // Prepare key + locale pairs
                $keys = collect($chunk)->map(fn($row) => [
                    'key' => $row['key'],
                    'locale_id' => $row['locale_id'],
                ]);
                $rows = $chunk->map(fn($t) => [
                    'key'       => $t->key,
                    'content'   => $t->content,
                    'locale_id' => $t->locale_id,
                ])->toArray();

                Translation::upsert(
                    $chunk->toArray(),
                    ['key', 'locale_id'],
                    ['content']
                );

                // Fetch translation IDs
                $translations = Translation::whereIn(
                    'key',
                    collect($rows)->pluck('key')->toArray()
                )->get(['id']);

                // Attach tags
                $pivotData = [];

                foreach ($translations as $translation) {
                    $selectedTags = (array) array_rand($tags, rand(1, 2));

                    foreach ($selectedTags as $tagIndex) {
                        $pivotData[] = [
                            'translation_id' => $translation->id,
                            'tag_id' => $tags[$tagIndex],
                        ];
                    }
                }

                DB::table('translation_tag')->insertOrIgnore($pivotData);
            });
    }
}
