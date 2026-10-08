{{-- CDN assets + brand theme (swap to Vite later when node is available) --}}
@include('partials.favicon')
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Plus Jakarta Sans', 'sans-serif'],
                },
                colors: {
                    brand: {
                        50: '#eef2ff',
                        100: '#e0e7ff',
                        500: '#6366f1',
                        600: '#4f46e5',
                        700: '#4338ca',
                        900: '#312e81',
                    }
                }
            }
        }
    }
</script>

<link rel="stylesheet" href="{{ asset('css/mikpay.css') }}?v={{ @filemtime(public_path('css/mikpay.css')) ?: 1 }}">
