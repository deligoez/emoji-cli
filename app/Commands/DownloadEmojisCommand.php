<?php

namespace App\Commands;

use Exception;
use ZipArchive;
use DOMDocument;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;

class DownloadEmojisCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'download';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Download Emojis from official Unicode repository';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->task('Downloading emojis from unicode repository ...', function () {
            if (Storage::exists(config('emoji.tempFolder').config('emoji.tempFileName'))) {
                $this->info(PHP_EOL.'Already downloaded from github');

                return true;
            }

            $url = file_get_contents(config('emoji.githubUrl'));

            Storage::put(config('emoji.tempFolder').config('emoji.tempFileName'), $url);

            return true;
        });

        $this->task('Unzipping file ...', function () {
            if (Storage::exists(config('emoji.tempFolder').config('emoji.repositoryDataPath')))
            {
                $this->info(PHP_EOL.'Already unzipped.');

                return true;
            }

            try {
                $zipArchive = new ZipArchive();
                $zipArchive->open(config('emoji.tempFolder').config('emoji.tempFileName'));
                $zipArchive->extractTo(config('emoji.tempFolder'));
                $zipArchive->close();
            } catch (Exception $e) {
                throw $e;
            }
        });

        $this->task('Extracting emojis ...', function() {
            $files = Storage::files(config('emoji.tempFolder').config('emoji.repositoryDataPath'));

            foreach ($files as $file) {
                $languageName = explode('/', $file);
                $languageName = $languageName[count($languageName) - 1];

                $this->task(PHP_EOL.'Processing language: ' .$languageName , function() use ($file, $languageName) {
                    $xml = new DomDocument('1.0', 'utf-8');
                    $xml->load(Storage::path($file));

                    $emojis = collect();
                    $emojiSymbols = collect();
                    $zip = new ZipArchive;

                    foreach ($xml->getElementsByTagName('annotation') as $item) {
                        if ($zip->open(Storage::path('tempData/'. str_replace('.xml', '', $languageName) . '.alfredsnippets'), ZipArchive::CREATE) === true)
                        {
                            $emoji = $item->attributes['cp']->value;

                            // Don't add if it already contains.
                            if  ($emojiSymbols->contains($emoji))
                            {
                                continue;
                            } else {
                                $emojiSymbols->push($emoji);
                            }

                            $uuid = strtoupper(Uuid::uuid4()->toString());
                            $keywords = explode(' | ', $item->nodeValue);
                            $snippet = '';
                            array_multisort(array_map('strlen', $keywords),SORT_DESC, $keywords);
                            $dontAutoExpand = false;

                            foreach ($keywords as $keyword) {
                                if (!$emojis->contains($keyword))
                                {
                                    $emojis->push($keyword);
                                    $snippet = ":{$keyword}:";
                                }
                            }

                            // keyword is not unique
                            if (empty($snippet))
                            {
                                $dontAutoExpand = true;

                                $snippet = empty($keywords[1]) ? $keywords[0] : $keywords[1];
                            }

                            $emojiEntry = [
                                'alfredsnippet' => [
                                    'name'           => $emoji.' : '.implode(' | ', $keywords),
                                    'dontautoexpand' => $dontAutoExpand,
                                    'keyword'        => $snippet,
                                    'snippet'        => $emoji,
                                    'uid'            => $uuid,
                                ],
                            ];

                            $zip->addFromString($uuid.'.json',
                                json_encode($emojiEntry, JSON_PRETTY_PRINT)
                            );
                        }
                    }

                    $zip->close();
                });
            }
        });
    }
}
