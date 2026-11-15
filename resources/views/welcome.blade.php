<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BRIO-IMMO — Gestion locative simplifiée</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6">
        <a class="text-2xl font-black tracking-tight" href="/">BRIO-IMMO</a>
        <div class="flex gap-3">
            <a class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10" href="{{ route('login') }}">Connexion</a>
            <a class="rounded-xl bg-teal-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-teal-300" href="{{ route('register') }}">Créer mon espace</a>
        </div>
    </header>
    <main class="mx-auto grid max-w-7xl gap-12 px-6 pb-24 pt-16 lg:grid-cols-[1.1fr_.9fr] lg:items-center lg:pt-28">
        <div>
            <p class="mb-5 inline-flex rounded-full border border-teal-300/30 bg-teal-300/10 px-4 py-2 text-sm font-semibold text-teal-200">Votre patrimoine, mieux organisé</p>
            <h1 class="max-w-3xl text-5xl font-black leading-tight tracking-tight sm:text-6xl">La gestion locative, <span class="text-teal-300">sans dispersion.</span></h1>
            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">Biens, contrats, échéances, paiements et demandes d’intervention réunis dans un espace clair pour bailleurs et locataires.</p>
            <div class="mt-9 flex flex-wrap gap-4">
                <a class="rounded-xl bg-teal-400 px-6 py-3 font-bold text-slate-950 hover:bg-teal-300" href="{{ route('register') }}">Commencer gratuitement</a>
                <a class="rounded-xl border border-slate-700 px-6 py-3 font-semibold text-white hover:bg-white/5" href="{{ route('login') }}">Accéder à mon compte</a>
            </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-teal-950/30">
            <div class="flex items-center justify-between border-b border-white/10 pb-5">
                <div><p class="text-sm text-slate-400">Vue d’ensemble</p><p class="mt-1 text-xl font-bold">Votre activité locative</p></div>
                <span class="rounded-full bg-teal-300/10 px-3 py-1 text-xs font-bold text-teal-200">EN DIRECT</span>
            </div>
            <div class="grid grid-cols-2 gap-4 py-5">
                <div class="rounded-2xl bg-white/[0.06] p-4"><p class="text-sm text-slate-400">Loyers suivis</p><p class="mt-2 text-2xl font-bold">Factures & soldes</p></div>
                <div class="rounded-2xl bg-white/[0.06] p-4"><p class="text-sm text-slate-400">Paiements</p><p class="mt-2 text-2xl font-bold">Validation & reçus</p></div>
                <div class="rounded-2xl bg-white/[0.06] p-4"><p class="text-sm text-slate-400">Contrats</p><p class="mt-2 text-2xl font-bold">Documents privés</p></div>
                <div class="rounded-2xl bg-white/[0.06] p-4"><p class="text-sm text-slate-400">Interventions</p><p class="mt-2 text-2xl font-bold">Suivi de bout en bout</p></div>
            </div>
            <p class="text-sm leading-6 text-slate-400">Chaque bailleur et chaque locataire accède uniquement aux dossiers qui le concernent.</p>
        </div>
    </main>
</body>
</html>
