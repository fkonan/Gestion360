<!-- Lista de navegacion -->
<nav aria-label="breadcrumb" class="mx-3">
    <ol class="breadcrumb">
        @foreach ($items as $item)
            @if ($loop->last)
                <li class="breadcrumb-item active" aria-current="page"><b>{{ $item['name'] }}</b></li>
            @else
                <li class="breadcrumb-item">
                    <a class="text-decoration-none" href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                </li>
            @endif
        @endforeach
    </ol>
</nav>

