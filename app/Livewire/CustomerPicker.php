<?php

namespace App\Livewire;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Reusable "Choose From List" picker for Customers.
 *
 * Rendered inside a Filament\Forms\Components\Actions\Action modal.
 * Emits a `customer-picker-selected` browser event carrying the chosen
 * customer id and the target form field name, which the hosting
 * page/Livewire component listens for to push the value back into the form.
 */
class CustomerPicker extends Component implements HasTable
{
    use InteractsWithTable;

    /**
     * Which Customer column should be shown as the first/primary
     * column in the list: 'code' or 'name'.
     */
    public string $primaryColumn = 'code';

    /**
     * The Filament form field name that should receive the selected
     * customer's id (e.g. 'customer_id').
     */
    public string $fieldName = 'customer_id';

    public function mount(string $primaryColumn = 'code', string $fieldName = 'customer_id'): void
    {
        $this->primaryColumn = $primaryColumn;
        $this->fieldName = $fieldName;
    }

    public function table(Table $table): Table
    {
        $primary = $this->primaryColumn === 'name' ? 'name' : 'code';
        $secondary = $primary === 'code' ? 'name' : 'code';

        return $table
            ->query(Customer::query())
            ->columns([
                Tables\Columns\TextColumn::make($primary)
                    ->label($primary === 'code' ? 'Customer Code' : 'Customer Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make($secondary)
                    ->label($secondary === 'code' ? 'Code' : 'Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bp_currency')
                    ->label('Currency')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('kra_pin')
                    ->label('KRA PIN')
                    ->toggleable(),
            ])
            ->searchOnBlur()
            ->defaultSort($primary)
            ->recordUrl(null)
            ->actions([
                Tables\Actions\Action::make('choose')
                    ->label('Select')
                    ->button()
                    ->size('sm')
                    ->action(fn (Customer $record) => $this->choose($record)),
            ])
            // Row click also selects the customer, matching typical
            // "choose from list" (CFL) grid behaviour.
            ->recordAction('choose')
            ->paginated([10, 25, 50])
            ->poll(null);
    }

    public function choose(Customer $record): void
    {
        $this->dispatch(
            'customer-picker-selected',
            id: $record->id,
            field: $this->fieldName,
        );
    }

    public function render()
    {
        return view('livewire.customer-picker');
    }
}
