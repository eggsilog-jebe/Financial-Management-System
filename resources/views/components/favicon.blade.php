@props(['version' => '1.0'])

<!-- Favicon & Universal Brand Icons (HIMS • FMS) -->
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $version }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $version }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $version }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $version }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $version }}">
<meta name="theme-color" content="#059669">
<meta name="apple-mobile-web-app-title" content="HIMS &bull; FMS">
<meta name="application-name" content="Hospital Financial Management System">
<meta name="msapplication-TileColor" content="#059669">
<meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">

<script>
  // Block browser PWA install / 'Open in app' prompts
  window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    return false;
  });
  // Clean up any lingering PWA Service Workers
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
      registrations.forEach(function(registration) {
        registration.unregister();
      });
    }).catch(function() {});
  }
</script>
