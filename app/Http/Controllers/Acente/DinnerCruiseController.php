<?php

namespace App\Http\Controllers\Acente;

use App\Models\LeisureRequest;

class DinnerCruiseController extends BaseLeisureRequestController
{
    protected function productType(): string
    {
        return LeisureRequest::PRODUCT_DINNER_CRUISE;
    }

    protected function routePrefix(): string
    {
        return 'acente.dinner-cruise';
    }
}
