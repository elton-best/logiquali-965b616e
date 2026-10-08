@extends('pdf.layouts.master')

@section('title', 'Fiche de poste')

@section('content')
    @php
    $mainActivities = is_iterable($jobDescription->main_activities ?? null) ? $jobDescription->main_activities : [];
    $secondaryActivities = is_iterable($jobDescription->secondary_activities ?? null) ? $jobDescription->secondary_activities : [];
    $requiredSkills = is_iterable($jobDescription->required_skills ?? null) ? $jobDescription->required_skills : [];
    $certificationsRequired = is_iterable($jobDescription->certifications_required ?? null) ? $jobDescription->certifications_required : [];
    $internalRelations = is_iterable($jobDescription->internal_relations ?? null) ? $jobDescription->internal_relations : [];
    $externalRelations = is_iterable($jobDescription->external_relations ?? null) ? $jobDescription->external_relations : [];
    @endphp

    <div class="header">
        <h1>FICHE DE POSTE</h1>
        <p><strong>{{ $jobDescription->job_title }}</strong></p>
    </div>

    @if($jobDescription->replacement_job_title)
    <div class="section">
        <span class="label">Poste du remplaçant (absence) :</span> {{ $jobDescription->replacement_job_title }}
    </div>
    @endif

    @if($jobDescription->user)
    <div class="section">
        <span class="label">Collaborateur :</span>
        {{ trim(($jobDescription->user->last_name ?? '').' '.($jobDescription->user->first_name ?? '')) ?: ($jobDescription->user->name ?? 'N/A') }}
    </div>
    @endif

    @if($jobDescription->department)
    <div class="section">
        <span class="label">Département :</span> {{ $jobDescription->department }}
    </div>
    @endif

    @if($jobDescription->reportsTo)
    <div class="section">
        <span class="label">Rapporte à :</span>
        {{ trim(($jobDescription->reportsTo->last_name ?? '').' '.($jobDescription->reportsTo->first_name ?? '')) ?: ($jobDescription->reportsTo->name ?? 'N/A') }}
    </div>
    @endif

    <h2>Mission principale</h2>
    <div class="section">
        <p>{{ $jobDescription->mission }}</p>
    </div>

    @if($jobDescription->activities)
    <h2>Activités principales</h2>
    <div class="section">
        <p>{{ $jobDescription->activities }}</p>
    </div>
    @endif

    @if(count($mainActivities) > 0)
    <h2>Activités principales (détail)</h2>
    <div class="section">
        <ul>
            @foreach($mainActivities as $activity)
            <li>{{ $activity }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(count($secondaryActivities) > 0)
    <h2>Activités secondaires</h2>
    <div class="section">
        <ul>
            @foreach($secondaryActivities as $activity)
            <li>{{ $activity }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(count($requiredSkills) > 0)
    <h2>Compétences requises</h2>
    <div class="section">
        <ul>
            @foreach($requiredSkills as $skill)
            <li>{{ $skill }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($jobDescription->required_education)
    <h2>Niveau d'études requis</h2>
    <div class="section">
        <p>{{ $jobDescription->required_education }}</p>
    </div>
    @endif

    @if($jobDescription->required_experience)
    <h2>Expérience requise</h2>
    <div class="section">
        <p>{{ $jobDescription->required_experience }}</p>
    </div>
    @endif

    @if(count($certificationsRequired) > 0)
    <h2>Certifications requises</h2>
    <div class="section">
        <ul>
            @foreach($certificationsRequired as $cert)
            <li>{{ $cert }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(count($internalRelations) > 0)
    <h2>Relations internes</h2>
    <div class="section">
        <ul>
            @foreach($internalRelations as $relation)
            <li>{{ $relation }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(count($externalRelations) > 0)
    <h2>Relations externes</h2>
    <div class="section">
        <ul>
            @foreach($externalRelations as $relation)
            <li>{{ $relation }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($jobDescription->work_location)
    <h2>Lieu de travail</h2>
    <div class="section">
        <p>{{ $jobDescription->work_location }}</p>
    </div>
    @endif

    @if($jobDescription->work_schedule)
    <h2>Horaires de travail</h2>
    <div class="section">
        <p>{{ $jobDescription->work_schedule }}</p>
    </div>
    @endif

    @if($jobDescription->physical_requirements)
    <h2>Exigences physiques</h2>
    <div class="section">
        <p>{{ $jobDescription->physical_requirements }}</p>
    </div>
    @endif

    @if(!empty($jobDescription->employee_signature_data))
    <div class="section" style="margin-top: 50px;">
        <p><strong>Lu et approuvé le :</strong> {{ $jobDescription->employee_signed_at ? \Carbon\Carbon::parse($jobDescription->employee_signed_at)->format('d/m/Y') : date('d/m/Y') }}</p>
        <div style="margin-top: 15px;">
            <img src="{{ $jobDescription->employee_signature_data }}" alt="Signature collaborateur" style="max-height: 80px; max-width: 200px; object-fit: contain;">
        </div>
    </div>
    @endif
@endsection