<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CharterPresetPackage;
use App\Models\SistemAyar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class CharterPresetPackageController extends Controller
{
    private const SETTINGS_KEY = 'charter_preset_packages_json';
    private const HERO_IMAGE_UPLOAD_DIR = 'charter/preset-packages';
    private const HERO_IMAGE_STORAGE_PREFIX = '/storage/' . self::HERO_IMAGE_UPLOAD_DIR . '/';

    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }

    public function index()
    {
        $this->assertAuthorized();

        return view('superadmin.charter-preset-packages', [
            'packages' => $this->loadPackages(),
            'usesDatabase' => $this->usesDatabaseStorage(),
            'heroImageFeatureReady' => $this->isHeroImageFeatureReady(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertAuthorized();

        $payload = $this->validatePayload($request);
        $this->ensureHeroImageFeatureReady($request);
        $payload['hero_image_url'] = $this->resolveHeroImageUrl($request);

        if ($this->usesDatabaseStorage()) {
            $this->omitHeroImagePayloadWhenColumnMissing($payload);
            CharterPresetPackage::query()->create($payload);
        } else {
            $packages = $this->loadPackages();
            $packages[] = $payload;
            $this->savePackagesToSettings($packages);
        }

        return back()->with('success', 'Hazir paket eklendi.');
    }

    public function update(Request $request, string $packageCode): RedirectResponse
    {
        $this->assertAuthorized();

        if ($this->usesDatabaseStorage()) {
            $current = CharterPresetPackage::query()
                ->where('code', strtolower(trim($packageCode)))
                ->firstOrFail();

            $payload = $this->validatePayload($request, $current->code);
            $this->ensureHeroImageFeatureReady($request);
            $payload['hero_image_url'] = $this->resolveHeroImageUrl($request, $current->hero_image_url);
            $this->omitHeroImagePayloadWhenColumnMissing($payload);

            $current->update($payload);

            return back()->with('success', 'Hazir paket guncellendi.');
        }

        $packages = $this->loadPackages();
        $currentCode = strtolower(trim($packageCode));
        $index = collect($packages)->search(fn (array $item) => ($item['code'] ?? '') === $currentCode);

        if ($index === false) {
            return back()->with('error', 'Guncellenecek hazir paket bulunamadi.');
        }

        $current = (array) ($packages[$index] ?? []);
        $payload = $this->validatePayload($request, $currentCode, $packages);
        $this->ensureHeroImageFeatureReady($request);
        $payload['hero_image_url'] = $this->resolveHeroImageUrl($request, $current['hero_image_url'] ?? null);
        $packages[$index] = $payload;
        $this->savePackagesToSettings($packages);

        return back()->with('success', 'Hazir paket guncellendi.');
    }

    public function destroy(string $packageCode): RedirectResponse
    {
        $this->assertAuthorized();
        $currentCode = strtolower(trim($packageCode));

        if ($this->usesDatabaseStorage()) {
            $package = CharterPresetPackage::query()
                ->where('code', $currentCode)
                ->firstOrFail();
            $this->cleanupManagedHeroImage($package->hero_image_url);
            $package->delete();

            return back()->with('success', 'Hazir paket silindi.');
        }

        $packages = $this->loadPackages();
        $target = collect($packages)
            ->first(fn (array $item): bool => ($item['code'] ?? '') === $currentCode);
        if ($target) {
            $this->cleanupManagedHeroImage($target['hero_image_url'] ?? null);
        }

        $packages = collect($packages)
            ->reject(fn (array $item) => ($item['code'] ?? '') === $currentCode)
            ->values()
            ->all();

        $this->savePackagesToSettings($packages);

        return back()->with('success', 'Hazir paket silindi.');
    }

    private function validatePayload(Request $request, ?string $ignoreCode = null, ?array $existingPackages = null): array
    {
        $validated = $request->validate([
            'code' => 'required|string|max:80|regex:/^[a-z0-9-]+$/',
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:255',
            'transport_type' => 'required|in:jet,helicopter,airliner',
            'from_iata' => 'required|string|max:10',
            'to_iata' => 'required|string|max:10',
            'from_label' => 'nullable|string|max:255',
            'to_label' => 'nullable|string|max:255',
            'aircraft_label' => 'nullable|string|max:255',
            'suggested_pax' => 'required|integer|min:1|max:400',
            'trip_type' => 'nullable|string|max:50',
            'group_type' => 'nullable|string|max:120',
            'cabin_preference' => 'nullable|in:ekonomik_jet,vip_jet,farketmez',
            'price' => 'required|numeric|min:0|max:999999999.99',
            'currency' => 'required|string|max:8',
            'hero_image_url' => 'nullable|string|max:2048',
            'hero_image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:6144',
            'highlights_text' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $code = strtolower(trim((string) $validated['code']));
        $ignore = $ignoreCode ? strtolower(trim($ignoreCode)) : null;

        if ($this->usesDatabaseStorage()) {
            $exists = CharterPresetPackage::query()
                ->where('code', $code)
                ->when($ignore, fn ($query) => $query->where('code', '!=', $ignore))
                ->exists();
        } else {
            $rows = $existingPackages ?? $this->loadPackages();
            $exists = collect($rows)->contains(function (array $item) use ($code, $ignore): bool {
                $itemCode = strtolower(trim((string) ($item['code'] ?? '')));
                if ($itemCode === '') {
                    return false;
                }
                if ($ignore !== null && $itemCode === $ignore) {
                    return false;
                }
                return $itemCode === $code;
            });
        }

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Bu kodla hazir paket zaten var.',
            ]);
        }

        return [
            'code' => $code,
            'title' => trim((string) $validated['title']),
            'summary' => isset($validated['summary']) ? trim((string) $validated['summary']) : null,
            'transport_type' => $validated['transport_type'],
            'from_iata' => strtoupper(trim((string) $validated['from_iata'])),
            'to_iata' => strtoupper(trim((string) $validated['to_iata'])),
            'from_label' => isset($validated['from_label']) ? trim((string) $validated['from_label']) : null,
            'to_label' => isset($validated['to_label']) ? trim((string) $validated['to_label']) : null,
            'aircraft_label' => isset($validated['aircraft_label']) ? trim((string) $validated['aircraft_label']) : null,
            'suggested_pax' => (int) $validated['suggested_pax'],
            'trip_type' => trim((string) ($validated['trip_type'] ?? 'Tek Yon')),
            'group_type' => isset($validated['group_type']) ? trim((string) $validated['group_type']) : null,
            'cabin_preference' => $validated['cabin_preference'] ?? null,
            'price' => (float) $validated['price'],
            'currency' => strtoupper(trim((string) $validated['currency'])),
            'hero_image_url' => $this->normalizeHeroImageUrl($validated['hero_image_url'] ?? null),
            'highlights_json' => $this->parseList((string) ($validated['highlights_text'] ?? '')),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 100),
        ];
    }

    private function usesDatabaseStorage(): bool
    {
        return Schema::hasTable('charter_preset_packages');
    }

    private function usesHeroImageColumn(): bool
    {
        return $this->usesDatabaseStorage() && Schema::hasColumn('charter_preset_packages', 'hero_image_url');
    }

    private function isHeroImageFeatureReady(): bool
    {
        if (! $this->usesDatabaseStorage()) {
            return true;
        }

        return $this->usesHeroImageColumn();
    }

    private function ensureHeroImageFeatureReady(Request $request): void
    {
        if (! $this->usesDatabaseStorage()) {
            return;
        }

        $hasManualUrl = $this->normalizeHeroImageUrl($request->input('hero_image_url')) !== null;
        $hasUpload = $request->hasFile('hero_image_file');

        if (! $hasManualUrl && ! $hasUpload) {
            return;
        }

        if ($this->usesHeroImageColumn()) {
            return;
        }

        $this->createHeroImageColumnIfMissing();
        if ($this->usesHeroImageColumn()) {
            return;
        }

        throw ValidationException::withMessages([
            'hero_image_url' => 'Hero gorsel alani hazir degil. Lutfen once `php artisan migrate --force` calistirin.',
        ]);
    }

    private function omitHeroImagePayloadWhenColumnMissing(array &$payload): void
    {
        if (! $this->usesDatabaseStorage() || $this->usesHeroImageColumn()) {
            return;
        }

        unset($payload['hero_image_url']);
    }

    private function createHeroImageColumnIfMissing(): void
    {
        if (! $this->usesDatabaseStorage() || $this->usesHeroImageColumn()) {
            return;
        }

        try {
            Schema::table('charter_preset_packages', function (Blueprint $table): void {
                if (! Schema::hasColumn('charter_preset_packages', 'hero_image_url')) {
                    $table->string('hero_image_url')->nullable()->after('currency');
                }
            });
        } catch (\Throwable) {
            // If schema change is blocked in runtime, validation error above informs operator.
        }
    }

    private function loadPackages(): array
    {
        if ($this->usesDatabaseStorage()) {
            return CharterPresetPackage::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(function (CharterPresetPackage $item): array {
                    return [
                        'code' => $item->code,
                        'title' => $item->title,
                        'summary' => $item->summary,
                        'transport_type' => $item->transport_type,
                        'from_iata' => $item->from_iata,
                        'to_iata' => $item->to_iata,
                        'from_label' => $item->from_label,
                        'to_label' => $item->to_label,
                        'aircraft_label' => $item->aircraft_label,
                        'suggested_pax' => (int) $item->suggested_pax,
                        'trip_type' => $item->trip_type,
                        'group_type' => $item->group_type,
                        'cabin_preference' => $item->cabin_preference,
                        'price' => (float) $item->price,
                        'currency' => $item->currency,
                        'hero_image_url' => $this->normalizeHeroImageUrl($item->hero_image_url),
                        'highlights_json' => array_values(array_filter((array) ($item->highlights_json ?? []))),
                        'is_active' => (bool) $item->is_active,
                        'sort_order' => (int) $item->sort_order,
                    ];
                })
                ->all();
        }

        $raw = (string) SistemAyar::get(self::SETTINGS_KEY, '[]');
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn ($item) => $this->normalizeStoredRow((array) $item))
            ->sortBy(['sort_order', 'code'])
            ->values()
            ->all();
    }

    private function savePackagesToSettings(array $packages): void
    {
        $rows = collect($packages)
            ->map(fn ($item) => $this->normalizeStoredRow((array) $item))
            ->sortBy(['sort_order', 'code'])
            ->values()
            ->all();

        SistemAyar::set(self::SETTINGS_KEY, json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeStoredRow(array $item): array
    {
        return [
            'code' => strtolower(trim((string) ($item['code'] ?? ''))),
            'title' => trim((string) ($item['title'] ?? '')),
            'summary' => isset($item['summary']) ? trim((string) $item['summary']) : null,
            'transport_type' => (string) ($item['transport_type'] ?? 'jet'),
            'from_iata' => strtoupper(trim((string) ($item['from_iata'] ?? ''))),
            'to_iata' => strtoupper(trim((string) ($item['to_iata'] ?? ''))),
            'from_label' => isset($item['from_label']) ? trim((string) $item['from_label']) : null,
            'to_label' => isset($item['to_label']) ? trim((string) $item['to_label']) : null,
            'aircraft_label' => isset($item['aircraft_label']) ? trim((string) $item['aircraft_label']) : null,
            'suggested_pax' => max(1, (int) ($item['suggested_pax'] ?? 1)),
            'trip_type' => trim((string) ($item['trip_type'] ?? 'Tek Yon')),
            'group_type' => isset($item['group_type']) ? trim((string) $item['group_type']) : null,
            'cabin_preference' => $item['cabin_preference'] ?? null,
            'price' => (float) ($item['price'] ?? 0),
            'currency' => strtoupper(trim((string) ($item['currency'] ?? 'EUR'))),
            'hero_image_url' => $this->normalizeHeroImageUrl($item['hero_image_url'] ?? null),
            'highlights_json' => array_values(array_filter((array) ($item['highlights_json'] ?? []))),
            'is_active' => (bool) ($item['is_active'] ?? false),
            'sort_order' => max(0, (int) ($item['sort_order'] ?? 100)),
        ];
    }

    private function resolveHeroImageUrl(Request $request, ?string $currentUrl = null): ?string
    {
        $currentNormalized = $this->normalizeHeroImageUrl($currentUrl);
        $manualUrl = $this->normalizeHeroImageUrl($request->input('hero_image_url'));

        if ($request->hasFile('hero_image_file')) {
            $finalUrl = $this->storeHeroImageFilePublicly($request->file('hero_image_file'));

            if ($currentNormalized !== null && $currentNormalized !== $finalUrl) {
                $this->cleanupManagedHeroImage($currentNormalized);
            }

            return $finalUrl;
        }

        if ($request->boolean('hero_image_remove')) {
            if ($currentNormalized !== null) {
                $this->cleanupManagedHeroImage($currentNormalized);
            }

            return null;
        }

        if ($currentNormalized !== null && $manualUrl !== $currentNormalized) {
            $this->cleanupManagedHeroImage($currentNormalized);
        }

        return $manualUrl;
    }

    private function normalizeHeroImageUrl(mixed $value): ?string
    {
        $url = trim((string) ($value ?? ''));
        if ($url === '') {
            return null;
        }

        return $url;
    }

    private function cleanupManagedHeroImage(?string $url): void
    {
        $normalizedUrl = $this->normalizeHeroImageUrl($url);
        if ($normalizedUrl === null || ! Str::startsWith($normalizedUrl, self::HERO_IMAGE_STORAGE_PREFIX)) {
            return;
        }

        $relativePath = Str::after($normalizedUrl, '/storage/');
        if ($relativePath === '') {
            return;
        }

        Storage::disk('public')->delete($relativePath);
        $publicPath = public_path(str_replace('/', DIRECTORY_SEPARATOR, 'storage/' . ltrim($relativePath, '/')));
        if (is_file($publicPath)) {
            @unlink($publicPath);
        }
    }

    private function storeHeroImageFilePublicly(UploadedFile $file): string
    {
        $directory = public_path(str_replace('/', DIRECTORY_SEPARATOR, 'storage/' . self::HERO_IMAGE_UPLOAD_DIR));
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg'));
        if ($extension === '') {
            $extension = 'jpg';
        }

        $filename = now()->format('YmdHis') . '-' . Str::lower(Str::random(12)) . '.' . $extension;
        $file->move($directory, $filename);

        return self::HERO_IMAGE_STORAGE_PREFIX . $filename;
    }

    private function parseList(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(static fn ($line) => trim((string) preg_replace('/^[-*\s]+/u', '', (string) $line)))
            ->filter()
            ->values()
            ->all();
    }
}
