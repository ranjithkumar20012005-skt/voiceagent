@extends('layouts.app')

@section('title', 'Map Columns')

@php
  $labels = [
    'customer_identifier' => 'Customer ID',
    'name'                => 'Customer name',
    'phone_number'        => 'Phone number',
    'policy_number'       => 'Policy number',
    'registered_mobile'   => 'Registered mobile',
    'policy_expiry_date'  => 'Policy expiry date',
    'renewal_premium'     => 'Renewal premium',
    'preferred_language'  => 'Preferred language',
    'notes'               => 'Notes',
  ];
  $map = (array) $batch->column_map;
  $matched = count(array_filter($map));
@endphp

@section('content')

<div class="ph">
  <div class="ph-main">
    <a href="{{ route('imports.index') }}" class="ph-back"><i class="icon-arrow-left"></i> Imports</a>
    <h1>Map columns</h1>
    <p class="ph-sub">{{ $batch->original_filename }} · {{ number_format($batch->total_rows) }} data rows detected · {{ $matched }} of {{ count($fields) }} fields matched automatically</p>
  </div>
</div>

<div class="steps" aria-label="Import steps">
  <span class="step-chip done"><b><i class="icon-check"></i></b>Upload</span>
  <span class="step-chip current"><b>2</b>Map columns</span>
  <span class="step-chip"><b>3</b>Process</span>
  <span class="step-chip"><b>4</b>Review results</span>
</div>

<div class="split-left">
  <form method="POST" action="{{ route('imports.process', $batch) }}" class="card">
    @csrf
    <div class="card-h"><div><h2>Your columns → our fields</h2><p>Adjust anything that looks wrong, then start the import.</p></div></div>
    <div class="card-b">
      @error('map')<div class="callout callout-danger mb-4"><i class="icon-circle-alert"></i><div>{{ $message }}</div></div>@enderror

      @foreach ($fields as $field)
        <div class="field">
          <label class="label" for="map_{{ $field }}">
            {{ $labels[$field] ?? $field }}
            @if ($field === 'phone_number')<span class="req">* required</span>@endif
          </label>
          <select name="map[{{ $field }}]" id="map_{{ $field }}" class="select">
            <option value="">— Not mapped —</option>
            @foreach ((array) $batch->detected_headers as $header)
              <option value="{{ $header }}" @selected(($map[$field] ?? null) === $header)>{{ $header }}</option>
            @endforeach
          </select>
        </div>
      @endforeach
    </div>
    <div class="card-f"><button type="submit" class="btn btn-primary btn-sm btn-block"><i class="icon-play"></i> Start import</button></div>
  </form>

  <div class="card">
    <div class="card-h">
      <div><h2>File preview</h2><p>The first rows of your file, exactly as uploaded</p></div>
      <span class="badge">{{ count($preview['rows'] ?? []) }} rows</span>
    </div>
    @if (empty($preview['rows']))
      <x-empty icon="icon-file-spreadsheet" title="No preview available" compact>The mapping still works without sample rows.</x-empty>
    @else
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              @foreach ((array) $batch->detected_headers as $header)
                <th>
                  {{ $header }}
                  @if ($field = array_search($header, $map, true))
                    <span class="cell-sub" style="text-transform:none;letter-spacing:0;color:var(--green-700)">→ {{ $labels[$field] ?? $field }}</span>
                  @endif
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($preview['rows'] as $row)
              <tr>
                @foreach ($row as $cell)
                  <td class="nowrap muted">{{ Str::limit($cell, 30) }}</td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

@endsection
