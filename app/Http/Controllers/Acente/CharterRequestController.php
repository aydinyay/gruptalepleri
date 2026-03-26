<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\CharterAirlinerRequest;
use App\Models\CharterBooking;
use App\Models\CharterExtra;
use App\Models\CharterHelicopterRequest;
use App\Models\CharterJetRequest;
use App\Models\CharterPresetPackage;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use App\Models\SistemAyar;
use App\Services\Charter\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CharterRequestController extends Controller
{
    public function index(Request $request)
    {
        $transportType = (string) $request->query('transport_type', '');
        $status = (string) $request->query('status', '');

        $query = CharterRequest::query()
            ->where('user_id', auth()->id())
            ->with(['salesQuotes', 'booking'])
            ->latest();

        if (in_array($transportType, [CharterRequest::TYPE_JET, CharterRequest::TYPE_HELICOPTER, CharterRequest::TYPE_AIRLINER], true)) {
            $query->where('transport_type', $transportType);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('acente.charter.index', [
            'requests' => $requests,
            'transportType' => $transportType,
            'status' => $status,
        ]);
    }

    public function create()
    {
        return view('acente.charter.create');
    }

    public function store(Request $request, AiService $aiService): RedirectResponse
    {
        $validated = $request->validate([
            'transport_type' => 'required|in:jet,helicopter,airliner',
            'from_iata' => 'required|string|max:10',
            'to_iata' => 'required|string|max:10',
            'departure_date' => 'required|date|after_or_equal:today',
            'pax' => 'required|integer|min:1|max:400',
            'is_flexible' => 'nullable|boolean',
            'group_type' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:4000',
            'preset_package_code' => 'nullable|string|max:80',

            'jet.flight_hours_estimate' => 'nullable|integer|min:0|max:1000',
            'jet.round_trip' => 'nullable|boolean',
            'jet.return_date' => 'nullable|date|after_or_equal:departure_date',
            'jet.different_return_route' => 'nullable|boolean',
            'jet.return_from_iata' => 'nullable|string|max:10',
            'jet.return_to_iata' => 'nullable|string|max:10',
            'jet.multi_leg' => 'nullable|boolean',
            'jet.segments' => 'nullable|array|max:10',
            'jet.segments.*.from_iata' => 'nullable|string|max:10',
            'jet.segments.*.to_iata' => 'nullable|string|max:10',
            'jet.segments.*.departure_date' => 'nullable|date',
            'jet.pet_onboard' => 'nullable|boolean',
            'jet.vip_catering' => 'nullable|boolean',
            'jet.wifi_required' => 'nullable|boolean',
            'jet.special_luggage' => 'nullable|boolean',
            'jet.luggage_count' => 'nullable|integer|min:0|max:100',
            'jet.cabin_preference' => 'nullable|string|max:120',
            'jet.airport_slot_note' => 'nullable|string|max:255',
            'jet.specs_json' => 'nullable|string|max:5000',

            'helicopter.pickup' => 'nullable|string|max:255',
            'helicopter.dropoff' => 'nullable|string|max:255',
            'helicopter.landing_details' => 'nullable|string|max:2000',

            'airliner.date_flexible' => 'nullable|boolean',
            'airliner.group_type' => 'nullable|string|max:120',
            'airliner.route_notes' => 'nullable|string|max:2000',

            'extras' => 'nullable|array',
            'extras.*.title' => 'nullable|string|max:120',
            'extras.*.agency_note' => 'nullable|string|max:1000',
        ]);

        $presetPackageCode = trim((string) ($validated['preset_package_code'] ?? ''));
        $selectedPresetPackage = null;
        if ($presetPackageCode !== '') {
            $selectedPresetPackage = $this->presetPackages()[$presetPackageCode] ?? null;

            if ($selectedPresetPackage === null) {
                return back()
                    ->withErrors(['preset_package_code' => 'Secilen hazir paket bulunamadi.'])
                    ->withInput();
            }
        }

        if (
            ($validated['transport_type'] ?? '') === CharterRequest::TYPE_JET
            && (bool) ($validated['jet']['round_trip'] ?? false)
            && empty($validated['jet']['return_date'])
        ) {
            return back()
                ->withErrors(['jet.return_date' => 'Gidiş - dönüş seçiminde dönüş tarihi zorunludur.'])
                ->withInput();
        }

        if (
            ($validated['transport_type'] ?? '') === CharterRequest::TYPE_JET
            && (bool) ($validated['jet']['round_trip'] ?? false)
            && (bool) ($validated['jet']['different_return_route'] ?? false)
            && (
                empty($validated['jet']['return_from_iata'])
                || empty($validated['jet']['return_to_iata'])
            )
        ) {
            return back()
                ->withErrors(['jet.return_from_iata' => 'Dönüş rotası farklıysa dönüş kalkış ve varış noktaları zorunludur.'])
                ->withInput();
        }

        if (
            ($validated['transport_type'] ?? '') === CharterRequest::TYPE_JET
            && (bool) ($validated['jet']['multi_leg'] ?? false)
        ) {
            $segments = collect($validated['jet']['segments'] ?? [])
                ->map(function ($segment) {
                    return [
                        'from_iata' => strtoupper(trim((string) ($segment['from_iata'] ?? ''))),
                        'to_iata' => strtoupper(trim((string) ($segment['to_iata'] ?? ''))),
                        'departure_date' => trim((string) ($segment['departure_date'] ?? '')),
                    ];
                })
                ->filter(function ($segment) {
                    return collect($segment)->filter()->count() > 0;
                })
                ->values();

            $hasIncompleteSegment = $segments->contains(function ($segment) {
                return empty($segment['from_iata']) || empty($segment['to_iata']) || empty($segment['departure_date']);
            });

            if ($hasIncompleteSegment) {
                return back()
                    ->withErrors(['jet.segments' => 'Çoklu uçuş satırlarında kalkış, varış ve tarih birlikte girilmelidir.'])
                    ->withInput();
            }

            if ($segments->isEmpty()) {
                return back()
                    ->withErrors(['jet.segments' => 'Çoklu uçuş için en az bir ek parkur girilmelidir.'])
                    ->withInput();
            }
        }

        $presetColumnsReady = Schema::hasColumns('charter_requests', [
            'preset_package_code',
            'preset_package_title',
            'preset_package_price',
            'preset_package_currency',
            'preset_package_snapshot',
        ]);

        $charterRequest = DB::transaction(function () use ($validated, $selectedPresetPackage, $presetColumnsReady) {
            $createPayload = [
                'user_id' => auth()->id(),
                'requester_type' => 'agency',
                'transport_type' => $validated['transport_type'],
                'status' => CharterRequest::STATUS_LEAD,
                'name' => auth()->user()->name,
                'phone' => auth()->user()->phone,
                'email' => auth()->user()->email,
                'from_iata' => strtoupper(trim($validated['from_iata'])),
                'to_iata' => strtoupper(trim($validated['to_iata'])),
                'departure_date' => $validated['departure_date'],
                'pax' => $validated['pax'],
                'is_flexible' => (bool) ($validated['is_flexible'] ?? false),
                'group_type' => $validated['group_type'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            if ($presetColumnsReady) {
                $createPayload['preset_package_code'] = $selectedPresetPackage['code'] ?? null;
                $createPayload['preset_package_title'] = $selectedPresetPackage['title'] ?? null;
                $createPayload['preset_package_price'] = $selectedPresetPackage['price'] ?? null;
                $createPayload['preset_package_currency'] = $selectedPresetPackage['currency'] ?? null;
                $createPayload['preset_package_snapshot'] = $selectedPresetPackage ? [
                    'summary' => $selectedPresetPackage['summary'] ?? null,
                    'transport_type' => $selectedPresetPackage['transport_type'] ?? null,
                    'from_iata' => $selectedPresetPackage['from_iata'] ?? null,
                    'to_iata' => $selectedPresetPackage['to_iata'] ?? null,
                    'aircraft_label' => $selectedPresetPackage['aircraft_label'] ?? null,
                    'suggested_pax' => $selectedPresetPackage['suggested_pax'] ?? null,
                    'trip_type' => $selectedPresetPackage['trip_type'] ?? null,
                    'group_type' => $selectedPresetPackage['group_type'] ?? null,
                    'cabin_preference' => $selectedPresetPackage['cabin_preference'] ?? null,
                    'highlights' => $selectedPresetPackage['highlights'] ?? [],
                ] : null;
            }

            $charterRequest = CharterRequest::query()->create($createPayload);

            if ($validated['transport_type'] === CharterRequest::TYPE_JET) {
                $specsJson = [];
                $rawSpecsJson = $validated['jet']['specs_json'] ?? null;
                if (is_string($rawSpecsJson) && trim($rawSpecsJson) !== '') {
                    $decodedSpecs = json_decode($rawSpecsJson, true);
                    if (is_array($decodedSpecs)) {
                        $specsJson = $decodedSpecs;
                    }
                }

                foreach (['return_date', 'different_return_route', 'return_from_iata', 'return_to_iata', 'multi_leg', 'segments'] as $specKey) {
                    unset($specsJson[$specKey]);
                }

                if (! empty($validated['jet']['return_date'])) {
                    $specsJson['return_date'] = $validated['jet']['return_date'];
                }

                if (! empty($validated['jet']['round_trip'])) {
                    $differentReturnRoute = (bool) ($validated['jet']['different_return_route'] ?? false);
                    $returnFromIata = $differentReturnRoute
                        ? strtoupper(trim((string) ($validated['jet']['return_from_iata'] ?? '')))
                        : strtoupper(trim((string) ($validated['to_iata'] ?? '')));
                    $returnToIata = $differentReturnRoute
                        ? strtoupper(trim((string) ($validated['jet']['return_to_iata'] ?? '')))
                        : strtoupper(trim((string) ($validated['from_iata'] ?? '')));

                    $specsJson['different_return_route'] = $differentReturnRoute;
                    if ($returnFromIata !== '') {
                        $specsJson['return_from_iata'] = $returnFromIata;
                    }
                    if ($returnToIata !== '') {
                        $specsJson['return_to_iata'] = $returnToIata;
                    }
                }

                if (! empty($validated['jet']['multi_leg'])) {
                    $segmentPayload = collect($validated['jet']['segments'] ?? [])
                        ->map(function ($segment) {
                            return [
                                'from_iata' => strtoupper(trim((string) ($segment['from_iata'] ?? ''))),
                                'to_iata' => strtoupper(trim((string) ($segment['to_iata'] ?? ''))),
                                'departure_date' => trim((string) ($segment['departure_date'] ?? '')),
                            ];
                        })
                        ->filter(function ($segment) {
                            return ! empty($segment['from_iata']) && ! empty($segment['to_iata']) && ! empty($segment['departure_date']);
                        })
                        ->values()
                        ->all();

                    if (! empty($segmentPayload)) {
                        $specsJson['multi_leg'] = true;
                        $specsJson['segments'] = $segmentPayload;
                    }
                }

                CharterJetRequest::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'flight_hours_estimate' => $validated['jet']['flight_hours_estimate'] ?? null,
                    'round_trip' => (bool) ($validated['jet']['round_trip'] ?? false),
                    'pet_onboard' => (bool) ($validated['jet']['pet_onboard'] ?? false),
                    'vip_catering' => (bool) ($validated['jet']['vip_catering'] ?? false),
                    'wifi_required' => (bool) ($validated['jet']['wifi_required'] ?? false),
                    'special_luggage' => (bool) ($validated['jet']['special_luggage'] ?? false),
                    'luggage_count' => $validated['jet']['luggage_count'] ?? null,
                    'cabin_preference' => $validated['jet']['cabin_preference'] ?? null,
                    'airport_slot_note' => $validated['jet']['airport_slot_note'] ?? null,
                    'specs_json' => ! empty($specsJson) ? $specsJson : null,
                ]);
            } elseif ($validated['transport_type'] === CharterRequest::TYPE_HELICOPTER) {
                CharterHelicopterRequest::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'pickup' => $validated['helicopter']['pickup'] ?? null,
                    'dropoff' => $validated['helicopter']['dropoff'] ?? null,
                    'landing_details' => $validated['helicopter']['landing_details'] ?? null,
                ]);
            } else {
                CharterAirlinerRequest::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'date_flexible' => (bool) ($validated['airliner']['date_flexible'] ?? false),
                    'group_type' => $validated['airliner']['group_type'] ?? null,
                    'route_notes' => $validated['airliner']['route_notes'] ?? null,
                ]);
            }

            foreach (($validated['extras'] ?? []) as $extra) {
                $title = trim((string) ($extra['title'] ?? ''));
                $note = trim((string) ($extra['agency_note'] ?? ''));
                if ($title === '' && $note === '') {
                    continue;
                }

                CharterExtra::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'title' => $title !== '' ? $title : 'Ek Hizmet',
                    'agency_note' => $note !== '' ? $note : null,
                    'status' => 'pending_pricing',
                ]);
            }

            return $charterRequest;
        });

        try {
            $charterRequest->load(['jetDetail', 'helicopterDetail', 'airlinerDetail']);
            $aiService->buildPreQuote($charterRequest);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('acente.charter.show', $charterRequest)
            ->with('success', 'Air Charter talebiniz olusturuldu. On teklif hesaplandi.');
    }

    public function show(CharterRequest $charterRequest)
    {
        abort_unless($charterRequest->user_id === auth()->id(), 403);

        $charterRequest->load([
            'jetDetail',
            'helicopterDetail',
            'airlinerDetail',
            'extras',
            'salesQuotes.supplierQuote',
            'booking.payments',
        ]);

        return view('acente.charter.show', compact('charterRequest'));
    }

    public function acceptSalesQuote(CharterRequest $charterRequest, CharterSalesQuote $salesQuote): RedirectResponse
    {
        abort_unless($charterRequest->user_id === auth()->id(), 403);
        abort_unless($salesQuote->charter_request_id === $charterRequest->id, 422);

        DB::transaction(function () use ($charterRequest, $salesQuote) {
            $charterRequest->salesQuotes()->where('id', '!=', $salesQuote->id)->update(['status' => 'rejected']);
            $salesQuote->update(['status' => 'accepted']);

            CharterBooking::query()->updateOrCreate(
                ['charter_request_id' => $charterRequest->id],
                [
                    'sales_quote_id' => $salesQuote->id,
                    'status' => 'pending_payment',
                    'total_amount' => $salesQuote->sale_price,
                    'total_paid' => 0,
                    'remaining_amount' => $salesQuote->sale_price,
                ]
            );

            $charterRequest->update(['status' => CharterRequest::STATUS_PENDING_PAYMENT]);
        });

        return back()->with('success', 'Teklif kabul edildi. Odeme surecine gecildi.');
    }

    private function presetPackages(): array
    {
        if (Schema::hasTable('charter_preset_packages')) {
            return CharterPresetPackage::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->mapWithKeys(function (CharterPresetPackage $package): array {
                    return [
                        $package->code => [
                            'code' => $package->code,
                            'title' => $package->title,
                            'summary' => $package->summary,
                            'transport_type' => $package->transport_type,
                            'from_iata' => strtoupper((string) $package->from_iata),
                            'to_iata' => strtoupper((string) $package->to_iata),
                            'from_label' => $package->from_label,
                            'to_label' => $package->to_label,
                            'aircraft_label' => $package->aircraft_label,
                            'suggested_pax' => (int) $package->suggested_pax,
                            'trip_type' => $package->trip_type ?: 'Tek Yon',
                            'group_type' => $package->group_type,
                            'cabin_preference' => $package->cabin_preference,
                            'price' => (float) $package->price,
                            'currency' => $package->currency ?: 'EUR',
                            'highlights' => array_values(array_filter((array) ($package->highlights_json ?? []))),
                        ],
                    ];
                })
                ->all();
        }

        $settingsRaw = (string) SistemAyar::get('charter_preset_packages_json', '[]');
        $settingsDecoded = json_decode($settingsRaw, true);
        if (is_array($settingsDecoded) && ! empty($settingsDecoded)) {
            return collect($settingsDecoded)
                ->mapWithKeys(function (array $package): array {
                    $code = strtolower(trim((string) ($package['code'] ?? '')));
                    if ($code === '') {
                        return [];
                    }

                    return [
                        $code => [
                            'code' => $code,
                            'title' => trim((string) ($package['title'] ?? '')),
                            'summary' => isset($package['summary']) ? trim((string) $package['summary']) : null,
                            'transport_type' => (string) ($package['transport_type'] ?? CharterRequest::TYPE_JET),
                            'from_iata' => strtoupper(trim((string) ($package['from_iata'] ?? ''))),
                            'to_iata' => strtoupper(trim((string) ($package['to_iata'] ?? ''))),
                            'from_label' => isset($package['from_label']) ? trim((string) $package['from_label']) : null,
                            'to_label' => isset($package['to_label']) ? trim((string) $package['to_label']) : null,
                            'aircraft_label' => isset($package['aircraft_label']) ? trim((string) $package['aircraft_label']) : null,
                            'suggested_pax' => max(1, (int) ($package['suggested_pax'] ?? 1)),
                            'trip_type' => trim((string) ($package['trip_type'] ?? 'Tek Yon')),
                            'group_type' => isset($package['group_type']) ? trim((string) $package['group_type']) : null,
                            'cabin_preference' => $package['cabin_preference'] ?? null,
                            'price' => (float) ($package['price'] ?? 0),
                            'currency' => strtoupper(trim((string) ($package['currency'] ?? 'EUR'))),
                            'highlights' => array_values(array_filter((array) ($package['highlights_json'] ?? []))),
                            'sort_order' => max(0, (int) ($package['sort_order'] ?? 100)),
                        ],
                    ];
                })
                ->sortBy('sort_order')
                ->map(function (array $package): array {
                    unset($package['sort_order']);
                    return $package;
                })
                ->all();
        }

        return [
            'ist-ayt-economy-jet-6' => [
                'code' => 'ist-ayt-economy-jet-6',
                'title' => 'Istanbul - Antalya Ekonomik Jet',
                'summary' => '6 kisiye kadar kisa/orta mesafe jet paketi.',
                'transport_type' => CharterRequest::TYPE_JET,
                'from_iata' => 'IST',
                'to_iata' => 'AYT',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Antalya Airport',
                'aircraft_label' => 'Cessna Citation CJ2 veya benzeri',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP Tatil',
                'cabin_preference' => 'ekonomik_jet',
                'price' => 12000,
                'currency' => 'EUR',
                'highlights' => ['Hizli onay sureci', 'Kabinde ikram dahil', 'Kabine bagaj uygunlugu'],
            ],
            'saw-bod-economy-jet-6' => [
                'code' => 'saw-bod-economy-jet-6',
                'title' => 'Istanbul - Bodrum Ekonomik Jet',
                'summary' => 'Yaz sezonunda sik tercih edilen ekonomik jet paketi.',
                'transport_type' => CharterRequest::TYPE_JET,
                'from_iata' => 'SAW',
                'to_iata' => 'BJV',
                'from_label' => 'Sabiha Gokcen Airport',
                'to_label' => 'Milas-Bodrum Airport',
                'aircraft_label' => 'HondaJet / Phenom 300 sinifi',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Tatil Grubu',
                'cabin_preference' => 'ekonomik_jet',
                'price' => 10900,
                'currency' => 'EUR',
                'highlights' => ['Marina transferine uygun slot', 'Esnek bagaj opsiyonu', 'VIP lounge destegi'],
            ],
            'ist-asr-mid-jet-8' => [
                'code' => 'ist-asr-mid-jet-8',
                'title' => 'Istanbul - Kayseri Orta Segment Jet',
                'summary' => 'Is seyahati ve dag rotalari icin dengeli paket.',
                'transport_type' => CharterRequest::TYPE_JET,
                'from_iata' => 'IST',
                'to_iata' => 'ASR',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Kayseri Airport',
                'aircraft_label' => 'Legacy 450 / Challenger 300 sinifi',
                'suggested_pax' => 8,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Kurumsal',
                'cabin_preference' => 'farketmez',
                'price' => 16900,
                'currency' => 'EUR',
                'highlights' => ['Toplantiya uygun sessiz kabin', 'Wi-Fi hazirligi', 'Hizli boarding'],
            ],
            'ist-dlm-vip-jet-8' => [
                'code' => 'ist-dlm-vip-jet-8',
                'title' => 'Istanbul - Dalaman VIP Jet',
                'summary' => 'Premium servisli vip jet paketi.',
                'transport_type' => CharterRequest::TYPE_JET,
                'from_iata' => 'IST',
                'to_iata' => 'DLM',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Dalaman Airport',
                'aircraft_label' => 'Challenger 350 / Gulfstream G200',
                'suggested_pax' => 8,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP Tatil',
                'cabin_preference' => 'vip_jet',
                'price' => 22400,
                'currency' => 'EUR',
                'highlights' => ['Premium catering', 'Kabin icinde toplanti masasi', 'Bagaj hacmi yuksek'],
            ],
            'ist-izmir-economy-jet-6' => [
                'code' => 'ist-izmir-economy-jet-6',
                'title' => 'Istanbul - Izmir Ekonomik Jet',
                'summary' => 'Kisa mesafede hizli operasyon icin ideal.',
                'transport_type' => CharterRequest::TYPE_JET,
                'from_iata' => 'IST',
                'to_iata' => 'ADB',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Izmir Adnan Menderes Airport',
                'aircraft_label' => 'Citation Mustang / CJ3',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Kurumsal',
                'cabin_preference' => 'ekonomik_jet',
                'price' => 9800,
                'currency' => 'EUR',
                'highlights' => ['Ayni gun donus icin uygun', 'Hizli slot bulunurlugu', 'Dusuk operasyon maliyeti'],
            ],
            'ist-ankara-heli-4' => [
                'code' => 'ist-ankara-heli-4',
                'title' => 'Istanbul - Ankara Helikopter Transfer',
                'summary' => 'Kisa ve acil nokta transferleri icin premium helikopter.',
                'transport_type' => CharterRequest::TYPE_HELICOPTER,
                'from_iata' => 'IST',
                'to_iata' => 'ESB',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Ankara Esenboga Airport',
                'aircraft_label' => 'AW139 / Bell 429',
                'suggested_pax' => 4,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP Transfer',
                'cabin_preference' => null,
                'price' => 14500,
                'currency' => 'EUR',
                'highlights' => ['Roof-top transfer koordinasyonu', 'Hizli kalkis penceresi', 'Esnek iniş noktasi'],
            ],
            'ist-izmir-airliner-70' => [
                'code' => 'ist-izmir-airliner-70',
                'title' => 'Istanbul - Izmir Grup Charter Ucak',
                'summary' => 'Etkinlik ve ekip tasimalari icin tek sefer grup charter.',
                'transport_type' => CharterRequest::TYPE_AIRLINER,
                'from_iata' => 'IST',
                'to_iata' => 'ADB',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Izmir Adnan Menderes Airport',
                'aircraft_label' => 'Embraer 190 / A319',
                'suggested_pax' => 70,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Etkinlik Grubu',
                'cabin_preference' => null,
                'price' => 39000,
                'currency' => 'EUR',
                'highlights' => ['Toplu check-in planlamasi', 'Kurumsal branding opsiyonu', 'Yer hizmetleri paketi'],
            ],
        ];
    }
}
