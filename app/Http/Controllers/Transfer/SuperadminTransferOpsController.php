<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\SistemAyar;
use App\Models\TransferAirport;
use App\Models\TransferSettlementEntry;
use App\Models\TransferSupplier;
use App\Models\TransferVehicleMedia;
use App\Models\TransferVehicleType;
use App\Models\TransferZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SuperadminTransferOpsController extends Controller
{
    public function index()
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        return view('transfer.superadmin-ops', [
            'suppliers' => TransferSupplier::query()
                ->withCount(['pricingRules', 'coverages'])
                ->orderByDesc('is_approved')
                ->orderBy('company_name')
                ->get(),
            'airports' => TransferAirport::query()
                ->with(['zones' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->get(),
            'settlements' => TransferSettlementEntry::query()
                ->with(['supplier', 'booking.airport', 'booking.zone', 'booking.vehicleType', 'booking.agencyUser'])
                ->latest('id')
                ->limit(40)
                ->get(),
            'termsText'    => SistemAyar::transferSupplierTermsText(),
            'termsVersion' => SistemAyar::transferSupplierTermsVersion(),
            'vehicleTypes' => Schema::hasTable('transfer_vehicle_types')
                ? TransferVehicleType::query()
                    ->with(['media' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                    ->orderBy('sort_order')
                    ->get()
                : collect(),
            'amenityLabels' => TransferVehicleType::AMENITY_LABELS,
        ]);
    }

    public function updateSupplier(Request $request, TransferSupplier $supplier): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        $validated = $request->validate([
            'is_approved' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $supplier->update([
            'is_approved' => (bool) ($validated['is_approved'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'commission_rate' => (float) $validated['commission_rate'],
            'approved_at' => (bool) ($validated['is_approved'] ?? false) ? now() : null,
        ]);

        return back()->with('success', 'Supplier ayarlari guncellendi.');
    }

    public function forceAcceptTerms(TransferSupplier $supplier): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        $currentVersion = SistemAyar::transferSupplierTermsVersion();

        $supplier->update([
            'terms_accepted_at'      => now(),
            'terms_version_accepted' => $currentVersion,
            'is_approved'            => true,
            'approved_at'            => $supplier->approved_at ?? now(),
        ]);

        return back()->with('success', $supplier->company_name . ' için sözleşme v' . $currentVersion . ' yönetici adına onaylandı.');
    }

    public function updateTerms(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_suppliers'), 404);

        $validated = $request->validate([
            'terms_text' => ['required', 'string', 'min:20', 'max:20000'],
        ]);

        $nextVersion = SistemAyar::transferSupplierTermsVersion() + 1;

        SistemAyar::set(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_TEXT, trim((string) $validated['terms_text']));
        SistemAyar::set(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_VERSION, (string) $nextVersion);

        return back()->with('success', 'Transfer tedarikci sozlesmesi guncellendi. Yeni versiyon: ' . $nextVersion);
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'airport_id' => ['required', 'integer', 'exists:transfer_airports,id'],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        TransferZone::query()->updateOrCreate(
            [
                'airport_id' => (int) $validated['airport_id'],
                'slug' => Str::slug((string) $validated['name']),
            ],
            [
                'name' => $validated['name'],
                'city' => $validated['city'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'sort_order' => (int) ($validated['sort_order'] ?? 100),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Transfer bolgesi kaydedildi.');
    }

    public function updateZone(Request $request, TransferZone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $zone->update([
            'name' => $validated['name'],
            'slug' => Str::slug((string) $validated['name']),
            'city' => $validated['city'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? $zone->sort_order),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Bolge bilgisi guncellendi.');
    }

    // ── Araç Tipi Yönetimi ────────────────────────────────────────────────

    public function storeVehicleType(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_vehicle_types'), 404);

        $validated = $request->validate([
            'code'                   => ['required', 'string', 'max:40', 'unique:transfer_vehicle_types,code'],
            'name'                   => ['required', 'string', 'max:120'],
            'max_passengers'         => ['required', 'integer', 'min:1', 'max:100'],
            'luggage_capacity'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'suggested_retail_price' => ['nullable', 'numeric', 'min:0'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'amenities'              => ['nullable', 'array'],
            'amenities.*'            => ['string'],
            'sort_order'             => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active'              => ['nullable', 'boolean'],
        ]);

        TransferVehicleType::query()->create([
            'code'                   => strtolower(trim((string) $validated['code'])),
            'name'                   => trim((string) $validated['name']),
            'max_passengers'         => (int) $validated['max_passengers'],
            'luggage_capacity'       => isset($validated['luggage_capacity']) ? (int) $validated['luggage_capacity'] : null,
            'suggested_retail_price' => isset($validated['suggested_retail_price']) && $validated['suggested_retail_price'] !== '' ? (float) $validated['suggested_retail_price'] : null,
            'description'            => trim((string) ($validated['description'] ?? '')) ?: null,
            'amenities_json'         => array_values(array_intersect(
                $validated['amenities'] ?? [],
                array_keys(TransferVehicleType::AMENITY_LABELS)
            )),
            'sort_order'             => (int) ($validated['sort_order'] ?? 100),
            'is_active'              => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Araç tipi eklendi.');
    }

    public function updateVehicleType(Request $request, TransferVehicleType $vehicleType): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_vehicle_types'), 404);

        $validated = $request->validate([
            'name'                   => ['required', 'string', 'max:120'],
            'max_passengers'         => ['required', 'integer', 'min:1', 'max:100'],
            'luggage_capacity'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'suggested_retail_price' => ['nullable', 'numeric', 'min:0'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'amenities'              => ['nullable', 'array'],
            'amenities.*'            => ['string'],
            'sort_order'             => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active'              => ['nullable', 'boolean'],
        ]);

        $vehicleType->update([
            'name'                   => trim((string) $validated['name']),
            'max_passengers'         => (int) $validated['max_passengers'],
            'luggage_capacity'       => isset($validated['luggage_capacity']) ? (int) $validated['luggage_capacity'] : null,
            'suggested_retail_price' => isset($validated['suggested_retail_price']) && $validated['suggested_retail_price'] !== '' ? (float) $validated['suggested_retail_price'] : null,
            'description'            => trim((string) ($validated['description'] ?? '')) ?: null,
            'amenities_json'         => array_values(array_intersect(
                $validated['amenities'] ?? [],
                array_keys(TransferVehicleType::AMENITY_LABELS)
            )),
            'sort_order'             => (int) ($validated['sort_order'] ?? $vehicleType->sort_order),
            'is_active'              => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Araç tipi güncellendi.');
    }

    public function storeVehicleMedia(Request $request, TransferVehicleType $vehicleType): RedirectResponse
    {
        abort_unless(Schema::hasTable('transfer_vehicle_media'), 404);

        $existingCount = TransferVehicleMedia::query()
            ->where('vehicle_type_id', $vehicleType->id)
            ->where('is_active', true)
            ->count();

        if ($existingCount >= 7) {
            return back()->with('error', 'Bu araç tipi için zaten maksimum medya (6 fotoğraf + 1 video) var. Önce bir tanesini silin.');
        }

        $request->validate([
            'vehicle_media'   => ['required', 'array', 'min:1'],
            'vehicle_media.*' => ['file', 'max:51200'],
        ]);

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'mp4', 'webm', 'mov', 'mkv', 'avi'];
        $videoExts   = ['mp4', 'webm', 'mov', 'mkv', 'avi'];
        $files       = $request->file('vehicle_media');
        $slots       = 7 - $existingCount;
        $added       = 0;
        $directory   = '/uploads/transfer-vehicle-media/' . $vehicleType->code;
        File::ensureDirectoryExists(public_path($directory));

        foreach (array_slice($files, 0, $slots) as $file) {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, $allowedExts, true)) {
                continue;
            }

            $mediaType = in_array($ext, $videoExts, true) ? 'video' : 'photo';

            // Araç başına 1 video sınırı
            if ($mediaType === 'video') {
                $hasVideo = TransferVehicleMedia::query()
                    ->where('vehicle_type_id', $vehicleType->id)
                    ->where('media_type', 'video')
                    ->where('is_active', true)
                    ->exists();
                if ($hasVideo) {
                    continue;
                }
            }

            $filename = uniqid('tvmedia_', true) . '.' . $ext;
            $file->move(public_path($directory), $filename);

            TransferVehicleMedia::query()->create([
                'vehicle_type_id' => $vehicleType->id,
                'media_type'      => $mediaType,
                'source_type'     => 'upload',
                'file_path'       => $directory . '/' . $filename,
                'is_active'       => true,
                'sort_order'      => $existingCount + $added + 1,
            ]);

            $added++;
        }

        return back()->with('success', $added . ' medya dosyası eklendi.');
    }

    public function deleteVehicleMedia(Request $request, TransferVehicleMedia $media): RedirectResponse
    {
        if ($media->file_path && str_starts_with($media->file_path, '/uploads/transfer-vehicle-media/')) {
            $absolutePath = public_path(ltrim($media->file_path, '/'));
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        $media->delete();

        return back()->with('success', 'Medya silindi.');
    }
}
