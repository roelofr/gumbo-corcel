<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\Arr;
use App\Helpers\Str;
use App\Models\Activity;
use App\Models\NewsItem;
use App\Models\Sponsor;
use Czim\Paperclip\Contracts\AttachableInterface;
use Czim\Paperclip\Contracts\AttachmentInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

class MigratePaperclipToAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paperclip:migrate-away';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move Paperclip images to regular file-attachments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! interface_exists(AttachableInterface::class)) {
            $this->line('Paperclip no longer available');
            $this->error('This command is now obsolete');

            return 255;
        }

        $this->line('Migrating activities...', null, OutputInterface::VERBOSITY_VERBOSE);
        $this->migrateClass(Activity::class, 'image', 'poster');
        $this->info('Migrated activities.');

        $this->line('Migrating sponsors...', null, OutputInterface::VERBOSITY_VERBOSE);
        $this->migrateClass(Sponsor::class, 'backdrop', 'background_image');
        $this->info('Migrated sponsors.');

        $this->line('Migrating news items...', null, OutputInterface::VERBOSITY_VERBOSE);
        $this->migrateClass(NewsItem::class, 'image', 'cover_image');
        $this->info('Migrated news items.');
    }

    /**
     * Migrates the given model.
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function migrateClass(string $class, string $attachmentProperty, string $toProperty): void
    {
        $baseClass = class_basename($class);

        // Skip if migrated
        if (! is_a($class, AttachableInterface::class, true)) {
            $this->line("${baseClass} model no longer uses Paperclip, ignoring");

            return;
        }

        // Determine next dir
        $destinationDir = sprintf('images/%s/%s', Str::plural(Str::slug(class_basename($class))), $toProperty);

        // Get models
        $query = $class::query()
            ->withoutGlobalScopes()
            ->cursor();

        /** @var \Illuminate\Database\Eloquent\Model $attachment */
        foreach ($query as $item) {
            /** @var AttachmentInterface $attachment */
            $attachment = $item->{$attachmentProperty};

            if (! $attachment instanceof AttachmentInterface || ! $attachment->exists()) {
                $this->line("Skipping <info>{$item->name}</>, asset missing.");

                continue;
            }

            if ($item->poster !== null) {
                $this->line("Skipping <info>{$item->name}</>, already migrated.", null, OutputInterface::VERBOSITY_VERY_VERBOSE);

                continue;
            }

            $this->line("Migrating <info>{$item->name}</>...", null, OutputInterface::VERBOSITY_VERBOSE);

            $this->line('Downloading file to public folder...', null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            $publicPath = $this->downloadToPublic($item, $attachmentProperty, $destinationDir);

            // Fail
            if (! $publicPath) {
                $this->line("<error>Failed</> Image transfer for <info>{$item->name}</> failed.");
            }

            $this->line('Updating model...', null, OutputInterface::VERBOSITY_VERY_VERBOSE);

            // Store and update item
            $item->{$toProperty} = $publicPath;
            $item->save();

            // Log
            $this->line("Migrated <info>{$item->name}</>.");
        }
    }

    /**
     * Donwloads a file to the public disk and returns the new path.
     * @param AttachableInterface $model Model to get the item from
     * @param string $attachment Attachment to download
     * @param string $directory Directory to store the file in
     * @return null|string Path to file on public disk, or null if impossible
     */
    private function downloadToPublic(AttachableInterface $model, string $attachment, string $directory = 'files'): ?string
    {
        // Get proper
        /** @var AttachmentInterface $attachment */
        $attachments = $model->getAttachedFiles();
        $attachment = Arr::get($attachments, $attachment) ?? Arr::first($attachments);

        // Check if found
        if (! $attachment || ! $attachment->exists()) {
            return null;
        }

        // Check if exists as file
        $sourceDisk = $attachment->getStorage();
        $sourcePath = $attachment->variantPath();
        if (! Storage::disk($sourceDisk)->exists($sourcePath)) {
            return null;
        }

        // Get a temp file
        $tempFile = new File(tempnam(sys_get_temp_dir(), 'temp'));

        try {
            // Open a stream with Flysystem
            $readStream = Storage::disk($sourceDisk)->readStream($sourcePath);

            // Open a stream with the temp disk
            if (! $writeStream = fopen($tempFile->getPathname(), 'w')) {
                return null;
            }

            // Copy A to B
            stream_copy_to_stream($readStream, $writeStream);
        } catch (FileNotFoundException $e) {
            // Not found means fail at $readstream, abort
            return null;
        } finally {
            // Ensure both streams are closed
            isset($readStream) and fclose($readStream);
            isset($writeStream) and fclose($writeStream);
        }

        // Write to storage and return path
        return Storage::disk('public')->putFile($directory, $tempFile);
    }
}
