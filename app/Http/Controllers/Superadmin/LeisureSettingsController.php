<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\LeisureExtraOption;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class LeisureSettingsController extends Controller
{
    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && auth()->user()->role === 'superadmin', 403);
    }

    public function index()
    {
        $this->assertAuthorized();

        $galleryAssets = LeisureMediaAsset::query()
            ->whereNotNull('package_code')
            ->where('category', 'gallery')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('package_code');

        return view('superadmin.leisure.settings', [
            'packages' => LeisurePackageTemplate::query()->orderBy('product_type')->orderBy('sort_order')->get(),
            'extras' => LeisureExtraOption::query()->orderByRaw('product_type is null desc')->orderBy('product_type')->orderBy('sort_order')->get(),
            'mediaAssets' => LeisureMediaAsset::query()->orderByRaw('product_type is null desc')->orderBy('product_type')->orderBy('sort_order')->latest('id')->get(),
            'galleryAssets' => $galleryAssets,
        ]);
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $this->validatePackage($request);
        LeisurePackageTemplate::query()->create($validated);

        return back()->with('success', 'Paket sablonu eklendi.');
    }

    public function updatePackage(Request $request, LeisurePackageTemplate $template): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $this->validatePackage($request, $template);
        $previousHeroImage = $template->hero_image_url;
        $template->update($validated);
        $this->cleanupManagedHeroImage($previousHeroImage, $validated['hero_image_url'] ?? null);

        return back()->with('success', 'Paket sablonu guncellendi.');
    }

    public function storeExtra(Request $request): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $this->validateExtra($request);
        LeisureExtraOption::query()->create($validated);

        return back()->with('success', 'Ekstra secenegi eklendi.');
    }

    public function updateExtra(Request $request, LeisureExtraOption $option): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $this->validateExtra($request, $option->id);
        $option->update($validated);

        return back()->with('success', 'Ekstra secenegi guncellendi.');
    }

    public function storeMedia(Request $request): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $this->validateMedia($request);
        LeisureMediaAsset::query()->create($validated);

        return back()->with('success', 'Medya kaydi eklendi.');
    }

    public function updateMedia(Request $request, LeisureMediaAsset $asset): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $this->validateMedia($request, true);
        $asset->update(array_filter($validated, fn ($value) => $value !== '__KEEP__'));

        return back()->with('success', 'Medya kaydi guncellendi.');
    }

    public function storeGalleryPhoto(Request $request, LeisurePackageTemplate $template): RedirectResponse
    {
        $this->assertAuthorized();

        $existing = LeisureMediaAsset::query()
            ->where('product_type', $template->product_type)
            ->where('package_code', $template->code)
            ->where('category', 'gallery')
            ->where('is_active', true)
            ->count();

        if ($existing >= 6) {
            return back()->with('error', 'Bu paket icin zaten 6 galeri fotografi var. Once birini silin.');
        }

        $request->validate([
            'gallery_photos'   => 'required|array|min:1',
            'gallery_photos.*' => 'file|max:51200',
            'gallery_title'    => 'nullable|string|max:100',
        ]);

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'mp4', 'webm', 'mov', 'mkv', 'avi'];
        $videoExts   = ['mp4', 'webm', 'mov', 'mkv', 'avi'];
        $files      = $request->file('gallery_photos');
        $slots      = 6 - $existing;
        $added      = 0;
        $directory  = '/uploads/leisure-gallery/' . $template->code;
        File::ensureDirectoryExists(public_path($directory));

        foreach (array_slice($files, 0, $slots) as $file) {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, $allowedExts, true)) {
                continue; // desteklenmeyen uzanti — atla
            }
            $mediaType = in_array($ext, $videoExts, true) ? 'video' : 'photo';
            $filename  = uniqid('gallery_', true) . '.' . $ext;
            $file->move(public_path($directory), $filename);

            LeisureMediaAsset::query()->create([
                'product_type' => $template->product_type,
                'package_code' => $template->code,
                'category'     => 'gallery',
                'media_type'   => $mediaType,
                'source_type'  => 'upload',
                'title_tr'     => trim((string) $request->input('gallery_title')) ?: ($template->name_tr . ' Galeri'),
                'file_path'    => $directory . '/' . $filename,
                'is_active'    => true,
                'sort_order'   => $existing + $added + 1,
            ]);

            $added++;
        }

        $total   = $existing + $added;
        $skipped = count($files) - $added;
        $msg     = $added . ' fotograf eklendi (' . $total . '/6).';
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' dosya limit asimi nedeniyle atland.';
        }

        return back()->with('success', $msg);
    }

    public function deleteGalleryPhoto(Request $request, LeisureMediaAsset $asset): RedirectResponse
    {
        $this->assertAuthorized();

        if ($asset->file_path && str_starts_with($asset->file_path, '/uploads/leisure-gallery/')) {
            $absolutePath = public_path(ltrim($asset->file_path, '/'));
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }
        }

        $asset->delete();

        return back()->with('success', 'Galeri fotografi silindi.');
    }

    private function validatePackage(Request $request, ?LeisurePackageTemplate $currentTemplate = null): array
    {
        $validated = $request->validate([
            'product_type' => 'required|in:dinner_cruise,yacht',
            'code' => 'required|string|max:40',
            'level' => 'required|string|max:30',
            'name_tr' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'summary_tr' => 'nullable|string|max:255',
            'summary_en' => 'nullable|string|max:255',
            'hero_image_url' => 'nullable|string|max:1000',
            'hero_image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,avif|max:10240',
            'clear_hero_image' => 'nullable|boolean',
            'includes_tr_text' => 'nullable|string|max:5000',
            'includes_en_text' => 'nullable|string|max:5000',
            'excludes_tr_text' => 'nullable|string|max:5000',
            'excludes_en_text' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            // Catalog fields
            'base_price_per_person' => 'nullable|numeric|min:0',
            'original_price_per_person' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'duration_hours' => 'nullable|numeric|min:0|max:24',
            'departure_times_text' => 'nullable|string|max:1000',
            'pier_name' => 'nullable|string|max:100',
            'meeting_point' => 'nullable|string|max:500',
            'max_pax' => 'nullable|integer|min:1|max:10000',
            'badge_text' => 'nullable|string|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'long_description_tr' => 'nullable|string|max:10000',
            'long_description_en' => 'nullable|string|max:10000',
            'timeline_tr_json' => 'nullable|string|max:20000',
            'cancellation_policy_tr' => 'nullable|string|max:2000',
            'important_notes_tr_text' => 'nullable|string|max:5000',
        ]);

        $code = strtolower(trim($validated['code']));
        $exists = LeisurePackageTemplate::query()
            ->where('product_type', $validated['product_type'])
            ->where('code', $code)
            ->when($currentTemplate?->id, fn ($query) => $query->whereKeyNot($currentTemplate->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Bu urun icin ayni kodla paket sablonu zaten var.',
            ]);
        }

        $heroImageUrl = $this->normalizeHeroImageUrl($validated['hero_image_url'] ?? null);

        if ($request->boolean('clear_hero_image')) {
            $heroImageUrl = null;
        }

        if ($request->hasFile('hero_image_file')) {
            $heroImageUrl = $this->storePackageHeroImage($request->file('hero_image_file'));
        }

        return [
            'product_type' => $validated['product_type'],
            'code' => $code,
            'level' => strtolower(trim($validated['level'])),
            'name_tr' => trim($validated['name_tr']),
            'name_en' => trim($validated['name_en']),
            'summary_tr' => $validated['summary_tr'] ?? null,
            'summary_en' => $validated['summary_en'] ?? null,
            'hero_image_url' => $heroImageUrl,
            'includes_tr' => $this->parseListText($validated['includes_tr_text'] ?? null),
            'includes_en' => $this->parseListText($validated['includes_en_text'] ?? null),
            'excludes_tr' => $this->parseListText($validated['excludes_tr_text'] ?? null),
            'excludes_en' => $this->parseListText($validated['excludes_en_text'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 100,
            // Catalog fields
            'base_price_per_person' => isset($validated['base_price_per_person']) && $validated['base_price_per_person'] !== '' ? (float) $validated['base_price_per_person'] : null,
            'original_price_per_person' => isset($validated['original_price_per_person']) && $validated['original_price_per_person'] !== '' ? (float) $validated['original_price_per_person'] : null,
            'currency' => strtoupper(trim($validated['currency'] ?? 'TRY')) ?: 'TRY',
            'duration_hours' => isset($validated['duration_hours']) && $validated['duration_hours'] !== '' ? (float) $validated['duration_hours'] : null,
            'departure_times' => $this->parseListText($validated['departure_times_text'] ?? null),
            'pier_name' => isset($validated['pier_name']) ? trim((string) $validated['pier_name']) ?: null : null,
            'meeting_point' => isset($validated['meeting_point']) ? trim((string) $validated['meeting_point']) ?: null : null,
            'max_pax' => $validated['max_pax'] ?? null,
            'badge_text' => isset($validated['badge_text']) ? trim((string) $validated['badge_text']) ?: null : null,
            'rating' => isset($validated['rating']) && $validated['rating'] !== '' ? (float) $validated['rating'] : null,
            'review_count' => $validated['review_count'] ?? null,
            'long_description_tr' => isset($validated['long_description_tr']) ? trim((string) $validated['long_description_tr']) ?: null : null,
            'long_description_en' => isset($validated['long_description_en']) ? trim((string) $validated['long_description_en']) ?: null : null,
            'timeline_tr' => $this->parseJsonField($validated['timeline_tr_json'] ?? null),
            'cancellation_policy_tr' => isset($validated['cancellation_policy_tr']) ? trim((string) $validated['cancellation_policy_tr']) ?: null : null,
            'important_notes_tr' => $this->parseListText($validated['important_notes_tr_text'] ?? null),
        ];
    }

    private function normalizeHeroImageUrl(?string $heroImageUrl): ?string
    {
        $heroImageUrl = trim((string) $heroImageUrl);
        if ($heroImageUrl === '') {
            return null;
        }

        if (str_starts_with($heroImageUrl, 'http://') || str_starts_with($heroImageUrl, 'https://')) {
            return $heroImageUrl;
        }

        return '/' . ltrim($heroImageUrl, '/');
    }

    private function storePackageHeroImage(UploadedFile $file): string
    {
        $directory = '/uploads/leisure-package-heroes/' . now()->format('Y/m');
        File::ensureDirectoryExists(public_path($directory));
        $filename = uniqid('leisure_package_', true) . '.' . strtolower((string) $file->getClientOriginalExtension());
        $file->move(public_path($directory), $filename);

        return $directory . '/' . $filename;
    }

    private function cleanupManagedHeroImage(?string $previousPath, ?string $nextPath = null): void
    {
        $previousPath = trim((string) $previousPath);
        if ($previousPath === '') {
            return;
        }

        if ($nextPath !== null && trim((string) $nextPath) === $previousPath) {
            return;
        }

        if (! str_starts_with($previousPath, '/uploads/leisure-package-heroes/')) {
            return;
        }

        $absolutePath = public_path(ltrim($previousPath, '/'));
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function validateExtra(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'product_type' => 'nullable|in:dinner_cruise,yacht',
            'category' => 'required|string|max:40',
            'code' => 'required|string|max:50',
            'title_tr' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_tr' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:255',
            'default_included' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $code = strtolower(trim($validated['code']));
        $productType = $validated['product_type'] ?? null;
        $exists = LeisureExtraOption::query()
            ->where('code', $code)
            ->when(
                $productType !== null,
                fn ($query) => $query->where('product_type', $productType),
                fn ($query) => $query->whereNull('product_type')
            )
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Bu urun ve kod icin ekstra secenegi zaten var.',
            ]);
        }

        return [
            'product_type' => $productType,
            'category' => strtolower(trim($validated['category'])),
            'code' => $code,
            'title_tr' => trim($validated['title_tr']),
            'title_en' => trim($validated['title_en']),
            'description_tr' => $validated['description_tr'] ?? null,
            'description_en' => $validated['description_en'] ?? null,
            'default_included' => (bool) ($validated['default_included'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 100,
        ];
    }

    private function validateMedia(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'product_type' => 'nullable|in:dinner_cruise,yacht',
            'category' => 'nullable|string|max:50',
            'media_type' => 'required|in:photo,video',
            'source_type' => 'required|in:upload,link',
            'title_tr' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'external_url' => 'nullable|url|max:1000|required_if:source_type,link',
            'upload_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,avif,mp4,mov,avi|required_if:source_type,upload',
            'tags_text' => 'nullable|string|max:2000',
            'capacity_min' => 'nullable|integer|min:1|max:10000',
            'capacity_max' => 'nullable|integer|min:1|max:10000|gte:capacity_min',
            'luxury_level' => 'nullable|string|max:30',
            'usage_type' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $filePath = $updating ? '__KEEP__' : null;
        if (($validated['source_type'] ?? null) === 'link') {
            $filePath = null;
        } elseif ($request->hasFile('upload_file')) {
            $directory = '/uploads/leisure-media/' . now()->format('Y/m');
            File::ensureDirectoryExists(public_path($directory));
            $file = $request->file('upload_file');
            $filename = uniqid('leisure_', true) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path($directory), $filename);
            $filePath = $directory . '/' . $filename;
        }

        return [
            'product_type' => $validated['product_type'] ?? null,
            'category' => $validated['category'] ?? null,
            'media_type' => $validated['media_type'],
            'source_type' => $validated['source_type'],
            'title_tr' => trim($validated['title_tr']),
            'title_en' => isset($validated['title_en']) ? trim((string) $validated['title_en']) : null,
            'file_path' => $filePath,
            'external_url' => ($validated['source_type'] ?? null) === 'link' ? ($validated['external_url'] ?? null) : null,
            'tags_json' => $this->parseTagText($validated['tags_text'] ?? null),
            'capacity_min' => $validated['capacity_min'] ?? null,
            'capacity_max' => $validated['capacity_max'] ?? null,
            'luxury_level' => $validated['luxury_level'] ?? null,
            'usage_type' => $validated['usage_type'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 100,
        ];
    }

    private function parseJsonField(?string $json): array
    {
        $json = trim((string) $json);
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function parseListText(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim((string) preg_replace('/^[-*•\s]+/u', '', (string) $line)))
            ->filter()
            ->values()
            ->all();
    }

    private function parseTagText(?string $text): array
    {
        return collect(preg_split('/[\r\n,;]+/', (string) $text))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
