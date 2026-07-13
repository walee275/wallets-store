<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Support\ProductEmailImage;
use Illuminate\Console\Command;

class GenerateEmailThumbsCommand extends Command
{
    protected $signature = 'media:generate-email-thumbs {--force : Regenerate even when a thumb already exists}';

    protected $description = 'Generate 640×640 email thumbnails for product images';

    public function handle(): int
    {
        $query = ProductImage::query()->orderBy('id');
        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        $generated = 0;

        $query->each(function (ProductImage $image) use (&$generated, $bar) {
            if (! $this->option('force') && $image->email_thumb_path) {
                $bar->advance();

                return;
            }

            if (ProductEmailImage::generateThumb($image)) {
                $generated++;
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Generated {$generated} email thumbnail(s).");

        return self::SUCCESS;
    }
}
