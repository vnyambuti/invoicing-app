<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesEmployee;
use App\Models\User;
use App\Models\Warehouse;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Transactions';

    protected static array $vatRates = [
        'O0' => 0.0,
        'O2' => 0.0,
        'S'  => 16.0,
    ];

    public static function form(Form $form): Form
    {

        return $form->columns(1)->schema([


            Forms\Components\Group::make()
                ->extraAttributes(['class' => 'ar-invoice-form'])
                ->schema([


                    Forms\Components\Grid::make(12)
                        ->extraAttributes(['class' => 'ar-invoice-header'])
                        ->schema([

                            Forms\Components\Grid::make(1)
                                ->columnSpan(3)
                                ->extraAttributes(['class' => 'ar-invoice-header-left'])
                                ->schema([
                                    Forms\Components\Select::make('customer_id')
                                        ->label('Customer')
                                        ->relationship('customer', 'code')
                                        ->searchable()
                                        ->getSearchResultsUsing(fn(string $search) => self::searchCustomersByCode($search))
                                        ->getOptionLabelUsing(fn($value) => Customer::find($value)?->code)
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->afterStateUpdated(fn(Set $set, $state) => self::syncCustomerFields($set, $state))
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('code')
                                                ->label('Customer Code')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(20),

                                            Forms\Components\TextInput::make('name')
                                                ->label('Customer Name')
                                                ->required()
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('contact_person')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('bp_currency')
                                                ->label('BP Currency')
                                                ->default('KES')
                                                ->maxLength(3),

                                            Forms\Components\TextInput::make('kra_pin')
                                                ->label('KRA PIN')
                                                ->maxLength(20),
                                        ])
                                        ->createOptionAction(fn(Forms\Components\Actions\Action $action) => $action
                                            ->modalHeading('Create Customer')
                                            ->modalWidth('lg'))
                                        ->placeholder(null),

                                    Forms\Components\TextInput::make('contact_person')
                                        ->label('Contact Person')
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field']),

                                    Forms\Components\TextInput::make('customer_name')
                                        ->label('Customer Name')
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field ar-customer-name-field'])
                                        ->extraInputAttributes(['class' => 'font-bold']),

                                    Forms\Components\TextInput::make('bp_currency')
                                        ->label('BP Currency')
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field']),

                                    Forms\Components\TextInput::make('kra_pin')
                                        ->label('KRA PIN')
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field']),
                                ]),


                            Forms\Components\Grid::make(1)
                                ->columnSpan(6)
                                ->extraAttributes(['class' => 'ar-invoice-doc-spacer'])
                                ->schema([]),

                            Forms\Components\Grid::make(1)
                                ->columnSpan(3)
                                ->extraAttributes(['class' => 'ar-invoice-doc-info'])
                                ->schema([
                                    Forms\Components\TextInput::make('doc_no')
                                        ->label('No.')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->placeholder('Auto-generated on save'),

                                    Forms\Components\TextInput::make('status_display')
                                        ->label('Status')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->formatStateUsing(fn() => 'Open'),

                                    Forms\Components\DatePicker::make('posting_date')
                                        ->required()
                                        ->default(now())
                                        ->native(false)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field']),

                                    Forms\Components\DatePicker::make('value_date')
                                        ->native(false)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field']),

                                    Forms\Components\DatePicker::make('document_date')
                                        ->required()
                                        ->default(now())
                                        ->native(false)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field']),

                                    Forms\Components\Placeholder::make('approval_notice')
                                        ->label('')
                                        ->content(fn(Get $get) => new \Illuminate\Support\HtmlString(
                                            '<span class="text-danger-600 font-semibold">Invoice will go for approval – Amount: KES '
                                                . number_format((float) $get('total_after_discount'), 2) . '</span>'
                                        ))
                                        ->visible(fn(Get $get) => (float) $get('total_after_discount') > 10000),
                                ]),
                        ]),

                    Forms\Components\Tabs::make('invoice_tabs')
                        ->contained(false)
                        ->extraAttributes(['class' => 'ar-invoice-tabs'])
                        ->tabs([

                            Forms\Components\Tabs\Tab::make('Contents')
                                ->schema([
                                    Forms\Components\Grid::make(12)
                                        ->extraAttributes(['class' => 'ar-invoice-lines-header'])
                                        ->schema([
                                            Forms\Components\Grid::make(1)
                                                ->columnSpan(3)
                                                ->extraAttributes(['class' => 'ar-invoice-lines-header-left'])
                                                ->schema([
                                                    Forms\Components\Select::make('line_type_filter')
                                                        ->label('Item/Service Type')
                                                        ->inlineLabel()
                                                        ->options(['item' => 'Item', 'service' => 'Service'])
                                                        ->default('item')
                                                        ->dehydrated(false)
                                                        ->extraAttributes(['class' => 'ar-line-type-filter'])
                                                        ->live()
                                                        ->placeholder(null),

                                                ]),
                                            Forms\Components\Grid::make(1)
                                                ->columnSpan(6)
                                                ->extraAttributes(['class' => 'ar-invoice-doc-spacer'])
                                                ->schema([]),
                                            Forms\Components\Grid::make(1)
                                                ->columnSpan(3)
                                                ->extraAttributes(['class' => 'ar-invoice-lines-header-left'])
                                                ->schema([
                                                    Forms\Components\Select::make('line_type_filter')
                                                        ->label('Summary Type')
                                                        ->inlineLabel()
                                                        ->options(['item' => 'Item', 'service' => 'Service'])
                                                        ->default('item')
                                                        ->dehydrated(false)
                                                        ->extraAttributes(['class' => 'ar-line-type-filter'])
                                                        ->live()
                                                        ->placeholder(null),

                                                ]),

                                        ]),



                                    TableRepeater::make('lines')
                                        ->relationship('lines')
                                        ->hiddenLabel()
                                        ->extraAttributes(['class' => 'ar-invoice-lines'])
                                        ->defaultItems(10)
                                        ->headers([
                                            Header::make('Item No.')->width('180px'),
                                            Header::make('Item Description'),
                                            Header::make('Quantity'),
                                            Header::make('Whse')->width('160px'),
                                            Header::make('UoM Code'),
                                            Header::make('Unit Price'),
                                            Header::make('Discount %'),
                                            Header::make('Price After Discount'),
                                            Header::make('VAT Code'),
                                            Header::make('Gross Price after Disc.'),
                                            Header::make('Total (LC)'),
                                            Header::make('Gross Total (LC)'),
                                        ])
                                        ->schema([
                                            Forms\Components\TextInput::make('item_no')
                                                ->label('Item No.')
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                                    if (blank($state)) {
                                                        $set('item_id', null);
                                                        return;
                                                    }

                                                    $item = Item::where('item_no', $state)->first();

                                                    if ($item) {

                                                        $set('item_id', $item->id);
                                                        $set('item_description', $item->description);
                                                        $set('price_before_discount', (float) $item->unit_price);
                                                        $set('uom_code', $item->uom_code);
                                                        $set('vat_code', $get('vat_code') ?: 'S');
                                                        self::recalcLine($set, $get);
                                                    } else {

                                                        $set('item_id', null);
                                                    }
                                                })
                                                ->suffixActions([
                                                    Forms\Components\Actions\Action::make('browseItems')
                                                        ->label('')
                                                        ->icon('heroicon-m-magnifying-glass')
                                                        ->tooltip('Choose from list')
                                                        ->modalHeading('Choose Item')
                                                        ->modalWidth('lg')
                                                        ->form([
                                                            Forms\Components\Select::make('selected_item_id')
                                                                ->label('Item')
                                                                ->options(fn() => Item::query()->orderBy('item_no')->limit(50)->pluck('item_no', 'id'))
                                                                ->searchable()
                                                                ->preload()
                                                                ->required()
                                                                ->getSearchResultsUsing(fn(string $search) => self::searchItems($search))
                                                                ->getOptionLabelUsing(fn($value) => Item::find($value)?->item_no),
                                                        ])
                                                        ->action(function (array $data, Set $set, Get $get) {
                                                            $item = Item::find($data['selected_item_id']);

                                                            if ($item) {
                                                                $set('item_id', $item->id);
                                                                $set('item_no', $item->item_no);
                                                                $set('item_description', $item->description);
                                                                $set('price_before_discount', (float) $item->unit_price);
                                                                $set('uom_code', $item->uom_code);
                                                                $set('vat_code', $get('vat_code') ?: 'S');
                                                                self::recalcLine($set, $get);
                                                            }
                                                        }),

                                                    Forms\Components\Actions\Action::make('createItem')
                                                        ->label('')
                                                        ->icon('heroicon-m-plus')
                                                        ->tooltip('Create new item')
                                                        ->modalHeading('Create Item')
                                                        ->modalWidth('lg')
                                                        ->form([
                                                            Forms\Components\TextInput::make('item_no')
                                                                ->label('Item No.')
                                                                ->required()
                                                                ->unique(ignoreRecord: true)
                                                                ->maxLength(30),

                                                            Forms\Components\TextInput::make('description')
                                                                ->required()
                                                                ->maxLength(255),

                                                            Forms\Components\TextInput::make('uom_code')
                                                                ->label('UoM Code')
                                                                ->maxLength(20),

                                                            Forms\Components\TextInput::make('unit_price')
                                                                ->numeric()
                                                                ->step(0.001)
                                                                ->required()
                                                                ->prefix('KES'),
                                                        ])
                                                        ->action(function (array $data, Set $set, Get $get) {
                                                            $item = Item::create($data);

                                                            $set('item_id', $item->id);
                                                            $set('item_no', $item->item_no);
                                                            $set('item_description', $item->description);
                                                            $set('price_before_discount', (float) $item->unit_price);
                                                            $set('uom_code', $item->uom_code);
                                                            $set('vat_code', $get('vat_code') ?: 'S');
                                                            self::recalcLine($set, $get);
                                                        }),
                                                ]),

                                            Forms\Components\Hidden::make('item_id'),

                                            Forms\Components\TextInput::make('item_description')->required(),

                                            Forms\Components\TextInput::make('quantity')
                                                ->numeric()
                                                ->required()
                                                ->live(onBlur: true)
                                                ->extraInputAttributes(['class' => 'text-right'])
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcLine($set, $get)),

                                            Forms\Components\Select::make('warehouse_id')
                                                ->relationship('warehouse', 'code')
                                                ->searchable()
                                                ->extraAttributes(['class' => 'ar-line-select-with-create'])
                                                ->preload()
                                                ->live()
                                                ->createOptionForm([
                                                    Forms\Components\TextInput::make('code')
                                                        ->label('Warehouse Code')
                                                        ->required()
                                                        ->unique(ignoreRecord: true)
                                                        ->maxLength(20),

                                                    Forms\Components\TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255),
                                                ])
                                                ->createOptionAction(fn(Forms\Components\Actions\Action $action) => $action
                                                    ->modalHeading('Create Warehouse')
                                                    ->modalWidth('sm'))
                                                ->placeholder(null),

                                            Forms\Components\TextInput::make('uom_code')
                                                ->label('UoM Code'),

                                            Forms\Components\TextInput::make('price_before_discount')
                                                ->label('Unit Price')
                                                ->numeric()
                                                ->step(0.001)
                                                ->required()
                                                ->live(onBlur: true)
                                                ->extraInputAttributes(['class' => 'text-right'])
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcLine($set, $get)),

                                            Forms\Components\TextInput::make('discount')
                                                ->label('Discount %')
                                                ->numeric()
                                                ->step(0.000001)
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->rules(['numeric', 'max:50'])
                                                ->validationMessages(['max' => 'Discount cannot exceed 50%.'])
                                                ->extraInputAttributes(['class' => 'text-right'])
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcLine($set, $get)),

                                            Forms\Components\TextInput::make('price_after_discount')
                                                ->numeric()
                                                ->step(0.0001)
                                                ->disabled()
                                                ->dehydrated()
                                                ->extraInputAttributes(['class' => 'text-right']),

                                            Forms\Components\Select::make('vat_code')
                                                ->label('VAT Code')
                                                ->options([
                                                    'O0' => 'O0',
                                                    'O2' => 'O2',

                                                ])
                                                ->default('S')
                                                ->live()
                                                ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcLine($set, $get))
                                                ->placeholder(null),

                                            Forms\Components\TextInput::make('gross_price_after_discount')
                                                ->label('Gross Price after Disc.')
                                                ->numeric()
                                                ->step(0.0001)
                                                ->disabled()
                                                ->dehydrated()
                                                ->extraInputAttributes(['class' => 'text-right']),

                                            Forms\Components\TextInput::make('total')
                                                ->label('Total (LC)')
                                                ->numeric()
                                                ->step(0.001)
                                                ->disabled()
                                                ->dehydrated()
                                                ->extraInputAttributes(['class' => 'text-right']),

                                            Forms\Components\TextInput::make('gross_total')
                                                ->label('Gross Total (LC)')
                                                ->numeric()
                                                ->step(0.001)
                                                ->disabled()
                                                ->dehydrated()
                                                ->extraInputAttributes(['class' => 'text-right']),
                                        ])
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get))
                                        ->deleteAction(fn($action) => $action->after(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get)))
                                        ->addActionLabel('Add Line'),
                                ]),

                            Forms\Components\Tabs\Tab::make('Logistics')
                                ->schema([
                                    Forms\Components\Placeholder::make('logistics_placeholder')
                                        ->label('')
                                        ->content('No logistics fields configured yet.'),
                                ]),

                            Forms\Components\Tabs\Tab::make('Accounting')
                                ->schema([
                                    Forms\Components\Placeholder::make('accounting_placeholder')
                                        ->label('')
                                        ->content('No accounting fields configured yet.'),
                                ]),

                            Forms\Components\Tabs\Tab::make('Attachments')
                                ->schema([
                                    Forms\Components\FileUpload::make('attachments')
                                        ->multiple()
                                        ->directory('invoice-attachments'),
                                ]),

                            Forms\Components\Tabs\Tab::make('TIMS')
                                ->schema([
                                    Forms\Components\Placeholder::make('tims_placeholder')
                                        ->label('')
                                        ->content('No TIMS fields configured yet.'),
                                ]),

                            Forms\Components\Tabs\Tab::make('ETIMS')
                                ->schema([
                                    Forms\Components\Placeholder::make('etims_placeholder')
                                        ->label('')
                                        ->content('No eTIMS fields configured yet.'),
                                ]),
                        ]),


                    Forms\Components\Grid::make(12)
                        ->extraAttributes(['class' => 'ar-invoice-footer'])
                        ->schema([

                            Forms\Components\Grid::make(1)
                                ->columnSpan(3)
                                ->extraAttributes(['class' => 'ar-invoice-footer-left'])
                                ->schema([
                                    Forms\Components\Select::make('sales_employee_id')
                                        ->label('Sales Employee')
                                        ->relationship('salesEmployee', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field ar-narrow-field'])
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                        ])
                                        ->createOptionAction(fn(Forms\Components\Actions\Action $action) => $action
                                            ->modalHeading('Create Sales Employee')
                                            ->modalWidth('sm'))
                                        ->placeholder(null),

                                    Forms\Components\Select::make('owner_id')
                                        ->label('Owner')
                                        ->relationship('owner', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field ar-narrow-field'])
                                        ->placeholder(null),

                                    Forms\Components\Checkbox::make('payment_order_run')
                                        ->label('Payment Order Run'),
                                    Forms\Components\Grid::make(12)
                                        ->extraAttributes(['class' => 'ar-invoice-footer-left-bottom'])
                                        ->schema([
                                            Forms\Components\Grid::make(1)
                                                ->columnSpan(6)
                                                ->extraAttributes(['class' => 'ar-invoice-footer-left-inside-left'])
                                                ->schema([
                                                    Forms\Components\Textarea::make('remarks')
                                                        ->required()
                                                        ->inlineLabel()
                                                        ->validationMessages(['required' => 'Remarks field cannot be empty.'])
                                                        ->rows(3),
                                                ]),

                                            Forms\Components\Grid::make(1)
                                                ->columnSpan(6)
                                                ->extraAttributes(['class' => 'ar-invoice-footer-left-inside-right'])
                                                ->schema([
                                                    Forms\Components\TextInput::make('qr_code')
                                                        ->label('QRCode')
                                                        ->inlineLabel()
                                                        ->extraAttributes(['class' => 'ar-inline-field']),
                                                ]),

                                        ]),


                                ]),

                            Forms\Components\Grid::make(1)
                                ->columnSpan(6)
                                ->schema([]),

                            Forms\Components\Grid::make(1)
                                ->columnSpan(3)
                                ->extraAttributes(['class' => 'ar-invoice-totals'])
                                ->schema([
                                    Forms\Components\TextInput::make('total_before_discount')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->extraInputAttributes(['class' => 'text-right']),

                                    Forms\Components\TextInput::make('discount_percent')
                                        ->label('Discount %')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->extraInputAttributes(['class' => 'text-right'])
                                        ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get)),

                                    Forms\Components\TextInput::make('total_down_payment')
                                        ->label('Total Down Payment')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->extraInputAttributes(['class' => 'text-right'])
                                        ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get)),

                                    Forms\Components\TextInput::make('freight')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->extraInputAttributes(['class' => 'text-right'])
                                        ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get)),

                                    Forms\Components\Checkbox::make('rounding')
                                        ->label('Rounding')
                                        ->live()
                                        ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get)),

                                    Forms\Components\TextInput::make('tax')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->extraInputAttributes(['class' => 'text-right']),

                                    Forms\Components\TextInput::make('total_after_discount')
                                        ->label('Total')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field ar-total-row'])
                                        ->extraInputAttributes(['class' => 'text-right font-bold']),

                                    Forms\Components\TextInput::make('applied_amount')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field'])
                                        ->extraInputAttributes(['class' => 'text-right'])
                                        ->afterStateUpdated(fn(Set $set, Get $get) => self::recalcInvoiceTotals($set, $get)),

                                    Forms\Components\TextInput::make('balance_due')
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->inlineLabel()
                                        ->extraAttributes(['class' => 'ar-inline-field ar-total-row'])
                                        ->extraInputAttributes(['class' => 'text-right font-bold']),
                                ]),
                        ]),

                ]),

        ]);
    }


    /**
     * Autofills the Contact Person / BP Currency / KRA PIN / Customer Name
     * fields whenever a customer is picked from the Customer Code select.
     * These fields are plain editable inputs (not tied to formatStateUsing),
     * so this only sets their initial value on selection — the user can
     * freely edit them afterwards without their edits being overwritten.
     */
    protected static function syncCustomerFields(Set $set, $customerId): void
    {
        $customer = $customerId ? Customer::find($customerId) : null;

        $set('customer_id', $customer?->id);
        $set('contact_person', $customer?->contact_person);
        $set('bp_currency', $customer?->bp_currency ?? 'KES');
        $set('kra_pin', $customer?->kra_pin);
        $set('customer_name', $customer?->name);
    }

    public static function searchCustomersByCode(string $search): \Illuminate\Support\Collection
    {
        return Customer::where('code', 'like', "%{$search}%")
            ->orWhere('name', 'like', "%{$search}%")
            ->limit(20)
            ->pluck('code', 'id');
    }


    public static function searchItems(string $search): \Illuminate\Support\Collection
    {
        return Item::where('item_no', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
            ->limit(20)
            ->pluck('item_no', 'id');
    }

    protected static function recalcLine(Set $set, Get $get): void
    {
        $qty = (float) ($get('quantity') ?? 0);
        $price = (float) ($get('price_before_discount') ?? 0);
        $discount = (float) ($get('discount') ?? 0);
        $vatCode = $get('vat_code') ?? 'S';

        if ($discount > 50) {
            $discount = 50;
        }

        $vatRate = self::$vatRates[$vatCode] ?? 0.0;

        $priceAfter = round($price - ($price * $discount / 100), 4);
        $total = round($priceAfter * $qty, 3);
        $grossPriceAfter = round($priceAfter * (1 + $vatRate / 100), 4);
        $grossTotal = round($grossPriceAfter * $qty, 3);

        $set('price_after_discount', $priceAfter);
        $set('total', $total);
        $set('gross_price_after_discount', $grossPriceAfter);
        $set('gross_total', $grossTotal);
    }

    protected static function recalcInvoiceTotals(Set $set, Get $get): void
    {
        $lines = $get('lines') ?? [];

        $sum = collect($lines)->sum(fn($line) => (float) ($line['total'] ?? 0));
        $grossSum = collect($lines)->sum(fn($line) => (float) ($line['gross_total'] ?? 0));

        $discountPercent = (float) ($get('discount_percent') ?? 0);
        $afterDiscount = round($sum - ($sum * $discountPercent / 100), 3);
        $grossAfterDiscount = round($grossSum - ($grossSum * $discountPercent / 100), 3);

        $freight = (float) ($get('freight') ?? 0);
        $downPayment = (float) ($get('total_down_payment') ?? 0);
        $appliedAmount = (float) ($get('applied_amount') ?? 0);

        $tax = round($grossAfterDiscount - $afterDiscount, 3);
        $total = round($afterDiscount + $tax + $freight - $downPayment, 2);

        if ($get('rounding')) {
            $total = round($total);
        }

        $balanceDue = round($total - $appliedAmount, 2);

        $set('total_before_discount', round($sum, 3));
        $set('total_after_discount', $afterDiscount);
        $set('tax', $tax);
        $set('balance_due', $balanceDue);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doc_no')->label('No.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('posting_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('salesEmployee.name')->label('Sales Employee'),
                Tables\Columns\TextColumn::make('total_after_discount')->label('Total')->money('KES', divideBy: 1)->alignRight(),
                Tables\Columns\TextColumn::make('balance_due')->label('Balance Due')->money('KES', divideBy: 1)->alignRight(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\ViewAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
