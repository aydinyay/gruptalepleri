@auth
    @php
        $role = auth()->user()->role ?? 'acente';
        $routeName = request()->route()?->getName() ?? '';
        $active = 'dashboard';

        if ($routeName === 'profile.edit' || str_starts_with($routeName, 'profile.')) {
            $active = 'hesap';
        } elseif ($role === 'superadmin') {
            if (str_starts_with($routeName, 'superadmin.acenteler')) {
                $active = 'acenteler';
            } elseif (str_starts_with($routeName, 'superadmin.transfer')) {
                $active = 'transfer';
            } elseif (str_starts_with($routeName, 'superadmin.tursab.')) {
                $active = 'tursab-kampanya';
            } elseif (str_starts_with($routeName, 'superadmin.charter.packages')) {
                $active = 'charter-packages';
            } elseif (str_starts_with($routeName, 'superadmin.charter.rfq-suppliers')) {
                $active = 'charter-rfq-suppliers';
            } elseif (str_starts_with($routeName, 'superadmin.charter')) {
                $active = 'charter';
            } elseif (str_starts_with($routeName, 'superadmin.dinner-cruise')) {
                $active = 'dinner-cruise';
            } elseif (str_starts_with($routeName, 'superadmin.yacht-charter')) {
                $active = 'yacht-charter';
            } elseif (str_starts_with($routeName, 'superadmin.finance.receipts')) {
                $active = 'finance-receipts';
            } elseif (str_starts_with($routeName, 'superadmin.finance')) {
                $active = 'finance';
            } elseif (str_starts_with($routeName, 'superadmin.quick-reply')) {
                $active = 'quick-reply';
            } elseif (str_starts_with($routeName, 'superadmin.broadcast')) {
                $active = 'broadcast';
            } elseif (str_starts_with($routeName, 'superadmin.sms.raporlar')) {
                $active = 'sms-raporlar';
            } elseif (str_starts_with($routeName, 'superadmin.sistem.olaylar')) {
                $active = 'sistem-olaylar';
            } elseif (str_starts_with($routeName, 'superadmin.blog.kategoriler')) {
                $active = 'blog-kategoriler';
            } elseif (str_starts_with($routeName, 'superadmin.sms.ayarlar')) {
                $active = 'sms-ayarlar';
            } elseif (str_starts_with($routeName, 'superadmin.ai-kutlama')) {
                $active = 'ai-kutlama';
            } elseif (str_starts_with($routeName, 'superadmin.leisure.settings')) {
                $active = 'leisure-settings';
            } elseif (str_starts_with($routeName, 'superadmin.site.ayarlar')) {
                $active = request()->query('sekme') === 'ai' ? 'ai-kutlama' : 'site-ayarlar';
            }
        } elseif ($role === 'admin') {
            if (str_starts_with($routeName, 'admin.requests')) {
                $active = 'talepler';
            } elseif (str_starts_with($routeName, 'admin.transfer')) {
                $active = 'transfer';
            } elseif (str_starts_with($routeName, 'admin.charter')) {
                $active = 'charter';
            } elseif (str_starts_with($routeName, 'admin.dinner-cruise')) {
                $active = 'dinner-cruise';
            } elseif (str_starts_with($routeName, 'admin.yacht-charter')) {
                $active = 'yacht-charter';
            } elseif (str_starts_with($routeName, 'admin.finance')) {
                $active = 'finance';
            } elseif (str_starts_with($routeName, 'admin.quick-reply')) {
                $active = 'quick-reply';
            } elseif (str_starts_with($routeName, 'admin.broadcast')) {
                $active = 'broadcast';
            } elseif (str_starts_with($routeName, 'admin.eski-sistem')) {
                $active = 'eski-sistem';
            }
        } else {
            if (str_starts_with($routeName, 'acente.charter')) {
                $active = 'charter';
            } elseif (str_starts_with($routeName, 'acente.transfer.supplier.terms')) {
                $active = 'transfer-supplier-terms';
            } elseif (str_starts_with($routeName, 'acente.transfer.supplier.')) {
                $active = 'transfer-supplier';
            } elseif (str_starts_with($routeName, 'acente.transfer')) {
                $active = 'transfer';
            } elseif (str_starts_with($routeName, 'acente.dinner-cruise')) {
                $active = 'dinner-cruise';
            } elseif (str_starts_with($routeName, 'acente.yacht-charter')) {
                $active = 'yacht-charter';
            } elseif (str_starts_with($routeName, 'acente.finance')) {
                $active = 'finance';
            } elseif (str_starts_with($routeName, 'acente.requests.')) {
                $active = 'create';
            } elseif (str_starts_with($routeName, 'acente.profil')) {
                $active = 'profil';
            }
        }
    @endphp

    @if($role === 'superadmin')
        <x-navbar-superadmin :active="$active" />
    @elseif($role === 'admin')
        <x-navbar-admin :active="$active" />
    @else
        <x-navbar-acente :active="$active" />
    @endif
@else
    @include('layouts.navigation')
@endauth
