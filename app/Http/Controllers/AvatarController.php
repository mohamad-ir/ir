<?php

namespace App\Http\Controllers;

use App\Services\Users\AvatarService;
use Illuminate\Http\Request;

/** Members upload, replace and remove their own profile photo (Account › Settings). */
class AvatarController extends Controller
{
    public function store(Request $request, AvatarService $avatars)
    {
        $request->validate(['avatar' => AvatarService::rules()], [
            'avatar.max' => 'The photo must be smaller than 5 MB.',
            'avatar.mimes' => 'Please choose a JPG, PNG or WebP image.',
            'avatar.mimetypes' => 'Please choose a JPG, PNG or WebP image.',
            'avatar.dimensions' => 'Please use a photo at least '.AvatarService::MIN_PX.' × '.AvatarService::MIN_PX.' pixels.',
        ]);

        $user = $avatars->store($request->user(), $request->file('avatar'));

        return $request->expectsJson()
            ? response()->json(['urls' => $this->urls($user)])
            : back()->with('status', 'Your profile photo has been updated.');
    }

    public function destroy(Request $request, AvatarService $avatars)
    {
        $avatars->remove($request->user());

        return $request->expectsJson()
            ? response()->json(['urls' => null])
            : back()->with('status', 'Your profile photo has been removed.');
    }

    protected function urls($user): array
    {
        return collect(AvatarService::SIZES)->mapWithKeys(fn ($px, $size) => [$size => $user->avatarUrl($size)])->all();
    }
}
