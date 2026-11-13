<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLinksRequest;
use App\Models\Link;
use App\Models\LinkGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LinkOrderController extends Controller
{
    public function __invoke(ReorderLinksRequest $request, LinkGroup $group): JsonResponse
    {
        DB::transaction(function () use ($request, $group) {
            foreach ($request->validated('links') as $position => $id) {
                Link::query()
                    ->whereBelongsTo($group)
                    ->whereKey($id)
                    ->update(['sort_order' => $position]);
            }
        });

        return response()->json(['message' => 'Link order saved.']);
    }
}
