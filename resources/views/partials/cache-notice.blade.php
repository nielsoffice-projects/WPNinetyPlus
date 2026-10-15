@php
    $has_failure = in_array('failed', $results, true);
    $notice_class = $has_failure ? 'notice notice-error' : 'notice notice-success';

    $labels = [
        'object_cache' => 'WordPress object cache',
        'plugin_transients' => 'Ninety Plus database transients',
        'plugin_file_cache' => 'Ninety Plus generated files',
    ];
@endphp

<div id="ninetyplus-cache-notice" class="{{ $notice_class }}"
    style="font-size: 12px; position:fixed; top:32px; right:20px; z-index:999999; background:#fff; border-left:4px solid {{ $has_failure ? '#dc3232' : '#46b450' }}; box-shadow:0 2px 5px rgba(0,0,0,0.2); padding:12px 15px; max-width:380px;">
    <p style="margin:0 0 5px 0;"><strong>{{ __('90+ Cache Clear Results', 'wpninetyplus') }}</strong></p>
    <ul style="list-style-type: disc; margin:0 0 0 20px; padding:0;">
        @foreach ($labels as $key => $label)
            @php
                $status = $results[$key] ?? 'unavailable';
                $message = match ($status) {
                    'success' => 'Success',
                    'no_files_found' => 'No files found to delete',
                    'skipped' => 'Skipped',
                    'failed' => 'Failed',
                    default => 'Unavailable',
                };
            @endphp
            <li><strong>{{ $label }}:</strong> {{ $message }}</li>
        @endforeach
    </ul>
</div>

