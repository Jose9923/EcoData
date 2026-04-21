<header class="shadow-sm border-b" style="background-color: var(--school-primary, #1d4ed8); border-color: var(--school-accent, #22c55e);">
    <div class="px-4 py-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($currentSchool?->shield_path)
                    <img
                        src="{{ asset('storage/' . $currentSchool->shield_path) }}"
                        alt="Escudo"
                        class="h-10 w-10 object-contain bg-white rounded p-1"
                    >
                @endif

                <div>
                    <div class="text-lg font-semibold text-white">
                        {{ $currentSchool?->name ?? config('app.name') }}
                    </div>

                    @if($currentSchool)
                        <div class="text-sm text-white/80">
                            Plataforma institucional
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <div class="text-sm text-white">
                        {{ auth()->user()?->name }}
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>