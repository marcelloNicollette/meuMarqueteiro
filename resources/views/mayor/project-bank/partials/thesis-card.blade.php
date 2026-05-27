@php
    $userState = $thesis->userStates->first();
    $trackingStatus = $thesis->trackingStatus();
    $searchText = strtolower(trim(implode(' ', array_filter([
        $thesis->title,
        $thesis->justification,
        $thesis->category,
        $thesis->funding_source,
    ]))));
@endphp

<article class="project-bank-card"
    data-card="thesis"
    data-saved="{{ $userState?->is_saved ? '1' : '0' }}"
    data-category="{{ strtolower((string) $thesis->category) }}"
    data-urgency="{{ strtolower((string) $thesis->urgency) }}"
    data-size="{{ strtolower((string) $thesis->estimated_size) }}"
    data-complexity="{{ strtolower((string) $thesis->execution_complexity) }}"
    data-search="{{ $searchText }}">
    <div class="project-bank-card-head">
        <div>
            <div class="project-bank-chips">
                <span class="project-bank-chip">{{ ucfirst($thesis->urgency) }}</span>
                <span class="project-bank-chip">{{ $thesis->category }}</span>
                <span class="project-bank-chip">{{ ucfirst($thesis->estimated_size) }}</span>
                <span class="project-bank-chip">{{ ucfirst($thesis->execution_complexity) }}</span>
            </div>
            <h2>{{ $thesis->title }}</h2>
            <p>{{ \Illuminate\Support\Str::limit($thesis->justification, 220) }}</p>
        </div>
        <div class="project-bank-status">
            Status: {{ str_replace('_', ' ', $trackingStatus) }}
        </div>
    </div>

    <div class="project-bank-actions">
        <a href="{{ route('mayor.project-bank.show', $thesis) }}" class="btn btn-dark">Abrir tese</a>
        <form method="POST" action="{{ route('mayor.project-bank.save', $thesis) }}">
            @csrf
            <button type="submit" class="btn">
                {{ $userState?->is_saved ? 'Remover dos salvos' : 'Salvar para depois' }}
            </button>
        </form>
        @if ($canGenerateProject)
            <form method="POST" action="{{ route('mayor.project-bank.generate-project', $thesis) }}">
                @csrf
                <button type="submit" class="btn">Gerar Projeto</button>
            </form>
        @endif
    </div>
</article>
