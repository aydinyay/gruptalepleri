<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeisureRequest;

class YachtCharterManagementController extends BaseLeisureManagementController
{
    protected function productType(): string
    {
        return LeisureRequest::PRODUCT_YACHT;
    }

    protected function routeKey(): string
    {
        return 'yacht-charter';
    }
}
