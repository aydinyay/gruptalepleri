<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;
use App\Models\B2C\B2cAgencySubscription;
use App\Models\TransferSupplier;
use App\Models\TransferVehicleFleet;
use App\Models\TransferVehicleType;
use Illuminate\Http\Request;

class B2cSubscriptionController extends Controller
{
    use ResolvesPreviewUser;

    /** Başvuru / durum sayfası */
    public function index()
    {
        $user         = $this->acenteActor();
        $subscription = B2cAgencySubscription::where('user_id', $user->id)
                            ->with('transferSupplier')
                            ->first();

        $transferSupplier = TransferSupplier::where('user_id', $user->id)->first();

        // Filo bilgileri (kapasite)
        $fleet = [];
        if ($transferSupplier) {
            $fleet = TransferVehicleFleet::where('supplier_id', $transferSupplier->id)
                ->with('vehicleType')
                ->get();
        }

        $vehicleTypes = TransferVehicleType::where('is_active', true)->orderBy('sort_order')->get();

        return view('acente.b2c.subscription', compact(
            'subscription', 'transferSupplier', 'fleet', 'vehicleTypes'
        ));
    }

    /** Başvuruyu kaydet */
    public function apply(Request $request)
    {
        if ($block = $this->blockPreviewWrites()) {
            return $block;
        }

        $user = $this->acenteActor();

        // Daha önce başvuru yapılmış mı?
        if (B2cAgencySubscription::where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Zaten bir başvurunuz bulunuyor.');
        }

        $validated = $request->validate([
            'service_types'  => 'required|array|min:1',
            'service_types.*'=> 'in:transfer,leisure,charter,tour',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $transferSupplier = TransferSupplier::where('user_id', $user->id)->first();

        B2cAgencySubscription::create([
            'user_id'              => $user->id,
            'transfer_supplier_id' => $transferSupplier?->id,
            'status'               => B2cAgencySubscription::STATUS_PENDING,
            'service_types_json'   => $validated['service_types'],
        ]);

        return back()->with('success', 'Başvurunuz alındı. İnceleme sonucunu e-posta ile bildireceğiz.');
    }

    /** Araç filosu kaydet (kapasite tanımı) */
    public function saveFleet(Request $request)
    {
        if ($block = $this->blockPreviewWrites()) {
            return $block;
        }

        $user             = $this->acenteActor();
        $transferSupplier = TransferSupplier::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'fleet'                           => 'required|array',
            'fleet.*.vehicle_type_id'         => 'required|exists:transfer_vehicle_types,id',
            'fleet.*.quantity'                => 'required|integer|min:0|max:100',
            'fleet.*.max_daily_bookings'      => 'required|integer|min:0|max:500',
        ]);

        foreach ($validated['fleet'] as $row) {
            if ((int) $row['quantity'] === 0) {
                // Sıfır araç girilmişse kaydı sil (veya pasif yap)
                TransferVehicleFleet::where('supplier_id', $transferSupplier->id)
                    ->where('vehicle_type_id', $row['vehicle_type_id'])
                    ->delete();
                continue;
            }

            TransferVehicleFleet::updateOrCreate(
                [
                    'supplier_id'     => $transferSupplier->id,
                    'vehicle_type_id' => $row['vehicle_type_id'],
                ],
                [
                    'quantity'           => $row['quantity'],
                    'max_daily_bookings' => $row['max_daily_bookings'],
                    'is_active'          => true,
                ]
            );
        }

        return back()->with('success', 'Araç filosu güncellendi.');
    }
}
