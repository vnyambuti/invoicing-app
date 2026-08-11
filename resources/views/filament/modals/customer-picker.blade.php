{{-- resources/views/filament/modals/customer-picker.blade.php --}}
<div>
    @livewire('customer-picker', [
        'primaryColumn' => $primaryColumn,
        'fieldName' => $fieldName,
    ], key($fieldName))
</div>
