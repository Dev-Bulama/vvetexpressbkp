<?php

namespace App\Http\Controllers\API\Seller;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Handlers\LfmConfigHandler;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller
{
    public function laravelFilemanagerImage(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $configHandler = new LfmConfigHandler();
        $shopId = $configHandler->userField();


        if (! $shopId) {
            return response()->json(['message' => 'No shop ID found for current session'], 404);
        }

        if (! Storage::disk('public')->exists("files/{$shopId}")) {
            return response()->json([
                'shop_id' => $shopId,
                'images' => [],
                'message' => 'No files found for this shop',
            ]);
        }

        $files = Storage::disk('public')->allFiles("files/{$shopId}");

        $images = collect($files)
            ->filter(fn($file) => Str::endsWith($file, ['.jpg', '.jpeg', '.png', '.gif', '.webp']))
            ->map(fn($file) => [
                'name' => basename($file),
                'store_path' => $file,
                'url'  => asset("storage/{$file}"),
                'size_kb' => round(Storage::disk('public')->size($file) / 1024, 2),
                'last_modified' => date('Y-m-d H:i:s', Storage::disk('public')->lastModified($file)),
            ])
            ->values();

        return $this->json(__('Image Fetch successfully'), [
            'count'   => $images->count(),
            'images'  => $images,
        ]);
    }

    public function laravelFilemanagerUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:2048|mimes:png,jpg,jpeg,gif,svg',
        ]);

        $user = auth()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $configHandler = new LfmConfigHandler();
        $shopId = $configHandler->userField();

        if (! $shopId) {
            return response()->json(['message' => 'No shop ID found for current session'], 404);
        }

        $file = $request->file('file');

        $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs(
            "files/{$shopId}",
            $filename,
            'public'
        );

        $url = asset("storage/{$path}");

        return $this->json(__('Image Upload successfully'), [
            'name'      => $filename,
            'path'      => $path,
            'url'       => $url,
            'size_kb'   => round(Storage::disk('public')->size($path) / 1024, 2),
            'extension' => $file->getClientOriginalExtension(),
        ]);
    }

    public function laravelFilemanagerDelete(Request $request)
    {
        $request->validate([
            'storage_path' => 'required|string',
        ]);

        $file = $request->storage_path;

        if (!Storage::disk('public')->exists($file)) {
            return $this->json(__('Image not found'));
        }

        Storage::delete($file);
        
        return $this->json(__('Image Delete successfully'));
    }
}
