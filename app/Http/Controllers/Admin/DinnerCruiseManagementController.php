<?php

namespace App\Http\Controllers\Admin;

use App\Models\LeisureRequest;

class DinnerCruiseManagementController extends BaseLeisureManagementController
{
    protected function productType(): string
    {
        return LeisureRequest::PRODUCT_DINNER_CRUISE;
    }

    protected function routeKey(): string
    {
        return 'dinner-cruise';
    }
}
