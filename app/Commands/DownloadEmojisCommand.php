<?php

namespace App\Commands;

use Exception;
use ZipArchive;
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
            // TODO: Just code snippet
            $xml = new DomDocument('1.0', 'utf-8');
            $xml->load(
                '/Users/deligoez/Downloads/cldr-release-37-alpha1/common/annotationsDerived/tr.xml'
            );

            foreach ($xml->getElementsByTagName('annotation') as $item) {
                echo explode(' | ', $item->nodeValue)[0] . PHP_EOL;
            }
        });
    }
}
