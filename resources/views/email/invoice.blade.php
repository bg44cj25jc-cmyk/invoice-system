<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f9fafc; font-family:Arial, sans-serif;">
    <table width="600" border="0" cellspacing="0" cellpadding="0" bgcolor="#f9fafc" style="margin: 0 auto">
        <tr>
            <td style="padding:35px 40px;">
                <!-- Header: Company Info & Logo -->
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-bottom: 20px;">
                    <tr>
                        <td valign="top">
                            <p style="margin:0; font-weight:600; font-size:12px; color:#61646b; margin-bottom: 2px;">
                                {{ $invoice->created_by }}
                            </p>
                            <p style="margin:0; font-weight:400; font-size:12px; line-height: 1.35em; color:#74787b;">
                                @if(isset($invoice->user))
                                    @if($invoice->user->address){{ $invoice->user->address }}<br>@endif
                                    @if($invoice->user->city){{ $invoice->user->city }}@endif
                                    @if($invoice->user->postal_code) {{ $invoice->user->postal_code }}@endif
                                    @if($invoice->user->city || $invoice->user->postal_code)<br>@endif
                                    @if($invoice->user->country){{ $invoice->user->country }}@endif
                                @endif
                            </p>
                        </td>
                        <td align="right" valign="top">
                            @if(isset($invoice->user) && $invoice->user->profile_logo)
                                <img src="{{ $profile_logo }}" alt="Company Logo" width="120" style="display:block; max-width:100%;">
                            @else
                                <p style="margin:0; font-weight:700; font-size:20px; color:#5f8ab5;">X-US</p>
                            @endif
                        </td>
                    </tr>
                </table>

                <!-- Invoice Details & Billed To -->
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                    <tr>
                        <td valign="top" style="border:1px solid #5f8ab5; border-left: 0; padding: 15px 20px 15px 0;">
                            <p style="margin:0; font-weight:600; font-size:14px; line-height: 1.35em; color:#5f8ab5; margin-bottom: 2px;">
                                Invoice Date
                            </p>
                            <p style="margin:0; font-weight:600; font-size:12px; color:#61646b; margin-bottom: 12px;">
                                {{ date('d M, Y', strtotime($invoice->invoice_date)) }}
                            </p>
                            <p style="margin:0; font-weight:600; font-size:12px; color:#5f8ab5; margin-bottom: 2px;">
                                Invoice Number
                            </p>
                            <p style="margin:0; font-weight:600; font-size:12px; line-height: 1.35em; color:#74787b;">
                                {{ $invoice->invoice_no }}
                            </p>
                            @if($invoice->due_date)
                            <p style="margin:12px 0 2px 0; font-weight:600; font-size:12px; color:#5f8ab5;">
                                Due Date
                            </p>
                            <p style="margin:0; font-weight:600; font-size:12px; color:#61646b;">
                                {{ date('d M, Y', strtotime($invoice->due_date)) }}
                            </p>
                            @endif
                        </td>
                        <td valign="top" style="border:1px solid #5f8ab5; border-right: 0; padding: 15px 0 15px 20px;">
                            <p style="margin:0; font-weight:600; font-size:14px; line-height: 1.35em; color:#5f8ab5; margin-bottom: 2px;">
                                Billed to
                            </p>
                            <p style="margin:0; font-weight:600; font-size:12px; color:#5f8ab5; margin-bottom: 2px;">
                                {{ $invoice->customer_name }}
                            </p>
                            <p style="margin:0; font-weight:400; font-size:12px; line-height: 1.35em; color:#74787b;">
                                @if($invoice->customer_address){{ $invoice->customer_address }}<br>@endif
                                @if($invoice->customer_ssn)SSN: {{ $invoice->customer_ssn }}<br>@endif
                                @if($invoice->customer_email){{ $invoice->customer_email }}@endif
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Products Table Header -->
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                    <tr>
                        <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px 14px 0;">
                            <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Product Name</p>
                        </td>
                        <td valign="top" style="text-align: center; border-bottom:1px solid #5f8ab5; padding: 14px 14px;">
                            <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Qty</p>
                        </td>
                        <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 14px;">
                            <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Price Excluding VAT</p>
                        </td>
<!--
                        <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 14px;">
                            <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">VAT</p>
                        </td>
