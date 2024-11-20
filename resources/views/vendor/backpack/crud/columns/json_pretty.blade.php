@php
    $value = $entry->{$column['name']};
    if (is_array($value)) {
        $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
@endphp

<pre class="bg-light p-2">{!! $value !!}</pre>
