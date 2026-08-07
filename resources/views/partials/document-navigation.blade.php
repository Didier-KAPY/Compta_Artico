@if($documentLinks->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($documentLinks as $link)
            <a href="{{ $link['url'] }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-{{ $link['icon'] }} me-1"></i>{{ $link['label'] }}</a>
        @endforeach
    </div>
@endif
