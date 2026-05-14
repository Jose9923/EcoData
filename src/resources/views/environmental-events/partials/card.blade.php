<div class="col-12 col-md-6 col-xl-4">
    <div class="border rounded-4 h-100 overflow-hidden bg-white">
        @if($event->image_path)
            <img src="{{ asset('storage/' . $event->image_path) }}"
                 alt="{{ $event->title }}"
                 class="w-100"
                 style="height: 180px; object-fit: cover;">
        @else
            <div class="bg-light d-flex align-items-center justify-content-center"
                 style="height: 180px;">
                <div class="display-5">🌎</div>
            </div>
        @endif

        <div class="p-4">
            <div class="text-muted small fw-semibold mb-2">
                {{ $event->starts_at?->format('d/m/Y') }}
                @if($event->ends_at && !$event->starts_at->isSameDay($event->ends_at))
                    - {{ $event->ends_at->format('d/m/Y') }}
                @endif
            </div>

            <h3 class="h5 fw-bold mb-2">{{ $event->title }}</h3>

            <p class="text-muted mb-3">
                {{ Str::limit($event->description, 130) }}
            </p>

            <a href="{{ route('environmental-events.show', $event) }}"
               class="btn text-white rounded-4 px-4"
               style="background-color: var(--school-primary);">
                Ver detalles
            </a>
        </div>
    </div>
</div>