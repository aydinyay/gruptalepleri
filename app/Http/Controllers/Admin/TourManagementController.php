<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeisureRequest;

class TourManagementController extends BaseLeisureManagementController
{
    protected function productType(): string
    {
        return LeisureRequest::PRODUCT_TOUR;
    }

    protected function routeKey(): string
    {
        return 'tour';
    }
}
