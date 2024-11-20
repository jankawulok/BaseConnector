@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @php
        $value = old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '{}';
        if (is_array($value)) {
            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    @endphp

    <textarea
        name="{{ $field['name'] }}"
        class="form-control json-editor"
        data-init-function="bpFieldInitJsonEditor"
        >{{ $value }}</textarea>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <style type="text/css">
        .CodeMirror {
            min-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
@endpush

@push('crud_fields_scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script>
        function bpFieldInitJsonEditor(element) {
            // Initialize CodeMirror
            var editor = CodeMirror.fromTextArea(element[0], {
                lineNumbers: true,
                mode: "application/json",
                theme: "monokai",
                matchBrackets: true,
                autoCloseBrackets: true,
                tabSize: 2,
                gutters: ["CodeMirror-linenumbers"],
                viewportMargin: Infinity
            });

            // Format JSON on load
            try {
                var value = editor.getValue().trim() || '{}';
                var formatted = JSON.stringify(JSON.parse(value), null, 2);
                editor.setValue(formatted);
            } catch (e) {
                console.warn('Invalid JSON:', e);
            }

            // Update textarea on change
            editor.on('change', function() {
                element.val(editor.getValue());
            });

            // Add format button
            var $button = $('<button type="button" class="btn btn-outline-primary btn-sm ml-2">Format JSON</button>');
            $(element).after($button);

            $button.click(function() {
                try {
                    var value = editor.getValue().trim() || '{}';
                    var formatted = JSON.stringify(JSON.parse(value), null, 2);
                    editor.setValue(formatted);
                } catch (e) {
                    console.warn('Invalid JSON:', e);
                }
            });
        }
    </script>
@endpush
