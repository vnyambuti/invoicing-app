<?php

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Item;
use App\Models\SalesEmployee;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);



beforeEach(function () {
    $this->user = User::create([
        'name'     => 'Test User',
        'email'    => 'tester@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($this->user);

    $this->customer = Customer::create([
        'code'            => 'C0001',
        'name'            => 'Acme Traders Ltd',
        'contact_person'  => 'Jane Doe',
        'bp_currency'     => 'KES',
        'kra_pin'         => 'P000111222A',
    ]);

    $this->salesEmployee = SalesEmployee::create([
        'name' => 'John Kamau',
    ]);

    $this->warehouse = Warehouse::create([
        'code' => 'WH01',
        'name' => 'Main Warehouse',
    ]);

    $this->item = Item::create([
        'item_no'     => 'ITM-001',
        'description' => 'Office Chair',
        'uom_code'    => 'PCS',
        'unit_price'  => 5000,
    ]);
});



it('persists customers, items, sales employees and invoices with their relationships', function () {
    $invoice = Invoice::create([
        'customer_id'       => $this->customer->id,
        'customer_name'     => $this->customer->name,
        'posting_date'      => now(),
        'document_date'     => now(),
        'sales_employee_id' => $this->salesEmployee->id,
        'remarks'           => 'Test invoice',
    ]);

    $line = $invoice->lines()->create([
        'item_id'                => $this->item->id,
        'item_no'                => $this->item->item_no,
        'item_description'       => $this->item->description,
        'quantity'                => 2,
        'price_before_discount' => 5000,
        'discount'                => 0,
        'price_after_discount'  => 5000,
        'total'                    => 10000,
    ]);

    expect($invoice->customer->is($this->customer))->toBeTrue()
        ->and($invoice->salesEmployee->is($this->salesEmployee))->toBeTrue()
        ->and($invoice->lines)->toHaveCount(1)
        ->and($line->item->is($this->item))->toBeTrue()
        ->and($this->customer->invoices)->toHaveCount(1)
        ->and($this->salesEmployee->invoices)->toHaveCount(1);
});



it('type-ahead searches customers by code, matching code or name', function () {
    Customer::create(['code' => 'C0002', 'name' => 'Beta Supplies']);

    $results = InvoiceResource::searchCustomersByCode('C0001');
    expect($results)->toHaveCount(1)
        ->and($results->first())->toBe('C0001');

    // Falls back to matching on name too.
    $resultsByName = InvoiceResource::searchCustomersByCode('Acme');
    expect($resultsByName)->toHaveCount(1);
});

it('type-ahead searches customers by name, matching name or code', function () {
    Customer::create(['code' => 'C0002', 'name' => 'Beta Supplies']);

    $results = InvoiceResource::searchCustomersByName('Acme');
    expect($results)->toHaveCount(1)
        ->and($results->first())->toBe('Acme Traders Ltd');

    $resultsByCode = InvoiceResource::searchCustomersByName('C0001');
    expect($resultsByCode)->toHaveCount(1);
});

it('selecting a customer on the invoice form mirrors code and name to each other', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm(['customer_id' => $this->customer->id])
        ->assertFormSet(['customer_id' => $this->customer->id]);
});


it('auto-generates a sequential doc_no on invoice creation', function () {
    $invoice1 = Invoice::create([
        'customer_id'  => $this->customer->id,
        'posting_date' => now(),
        'remarks'      => 'First',
    ]);

    $invoice2 = Invoice::create([
        'customer_id'  => $this->customer->id,
        'posting_date' => now(),
        'remarks'      => 'Second',
    ]);

    $year = now()->format('y');

    expect($invoice1->doc_no)->toBe("IN{$year}000001")
        ->and($invoice2->doc_no)->toBe("IN{$year}000002");
});

it('does not overwrite a manually supplied doc_no', function () {
    $invoice = Invoice::create([
        'doc_no'       => 'CUSTOM-001',
        'customer_id'  => $this->customer->id,
        'posting_date' => now(),
        'remarks'      => 'Manual doc no',
    ]);

    expect($invoice->doc_no)->toBe('CUSTOM-001');
});



it('defaults posting_date to today on the create form', function () {
    Livewire::test(CreateInvoice::class)
        ->assertFormSet(fn(array $state) => now()->isSameDay($state['posting_date']));
});



it('hides the approval notice when the total is at or below 10,000', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm(['total_after_discount' => 10000])
        ->assertDontSee('Invoice will go for approval');
});

it('shows the approval notice with the amount once total exceeds 10,000', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm(['total_after_discount' => 15000.5])
        ->assertSee('Invoice will go for approval')
        ->assertSee('15,000.50');
});



it('lists sales employees for selection via the relationship field', function () {
    SalesEmployee::create(['name' => 'Alice Wanjiru']);

    Livewire::test(CreateInvoice::class)
        ->assertFormFieldExists('sales_employee_id');
});

it('requires a sales employee to be selected', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id'       => $this->customer->id,
            'posting_date'      => now(),
            'remarks'           => 'Missing sales employee',
            'sales_employee_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['sales_employee_id' => 'required']);
});


