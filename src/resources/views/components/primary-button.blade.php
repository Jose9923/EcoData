@props(['disabled' => false])

<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white shadow-lg shadow-slate-900/20 transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-emerald-200 active:bg-slate-950 disabled:cursor-not-allowed disabled:opacity-60'
    ]) }}
    @disabled($disabled)
>
    {{ $slot }}
</button>