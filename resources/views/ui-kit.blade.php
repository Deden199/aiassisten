@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <x-ui.card>
        <x-slot name="header">{{ __t('Buttons') }}</x-slot>
        <div class="flex flex-wrap gap-2">
            <x-ui.button>{{ __t('Primary') }}</x-ui.button>
            <x-ui.button variant="secondary">{{ __t('Secondary') }}</x-ui.button>
            <x-ui.button variant="ghost">{{ __t('Ghost') }}</x-ui.button>
            <x-ui.button variant="link">{{ __t('Link') }}</x-ui.button>
            <x-ui.button variant="danger">{{ __t('Danger') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
@endsection
