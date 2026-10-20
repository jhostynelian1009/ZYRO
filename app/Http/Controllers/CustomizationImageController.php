<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CustomizationImageController extends Controller
{
    public function show(OrderItem $item): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $item->loadMissing('order');

        abort_unless($user->isAdmin() || $item->order->isOwnedBy($user), 403);

        $path = data_get($item->customization, 'image_path');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
