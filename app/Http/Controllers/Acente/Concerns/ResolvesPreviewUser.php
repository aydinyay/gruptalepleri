<?php

namespace App\Http\Controllers\Acente\Concerns;

use App\Models\User;

trait ResolvesPreviewUser
{
    protected function acenteActor(): User
    {
        $authUser = auth()->user();

        if (! $authUser) {
            abort(403);
        }

        if (! in_array($authUser->role, ['admin', 'superadmin'], true)) {
            return $authUser;
        }

        $previewUserId = session('acente_preview_user_id');
        if (! $previewUserId) {
            return $authUser;
        }

        $previewUser = User::with('agency')
            ->where('role', 'acente')
            ->find($previewUserId);

        if (! $previewUser) {
            session()->forget('acente_preview_user_id');
            return $authUser;
        }

        return $previewUser;
    }

    protected function isAcentePreviewMode(): bool
    {
        $authUser = auth()->user();
        if (! $authUser || ! in_array($authUser->role, ['admin', 'superadmin'], true)) {
            return false;
        }

        $previewUserId = (int) session('acente_preview_user_id', 0);
        if ($previewUserId <= 0) {
            return false;
        }

        $exists = User::where('role', 'acente')->whereKey($previewUserId)->exists();
        if (! $exists) {
            session()->forget('acente_preview_user_id');
            return false;
        }

        return true;
    }

    protected function blockPreviewWrites()
    {
        if (! $this->isAcentePreviewMode()) {
            return null;
        }

        $message = 'Acente onizleme modunda degisiklik yapilamaz.';

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['error' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
