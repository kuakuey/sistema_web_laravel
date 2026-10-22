<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
(function () {
  try {
    if (localStorage.getItem('cda-theme') === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    }
    if (localStorage.getItem('cda-sidebar') === 'collapsed') {
      document.documentElement.setAttribute('data-sidebar', 'collapsed');
    }
  } catch (e) {}
})();
</script>
<title>{{ $tituloPagina ?? ($titulo ?? 'Sistema Web CDA') }} · Sistema CDA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