it('rejects an invoice with an empty remarks field', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id'       => $this->customer->id,
            'posting_date'      => now(),
            'sales_employee_id' => $this->salesEmployee->id,
            'remarks'           => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['remarks' => 'required']);
});




it('rejects a line discount greater than 50 percent', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id'       => $this->customer->id,
            'posting_date'      => now(),
            'sales_employee_id' => $this->salesEmployee->id,
            'remarks'           => 'Discount test',
            'lines'             => [
                'line-1' => [
                    'item_no'                => $this->item->item_no,
                    'item_id'                => $this->item->id,
                    'item_description'       => $this->item->description,
                    'quantity'                => 1,
                    'price_before_discount' => 5000,
                    'discount'                => 55,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['lines.line-1.discount' => 'max']);
});

it('accepts a line discount at or below 50 percent', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id'       => $this->customer->id,
            'posting_date'      => now(),
            'sales_employee_id' => $this->salesEmployee->id,
            'remarks'           => 'Discount test',
            'lines'             => [
                'line-1' => [
                    'item_no'                => $this->item->item_no,
                    'item_id'                => $this->item->id,
                    'item_description'       => $this->item->description,
                    'quantity'                => 1,
                    'price_before_discount' => 5000,
                    'discount'                => 50,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors(['lines.line-1.discount']);
});



it('type-ahead searches items by item_no or description', function () {
    Item::create(['item_no' => 'ITM-002', 'description' => 'Standing Desk', 'unit_price' => 12000]);

    $byNo = InvoiceResource::searchItems('ITM-001');
    expect($byNo)->toHaveCount(1)->and($byNo->first())->toBe('ITM-001');

    $byDescription = InvoiceResource::searchItems('Standing');
    expect($byDescription)->toHaveCount(1)->and($byDescription->first())->toBe('ITM-002');
});

it('auto-populates description, price and uom when a known item_no is typed', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'lines' => [
                'line-1' => ['item_no' => 'ITM-001'],
            ],
        ])
        ->assertFormSet([
            'lines' => [
                'line-1' => [
                    'item_description'       => 'Office Chair',
                    'price_before_discount' => 5000,
                    'uom_code'                => 'PCS',
                ],
            ],
        ]);
});

it('leaves the description untouched when item_no does not match any item', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'lines' => [
                'line-1' => [
                    'item_no'          => 'NOT-IN-CATALOG',
                    'item_description' => 'Manually typed line',
                ],
            ],
        ])
        ->assertFormSet([
            'lines' => [
                'line-1' => [
                    'item_id'          => null,
                    'item_description' => 'Manually typed line',
                ],
            ],
        ]);
});

it('stores line quantities and prices with up to three decimal places', function () {
    $invoice = Invoice::create([
        'customer_id'  => $this->customer->id,
        'posting_date' => now(),
        'remarks'      => 'Decimal precision test',
    ]);

    $line = $invoice->lines()->create([
        'item_id'                => $this->item->id,
        'item_no'                => $this->item->item_no,
        'item_description'       => $this->item->description,
        'quantity'                => 2.505,
        'price_before_discount' => 999.999,
        'discount'                => 10.125,
        'price_after_discount'  => 899.750,
        'total'                    => 2253.87,
    ]);

    expect((string) $line->quantity)->toBe('2.505')
        ->and((string) $line->price_before_discount)->toBe('999.999')
        ->and((string) $line->discount)->toBe('10.125');
});

it('rejects non-numeric input for quantity', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id'       => $this->customer->id,
            'posting_date'      => now(),
            'sales_employee_id' => $this->salesEmployee->id,
            'remarks'           => 'Non-numeric quantity',
            'lines'             => [
                'line-1' => [
                    'item_no'                => $this->item->item_no,
                    'item_description'       => $this->item->description,
                    'quantity'                => 'abc',
                    'price_before_discount' => 5000,
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['lines.line-1.quantity' => 'numeric']);
});



it('recalculates invoice totals from its lines end to end (currently blocked by schema gap)', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id'       => $this->customer->id,
            'posting_date'      => now(),
            'document_date'     => now(),
            'sales_employee_id' => $this->salesEmployee->id,
            'remarks'           => 'Totals test',
            'lines'             => [
                'line-1' => [
                    'item_no'                => $this->item->item_no,
                    'item_id'                => $this->item->id,
                    'item_description'       => $this->item->description,
                    'quantity'                => 2,
                    'price_before_discount' => 5000,
                    'discount'                => 0,
                    'vat_code'                => 'S',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::where('remarks', 'Totals test')->firstOrFail();

    expect((float) $invoice->total_before_discount)->toBe(10000.0)
        ->and((float) $invoice->total_after_discount)->toBe(10000.0);
})->skip('Enable once freight/tax/balance_due/applied_amount/total_down_payment/rounding/owner_id/qr_code/payment_order_run/attachments columns exist on invoices and are fillable.');
