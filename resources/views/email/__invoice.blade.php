<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0891b2; color: white; padding: 20px; text-align: center; }
        .invoice-details { margin: 20px 0; padding: 20px; background-color: #f8f9fa; }
        .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .invoice-table th { background-color: #0891b2; color: white; }
        .totals { margin: 20px 0; text-align: right; }
        .totals-row { margin: 10px 0; font-size: 16px; }
        .total-amount { font-weight: bold; font-size: 20px; color: #0891b2; }
        .footer { margin-top: 40px; padding: 20px; background-color: #f8f9fa; text-align: center; font-size: 14px; color: #666; }
        .button { display: inline-block; padding: 12px 30px; background-color: #0891b2; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice</h1>
            <p>{{ $invoice->invoice_no }}</p>
        </div>

        <div class="invoice-details">
            <h2>Invoice Details</h2>
            <p><strong>Invoice Date:</strong> {{ date('d/m/Y', strtotime($invoice->invoice_date)) }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date ? date('d/m/Y', strtotime($invoice->due_date)) : 'N/A' }}</p>
            @if($invoice->order_number)
            <p><strong>Order Number:</strong> {{ $invoice->order_number }}</p>
            @endif
        </div>

        <div class="invoice-details">
            <h3>Bill To:</h3>
            <p><strong>{{ $invoice->customer_name }}</strong></p>
            @if($invoice->customer_ssn)
            <p>SSN: {{ $invoice->customer_ssn }}</p>
            @endif
            @if($invoice->customer_address)
            <p>{{ $invoice->customer_address }}</p>
            @endif
            @if($invoice->customer_email)
            <p>Email: {{ $invoice->customer_email }}</p>
            @endif
        </div>

        <div class="invoice-details">
            <h3>From:</h3>
            <p><strong>{{ $invoice->created_by }}</strong></p>
        </div>

        @if($invoice->regarding_job || $invoice->comment_notes)
        <div class="invoice-details">
            @if($invoice->regarding_job)
            <p><strong>Regarding/Job:</strong> {{ $invoice->regarding_job }}</p>
            @endif
            @if($invoice->comment_notes)
            <p><strong>Notes:</strong> {{ $invoice->comment_notes }}</p>
            @endif
        </div>
        @endif

        <h3>Invoice Items</h3>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price (Excl. VAT)</th>
                    <th>Discount</th>
                    <th>VAT %</th>
                    <th>Total (Incl. VAT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->notes)
                        <br><small>{{ $item->notes }}</small>
                        @endif
                    </td>
                    <td>{{ $item->quantity }} {{ $item->unit_of_measure }}</td>
                    <td>{{ number_format($item->price_excl_vat, 2) }} {{ $invoice->currency }}</td>
                    <td>{{ $item->discount }}%</td>
                    <td>{{ $item->vat_percentage }}%</td>
                    <td>{{ number_format($item->line_total_incl_vat, 2) }} {{ $invoice->currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <strong>Subtotal (Excl. VAT):</strong> {{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}
            </div>
            @if($invoice->total_discount > 0)
            <div class="totals-row">
                <strong>Discount:</strong> -{{ number_format($invoice->total_discount, 2) }} {{ $invoice->currency }}
            </div>
            @endif
            <div class="totals-row">
                <strong>Total VAT:</strong> {{ number_format($invoice->total_vat, 2) }} {{ $invoice->currency }}
            </div>
            <div class="totals-row total-amount">
                <strong>Total Amount (Incl. VAT):</strong> {{ number_format($invoice->total_amount_incl_vat, 2) }} {{ $invoice->currency }}
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $site_url }}/invoices/view/{{ $invoice->id }}" class="button">View Invoice Online</a>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is an automated email from X-US Invoice System. Please do not reply to this email.</p>
            <p>If you have any questions, please contact {{ $invoice->created_by }}</p>
        </div>
    </div>
</body>
</html>