-->
                        <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 0 14px 14px;">
                            <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Line total</p>
                        </td>
                    </tr>

                    <!-- Products Loop -->
                    @foreach($invoice->items as $item)
                    <tr>
                        <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px 14px 0;">
                            <p style="margin:0; font-weight:600; font-size:12px; color:#5f8ab5; margin-bottom: 5px;">
                                {{ $item->product_name }}
                            </p>
                            @if($item->discount > 0 || $item->notes)
                            <p style="margin:0; font-weight:400; font-size:11px; line-height: 1.35em; color:#74787b;">
                                @if($item->discount > 0)Discount: {{ $item->discount }}%<br>@endif
                                @if($item->notes){{ $item->notes }}@endif
                            </p>
                            @endif
                        </td>
                        <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px;">
                            <p style="text-align: center; margin:0; font-weight:400; font-size:12px; line-height: 1.35em; color:#74787b;">
                                {{ $item->quantity }}
                            </p>
                        </td>
                        <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px;">
                            <p style="text-align: right; margin:0; font-weight:400; font-size:12px; line-height: 1.35em; color:#74787b;">
                                {{ number_format($item->price_excl_vat, 0) }} {{ $invoice->currency }}
                            </p>
                        </td>
<!--
                        <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px;">
                            <p style="text-align: right; margin:0; font-weight:400; font-size:12px; line-height: 1.35em; color:#74787b;">
                                {{ $item->vat_percentage }}%
                            </p>
                        </td>
-->
                        <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 0 14px 14px;">
                            <p style="margin:0; font-weight:400; font-size:12px; line-height: 1.35em; color:#74787b;">
                                {{ number_format($item->line_total_incl_vat, 0) }} {{ $invoice->currency }}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                </table>

                <!-- Totals Section -->
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                    <tr>
                        <td valign="top" style="padding:0; width: 50%;">
                            <!-- Empty space for notes if needed -->
                        </td>
                        <td valign="top" style="padding:0; width: 50%;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse;">
                                <tr>
                                    <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px 14px 0;">
                                        <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Subtotal</p>
                                    </td>
                                    <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 0 14px 14px;">
                                        <p style="margin:0; font-weight:400; font-size:14px; color:#74787b;">
                                            {{ number_format($invoice->subtotal, 0) }} {{ $invoice->currency }}
                                        </p>
                                    </td>
                                </tr>
                                @if($invoice->total_discount > 0)
                                <tr>
                                    <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px 14px 0;">
                                        <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Discount</p>
                                    </td>
                                    <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 0 14px 14px;">
                                        <p style="margin:0; font-weight:400; font-size:14px; color:#74787b;">
                                            -{{ number_format($invoice->total_discount, 0) }} {{ $invoice->currency }}
                                        </p>
                                    </td>
                                </tr>
                                @endif
<!--
                                <tr>
                                    <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px 14px 0;">
                                        <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">VAT</p>
                                    </td>
                                    <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 0 14px 14px;">
                                        <p style="margin:0; font-weight:400; font-size:14px; color:#74787b;">
                                            {{ number_format($invoice->total_vat, 0) }} {{ $invoice->currency }}
                                        </p>
                                    </td>
                                </tr>
-->
                                <tr>
                                    <td valign="top" style="border-bottom:1px solid #5f8ab5; padding: 14px 14px 14px 0;">
                                        <p style="margin:0; font-weight:600; font-size:14px; color:#5f8ab5;">Total</p>
                                    </td>
                                    <td valign="top" style="text-align: right; border-bottom:1px solid #5f8ab5; padding: 14px 0 14px 14px;">
                                        <p style="margin:0; font-weight:700; font-size:14px; color:#5f8ab5;">
                                            {{ number_format($invoice->total_amount_incl_vat, 0) }} {{ $invoice->currency }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Notes Section -->
                @if($invoice->regarding_job || $invoice->comment_notes)
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-top: 20px;">
                    <tr>
                        <td style="padding: 15px; background-color: #f0f4f8; border: 1px solid #5f8ab5;">
                            @if($invoice->regarding_job)
                            <p style="margin:0 0 10px 0; font-weight:600; font-size:12px; color:#5f8ab5;">
                                Regarding/Job:
                            </p>
                            <p style="margin:0 0 15px 0; font-weight:400; font-size:12px; line-height:1.5em; color:#74787b;">
                                {{ $invoice->regarding_job }}
                            </p>
                            @endif
                            @if($invoice->comment_notes)
                            <p style="margin:0 0 10px 0; font-weight:600; font-size:12px; color:#5f8ab5;">
                                Notes:
                            </p>
                            <p style="margin:0; font-weight:400; font-size:12px; line-height:1.5em; color:#74787b;">
                                {{ $invoice->comment_notes }}
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
                @endif

                <!-- Footer -->
                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-top: 30px;">
                    <tr>
                        <td style="text-align: center; padding: 20px 0;">
                            <p style="margin:0 0 10px 0; font-weight:600; font-size:14px; color:#5f8ab5;">
                                Thank you for your business!
                            </p>
                            <p style="margin:0; font-weight:400; font-size:11px; line-height:1.5em; color:#999; font-style: italic;">
                                Invoice was created in an electronic account system [...] according to Icelandic<br>
                                ordinance no. 505/2019.<br>
                                {{ isset($site_url) ? $site_url : 'www.x-us.com' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>