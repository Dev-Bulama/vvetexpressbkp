<?php

use Illuminate\Support\Facades\Http;
use Webkul\Marketplace\ERPNext\ERPNextClient;

/**
 * ERPNext's Image field is free text - not guaranteed to always come back
 * with a leading slash. Naively concatenating the base URL and the path
 * (e.g. "https://erp.example.com" + "files/x.jpg") produces a malformed
 * URL that silently fails to download, indistinguishable from ERPNext
 * genuinely having no image for that item. These prove the join is safe
 * either way.
 */
beforeEach(function () {
    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
    ]);
});

it('downloads an image when ERPNext returns a path with a leading slash', function () {
    Http::fake([
        'https://erp.test/files/dog-food.jpg' => Http::response('image-bytes', 200),
    ]);

    $client = app(ERPNextClient::class);

    expect($client->downloadImage('/files/dog-food.jpg'))->toBe('image-bytes');
});

it('downloads an image when ERPNext returns a path with no leading slash', function () {
    Http::fake([
        'https://erp.test/files/cat-food.jpg' => Http::response('image-bytes', 200),
    ]);

    $client = app(ERPNextClient::class);

    expect($client->downloadImage('files/cat-food.jpg'))->toBe('image-bytes');
});

it('downloads an image when ERPNext returns an already-absolute URL', function () {
    Http::fake([
        'https://cdn.example.com/product-photo.jpg' => Http::response('image-bytes', 200),
    ]);

    $client = app(ERPNextClient::class);

    expect($client->downloadImage('https://cdn.example.com/product-photo.jpg'))->toBe('image-bytes');
});

it('returns null without throwing when the image download fails', function () {
    Http::fake([
        'https://erp.test/files/missing.jpg' => Http::response('', 404),
    ]);

    $client = app(ERPNextClient::class);

    expect($client->downloadImage('/files/missing.jpg'))->toBeNull();
});
