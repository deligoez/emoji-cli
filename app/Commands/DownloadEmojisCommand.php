<?php

namespace App\Commands;

use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Scheduling\Schedule;
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
        $this->task('Downloading emojis from unicode repository', function () {
            if (Storage::exists(config('emoji.tempFolder').config('emoji.tempFileName'))) {
                $this->info('Already downloaded from github');

                return true;
            }

            $url = file_get_contents(config('emoji.githubUrl'));

            Storage::put(config('emoji.tempFolder').config('emoji.tempFileName'), $url);

            return true;
        });
    }
}
