<x-filament-panels::page>
    {!! QrCode::size(100)->generate($this->getrecord()->name) !!}

    {{ $this->getrecord()->name }}
</x-filament-panels::page>