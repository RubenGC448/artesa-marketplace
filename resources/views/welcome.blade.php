{{-- La portada real es public/home (HomeController). Se mantiene por compatibilidad. --}}
<x-public-layout>
    <a href="{{ route('home') }}">{{ __('Ir a la portada') }}</a>
</x-public-layout>
