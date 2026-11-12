<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Merci pour votre inscription. Cliquez sur le lien reçu par e-mail pour vérifier votre adresse. Si vous ne l’avez pas reçu, nous pouvons vous en envoyer un nouveau.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Un nouveau lien de vérification a été envoyé à l’adresse e-mail indiquée lors de votre inscription.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Renvoyer l’e-mail de vérification
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                Se déconnecter
            </button>
        </form>
    </div>
</x-guest-layout>
