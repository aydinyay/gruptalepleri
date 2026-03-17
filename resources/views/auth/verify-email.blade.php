<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Kaydiniz icin tesekkurler. Devam etmeden once e-posta adresinizi dogrulamaniz gerekiyor. Size gonderdigimiz baglantiya tiklayin. E-posta gelmediyse yeniden gonderebiliriz.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Kayit sirasinda verdiginiz e-posta adresine yeni dogrulama baglantisi gonderildi.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>Dogrulama E-postasini Tekrar Gonder</x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cikis Yap</button>
        </form>
    </div>
</x-guest-layout>
