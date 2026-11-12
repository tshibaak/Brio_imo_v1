<nav x-data="{ open: false }" class="border-b border-neutral-800 bg-neutral-950">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="text-xl font-black tracking-tight text-white">BRIO<span class="text-orange-500">.</span></a>
            <div class="hidden items-center gap-1 md:flex">
                <a class="nav-link" @if (request()->routeIs('dashboard')) aria-current="page" @endif href="{{ route('dashboard') }}">Tableau de bord</a>
                @if (Auth::user()->isLandlord())
                    <a class="nav-link" @if (request()->routeIs('properties.*')) aria-current="page" @endif href="{{ route('properties.index') }}">Biens</a>
                    <a class="nav-link" @if (request()->routeIs('tenants.*')) aria-current="page" @endif href="{{ route('tenants.index') }}">Locataires</a>
                    <a class="nav-link" @if (request()->routeIs('contracts.*')) aria-current="page" @endif href="{{ route('contracts.index') }}">Contrats</a>
                    <a class="nav-link" @if (request()->routeIs('payments.*')) aria-current="page" @endif href="{{ route('payments.index') }}">Paiements</a>
                    <a class="nav-link" @if (request()->routeIs('tickets.*')) aria-current="page" @endif href="{{ route('tickets.index') }}">Interventions</a>
                    <a class="nav-link" @if (request()->routeIs('expenses.*')) aria-current="page" @endif href="{{ route('expenses.index') }}">Dépenses</a>
                    <a class="nav-link" @if (request()->routeIs('reports.*')) aria-current="page" @endif href="{{ route('reports.index') }}">Rapports</a>
                @else
                    <a class="nav-link" @if (request()->routeIs('tenant.invoices.*')) aria-current="page" @endif href="{{ route('tenant.invoices.index') }}">Mes factures</a>
                    <a class="nav-link" @if (request()->routeIs('tickets.*')) aria-current="page" @endif href="{{ route('tickets.index') }}">Mes demandes</a>
                @endif
            </div>
        </div>
        <div class="hidden items-center gap-4 md:flex">
            <span class="text-sm text-stone-300">{{ Auth::user()->name }}</span>
            <a class="nav-link" @if (request()->routeIs('profile.*')) aria-current="page" @endif href="{{ route('profile.edit') }}">Profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rounded-lg px-3 py-2 text-sm font-semibold text-stone-300 hover:bg-white/10 hover:text-white">Déconnexion</button>
            </form>
        </div>
        <button class="rounded-lg p-2 text-stone-200 hover:bg-white/10 md:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mobile-navigation" aria-label="Ouvrir le menu">☰</button>
    </div>
    <div id="mobile-navigation" x-show="open" x-transition class="space-y-1 border-t border-neutral-800 px-4 py-3 md:hidden">
        <a class="nav-link block" href="{{ route('dashboard') }}">Tableau de bord</a>
        @if (Auth::user()->isLandlord())
            <a class="nav-link block" href="{{ route('properties.index') }}">Biens</a>
            <a class="nav-link block" href="{{ route('tenants.index') }}">Locataires</a>
            <a class="nav-link block" href="{{ route('contracts.index') }}">Contrats</a>
            <a class="nav-link block" href="{{ route('payments.index') }}">Paiements</a>
            <a class="nav-link block" href="{{ route('tickets.index') }}">Interventions</a>
            <a class="nav-link block" href="{{ route('expenses.index') }}">Dépenses</a>
            <a class="nav-link block" href="{{ route('reports.index') }}">Rapports</a>
        @else
            <a class="nav-link block" href="{{ route('tenant.invoices.index') }}">Mes factures</a>
            <a class="nav-link block" href="{{ route('tickets.index') }}">Mes demandes</a>
        @endif
        <a class="nav-link block" href="{{ route('profile.edit') }}">Profil</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-link w-full text-left">Déconnexion</button></form>
    </div>
</nav>
