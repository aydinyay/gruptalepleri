<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\LeisureRequest;
use App\Models\TransferBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AcenteReservationsController extends Controller
{
    use Concerns\ResolvesPreviewUser;

    public function index(Request $request)
    {
        $actor = $this->acenteActor();

        // Transfer rezervasyonları
        $transferBookings = collect();
        if (Schema::hasTable('transfer_bookings')) {
            $transferBookings = TransferBooking::query()
                ->where('agency_user_id', $actor->id)
                ->with(['airport', 'zone', 'vehicleType', 'paymentTransactions' => fn ($q) => $q->latest()->limit(1)])
                ->orderByDesc('id')
                ->get();
        }

        // Dinner cruise + yacht rezervasyonları
        $leisureBookings = collect();
        if (Schema::hasTable('leisure_requests') && Schema::hasTable('leisure_bookings')) {
            $leisureBookings = LeisureRequest::query()
                ->where('user_id', $actor->id)
                ->whereHas('booking')
                ->with([
                    'booking',
                    'dinnerCruiseDetail',
                    'yachtDetail',
                ])
                ->orderByDesc('id')
                ->get();
        }

        return view('acente.rezervasyonlarim', [
            'transferBookings' => $transferBookings,
            'leisureBookings'  => $leisureBookings,
        ]);
    }
}
