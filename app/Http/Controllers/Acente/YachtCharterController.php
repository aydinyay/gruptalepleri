<?php

namespace App\Http\Controllers\Acente;

use App\Models\LeisureRequest;

class YachtCharterController extends BaseLeisureRequestController
{
    protected function productType(): string
    {
        return LeisureRequest::PRODUCT_YACHT;
    }

    protected function routePrefix(): string
    {
        return 'acente.yacht-charter';
    }
}
