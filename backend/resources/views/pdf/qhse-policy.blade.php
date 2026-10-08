@extends('pdf.layouts.master')

@section('title', 'Politique QHSE - Version ' . $policy->version)

@section('styles')
    <style>
        .meta { background: #f3f4f6; padding: 15px; border-radius: 5px; margin-bottom: 30px; }
    </style>
@endsection

@section('content')
    <div class="header">
        <h1>Politique QHSE</h1>
        <p><strong>Version:</strong> {{ $policy->version }} | <strong>Date:</strong> {{ $policy->effective_date?->format('d/m/Y') ?? 'N/A' }}</p>
    </div>

    <div class="meta">
        <strong>Statut:</strong> {{ $policy->status === 'validated' ? 'Validée' : 'Brouillon' }}
        @if($policy->validated_by_direction_at)
        <br><strong>Validée le:</strong> {{ $policy->validated_by_direction_at->format('d/m/Y à H:i') }}
        @endif
    </div>

    <h2>Mission</h2>
    <p>{{ $policy->mission }}</p>

    @if($policy->vision)
    <h2>Vision</h2>
    <p>{{ $policy->vision }}</p>
    @endif

    @if($policy->values && count($policy->values) > 0)
    <h2>Valeurs</h2>
    <ul>
        @foreach($policy->values as $value)
        <li>{{ $value }}</li>
        @endforeach
    </ul>
    @endif

    @if($policy->commitments && count($policy->commitments) > 0)
    <h2>Engagements</h2>
    <ul>
        @foreach($policy->commitments as $commitment)
        <li>{{ $commitment }}</li>
        @endforeach
    </ul>
    @endif

    @if($policy->quality_policy)
    <h2>Politique Qualité</h2>
    <p>{{ $policy->quality_policy }}</p>
    @endif

    @if($policy->environmental_policy)
    <h2>Politique Environnementale</h2>
    <p>{{ $policy->environmental_policy }}</p>
    @endif

    @if($policy->health_safety_policy)
    <h2>Politique Santé & Sécurité</h2>
    <p>{{ $policy->health_safety_policy }}</p>
    @endif
@endsection
