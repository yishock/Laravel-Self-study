<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AdminUserListPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserListPreferenceController extends Controller
{
    public function show(string $pageKey): JsonResponse
    {
        $row = AdminUserListPreference::query()
            ->where('admin_user_id', auth()->id())
            ->where('page_key', $pageKey)
            ->first();

        return response()->json([
            'page_key' => $pageKey,
            'preferences' => $row?->preferences ?? [],
        ]);
    }

    public function update(Request $request, string $pageKey): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.pageLength' => ['nullable', 'integer', 'min:5', 'max:500'],
            'preferences.order' => ['nullable', 'array'],
            'preferences.sortKey' => ['nullable', 'string'],
            'preferences.sortDir' => ['nullable', 'string', 'in:asc,desc'],
            'preferences.columnOrder' => ['nullable', 'array'],
            'preferences.columnWidths' => ['nullable', 'array'],
        ]);

        AdminUserListPreference::query()->updateOrCreate(
            [
                'admin_user_id' => auth()->id(),
                'page_key' => $pageKey,
            ],
            [
                'preferences' => $validated['preferences'],
            ]
        );

        return response()->json([
            'message' => '已儲存列表設定',
            'page_key' => $pageKey,
        ]);
    }

    public function destroy(string $pageKey): JsonResponse
    {
        AdminUserListPreference::query()
            ->where('admin_user_id', auth()->id())
            ->where('page_key', $pageKey)
            ->delete();

        return response()->json([
            'message' => '已恢復預設列表設定',
            'page_key' => $pageKey,
        ]);
    }
}
