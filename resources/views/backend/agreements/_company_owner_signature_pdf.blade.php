@php
    $companySignatureUri = $letterMeta['signature_uri'] ?? null;
    $signatoryName = $letterMeta['signatory_name'] ?? strtoupper($company->name ?? '');
@endphp

<div class="sig-container">
    @if($companySignatureUri)
        <img src="{{ $companySignatureUri }}" class="sig-img" alt="Company signature">
    @endif
    <div class="sig-underline"></div>
</div>
<div class="sig-label">{{ $signatoryName }}</div>
<div class="sig-label" style="font-size:9px;">Company Director</div>
