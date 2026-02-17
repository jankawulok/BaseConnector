@push('after_scripts')
    <script>
        function syncFull(button) {
            let route = button.getAttribute('data-route');
            if (confirm("Are you sure you want to start a full sync?")) {
                window.location.href = route;
            }
        }

        function syncLight(button) {
            let route = button.getAttribute('data-route');
            // Check if button is disabled
            if (button.classList.contains('disabled')) return;

            if (confirm("Are you sure you want to start a light sync? This will update prices and stock levels.")) {
                // Disable button to prevent double-clicks
                button.classList.add('disabled');
                let icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('la-bolt');
                    icon.classList.add('la-spinner', 'la-spin');
                }

                // Redirect to sync route
                window.location.href = route;
            }
        }

        function cleanup(button) {
            let route = button.getAttribute('data-route');
            if (confirm("WARNING: This will delete all products for this integration. Are you sure?")) {
                window.location.href = route;
            }
        }
    </script>
@endpush