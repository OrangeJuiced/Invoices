<?php
/**
 * This file is part of consoletvs/invoices.
 *
 * (c) Erik Campobadal <soc@erik.cat>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ConsoleTVs\Invoices\Classes;

use Carbon\Carbon;
use ConsoleTVs\Invoices\Traits\Setters;
use Illuminate\Support\Collection;
use Storage;

/**
 * This is the Invoice class.
 *
 * @author Erik Campobadal <soc@erik.cat>
 */
class Invoice
{
    use Setters;

    /**
     * Invoice name.
     *
     * @var string
     */
    public $name;

    /**
     * Invoice item collection.
     *
     * @var Illuminate\Support\Collection
     */
    public $items;

    /**
     * Invoice currency.
     *
     * @var string
     */
    public $currency;

    /**
     * Invoice tax.
     *
     * @var int
     */
    public $tax;

    /**
     * Invoice tax type.
     *
     * @var string
     */
    public $tax_type;

    /**
     * Invoice number.
     *
     * @var int
     */
    public $number = null;

    /**
     * Invoice decimal precision.
     *
     * @var int
     */
    public $decimals;

    /**
     * Invoice decimal precision.
     *
     * @var int
     */
    public $decimalpoint;

    /**
     * Invoice decimal precision.
     *
     * @var int
     */
    public $thousandseparator;

    /**
     * Invoice logo.
     *
     * @var string
     */
    public $logo;

    /**
     * Invoice Logo Height.
     *
     * @var int
     */
    public $logo_height;

    /**
     * Invoice Date.
     *
     * @var Carbon\Carbon
     */
    public $date;

    /**
     * Invoice Notes.
     *
     * @var string
     */
    public $notes;

    /**
     * Invoice Business Details.
     *
     * @var array
     */
    public $business_details;

    /**
     * Invoice Customer Details.
     *
     * @var array
     */
    public $customer_details;

    /**
     * Invoice Footnote.
     *
     * @var array
     */
    public $footnote;

    /**
     * Stores the PDF object.
     *
     * @var Dompdf\Dompdf
     */
    private $pdf;

    /**
     * Stores the PDF object.
     *
     * @var string
     */
    public $status;

    /**
     * Create a new invoice instance.
     *
     * @method __construct
     *
     * @param string $name
     */
    public function __construct($name = 'Invoice')
    {
        $this->name = $name;
        $this->items = Collection::make([]);
        $this->currency = config('invoices.currency');
        $this->tax = config('invoices.tax');
        $this->decimals = config('invoices.decimals');
        $this->logo = config('invoices.logo');
        $this->logo_height = config('invoices.logo_height');
        $this->date = Carbon::now();
        $this->business_details = Collection::make(config('invoices.business_details'));
        $this->customer_details = Collection::make([]);
        $this->footnote = config('invoices.footnote');
        $this->decimalpoint = config('invoices.decimalpoint');
        $this->thousandseparator = config('invoices.thousandseparator');
        $this->tax_type = config('invoices.tax_type');
    }

    /**
     * Return a new instance of Invoice.
     *
     * @method make
     *
     * @param string $name
     *
     * @return ConsoleTVs\Invoices\Classes\Invoice
     */
    public static function make($name = 'Invoice')
    {
        return new self($name);
    }

    /**
     * Adds an item to the invoice.
     *
     * @method addItem
     *
     * @param string $name
     * @param string $start
     * @param string $end
     * @param float  $price
     * @return self
     */
    public function addItem($name, $start, $end, $price)
    {
        $this->items->push(Collection::make([
            'name'       => $name,
            'start'      => $start,
            'end'        => $end,
            'price'      => number_format($price, $this->decimals, $this->decimalpoint, $this->thousandseparator),
        ]));

        return $this;
    }

    /**
     * Pop the last invoice item.
     *
     * @method popItem
     *
     * @return self
     */
    public function popItem()
    {
        $this->items->pop();

        return $this;
    }

    /**
     * Return the currency object.
     *
     * @method formatCurrency
     *
     * @return stdClass
     */
    public function formatCurrency()
    {
        $currencies = json_decode(file_get_contents(__DIR__.'/../Currencies.json'));
        $currency = $this->currency;

        return $currencies->$currency;
    }

    /**
     * Return the subtotal invoice price. Tax is removed from the subtotal.
     *
     * @method subTotalPrice
     *
     * @return int
     */
    private function subTotalPrice()
    {
        $sum = $this->items->sum(function ($item) {
            return round($item['price'], 2);
        });

        return round($sum / ($this->tax + 100) * 100, 2);
    }

    /**
     * Return formatted sub total price.
     *
     * @method subTotalPriceFormatted
     *
     * @return int
     */
    public function subTotalPriceFormatted()
    {
        return number_format($this->subTotalPrice(), $this->decimals, $this->decimalpoint, $this->thousandseparator);
    }

    /**
     * Return the total invoice price after applying the tax.
     *
     * @method totalPrice
     *
     * @return int
     */
    private function totalPrice()
    {
        return bcadd($this->subTotalPrice(), $this->taxPrice(), $this->decimals);
    }

    /**
     * Return formatted total price.
     *
     * @method totalPriceFormatted
     *
     * @return int
     */
    public function totalPriceFormatted()
    {
        return number_format($this->totalPrice(), $this->decimals, $this->decimalpoint, $this->thousandseparator);
    }

    /**
     * Return the amount of tax on the invoice.
     *
     * @method taxPrice
     *
     * @return float
     */
    private function taxPrice()
    {
        if ($this->tax_type == 'percentage') {
            return bcdiv(bcmul($this->tax, $this->subTotalPrice(), $this->decimals), 100, $this->decimals);
        }

        return $this->tax;
    }

    /**
     * Return formatted tax.
     *
     * @method taxPriceFormatted
     *
     * @return int
     */
    public function taxPriceFormatted()
    {
        return number_format($this->taxPrice(), $this->decimals, $this->decimalpoint, $this->thousandseparator);
    }

    /**
     * Generate the PDF.
     *
     * @method generate
     *
     * @return self
     */
    private function generate()
    {
        $this->pdf = PDF::generate($this);

        return $this;
    }

    /**
     * Downloads the generated PDF.
     *
     * @method download
     *
     * @param string $name
     *
     * @return response
     */
    public function download($name = 'invoice')
    {
        $this->generate();

        return $this->pdf->stream($name);
    }

    /**
     * Save the generated PDF.
     *
     * @method save
     *
     * @param string $name
     *
     */
    public function save($name = 'invoice.pdf')
    {
        $invoice = $this->generate();

        Storage::put($name, $invoice->pdf->output());
    }

    /**
     * Show the PDF in the browser.
     *
     * @method show
     *
     * @param string $name
     *
     * @return response
     */
    public function show($name = 'invoice')
    {
        $this->generate();

        return $this->pdf->stream($name, ['Attachment' => false]);
    }
}
