<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\LeisureExtraOption;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;

class DinnerCruiseShowcaseController extends Controller
{
    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }

    public function __invoke()
    {
        $this->assertAuthorized();

        $packages = LeisurePackageTemplate::query()
            ->where('product_type', 'dinner_cruise')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $extras = LeisureExtraOption::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('product_type')
                    ->orWhere('product_type', 'dinner_cruise');
            })
            ->orderBy('default_included', 'desc')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mediaAssets = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('product_type')
                    ->orWhere('product_type', 'dinner_cruise');
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(24)
            ->get();

        return view('superadmin.leisure.dinner-cruise-showcase', [
            'packages' => $packages,
            'mediaAssets' => $mediaAssets,
            'includedExtras' => $extras->where('default_included', true)->values(),
            'optionalExtras' => $extras->where('default_included', false)->values(),
        ]);
    }
}

