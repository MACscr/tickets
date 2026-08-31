<?php

use Illuminate\Support\Facades\Storage;
use Padmission\Tickets\Models\TicketAttachment;

it('deletes file when record gets deleted', function () {
    $filesystem = Storage::fake(config('padmission-tickets.attachments.disk'));

    $attachment = TicketAttachment::factory()->create();
    $filesystem->put($attachment->filepath, 'testfile');

    $filesystem->assertExists($attachment->filepath);

    $attachment->delete();

    $filesystem->assertMissing($attachment->filepath);
});

it('deletes the preview file from the preview disk when record gets deleted', function () {
    config()->set('padmission-tickets.attachments.disk', 'attachments');
    config()->set('padmission-tickets.attachments.preview_disk', 'previews');

    $attachmentDisk = Storage::fake('attachments');
    $previewDisk = Storage::fake('previews');

    $attachment = TicketAttachment::factory()->create([
        'filepath' => 'tickets/1/file.png',
        'preview_filepath' => 'tickets/1/thumbnails/file.png',
    ]);

    $attachmentDisk->put($attachment->filepath, 'testfile');
    $previewDisk->put($attachment->preview_filepath, 'thumbnail');

    $attachmentDisk->assertExists($attachment->filepath);
    $previewDisk->assertExists($attachment->preview_filepath);

    $attachment->delete();

    $attachmentDisk->assertMissing('tickets/1/file.png');
    $previewDisk->assertMissing('tickets/1/thumbnails/file.png');
});

it('does not error when the attachment has no preview file', function () {
    config()->set('padmission-tickets.attachments.disk', 'attachments');
    config()->set('padmission-tickets.attachments.preview_disk', 'previews');

    $attachmentDisk = Storage::fake('attachments');
    Storage::fake('previews');

    $attachment = TicketAttachment::factory()->create([
        'filepath' => 'tickets/1/file.png',
        'preview_filepath' => null,
    ]);

    $attachmentDisk->put($attachment->filepath, 'testfile');

    $attachment->delete();

    $attachmentDisk->assertMissing('tickets/1/file.png');
    expect($attachment->exists)->toBeFalse();
});
