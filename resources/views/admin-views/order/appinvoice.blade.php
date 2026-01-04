@extends('layouts.admin.mobile')

@section('title', '')

@push('css_or_js')
    <style>
        @media print {
            .non-printable {
                display: none;
            }

            .printable {
                display: block;
                font-family: emoji !important;
            }

            body {
                -webkit-print-color-adjust: exact !important;
                /* Chrome, Safari */
                color-adjust: exact !important;
                font-family: emoji !important;
            }
        }
    </style>

    <style type="text/css" media="print">
        @page {
            size: auto;
            /* auto is the initial value */
            margin: 2px;
            /* this affects the margin in the printer settings */
            font-family: emoji !important;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="row" id="printableArea" style="font-family: emoji;">

            <div class="col-md-5 col-12">
                <div class="text-center pt-4 mb-3">
                    <h2 style="line-height: 1">{{ $order->restaurant->name }}</h2>
                    <h5 style="font-size: 20px;font-weight: lighter;line-height: 1">
                        {{ $order->restaurant->address }}
                    </h5>
                    <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                        Phone : {{ $order->restaurant->phone }}
                    </h5>
                </div>

                <span>---------------------------------------------------------------------------------</span>
                <div class="row mt-3">
                    <div class="col-6">
                        <h5><strong>Order ID :</strong> {{ $order['id'] }}</h5>
                    </div>
                    <div class="col-6">
                        <h5 style="font-weight: lighter">
                            {{ date('d/M/Y ' . config('timeformat'), strtotime($order['created_at'])) }}
                        </h5>
                    </div>
                    <div class="col-12">
                        <h5>
                            <strong>Customer Name :</strong> {{ $order->customer['f_name'] . ' ' . $order->customer['l_name'] }}
                        </h5>
                        <h5>
                            <strong>Phone :</strong> {{ $order->customer['phone'] }}
                        </h5>
                        <h5 class="text-break">
                            <strong>Address :</strong>
                            {{ isset($order->delivery_address) ? json_decode($order->delivery_address, true)['address'] : '' }}
                        </h5>
                    </div>
                </div>
                <h5 class="text-uppercase"></h5>
                <span>---------------------------------------------------------------------------------</span>
                <table class="table table-bordered mt-3" style="width: 98%">
                    <thead>
                        <tr>
                            <th style="width: 10%; font-weight: bold;">QTY</th>
                            <th style="font-weight: bold;">DESC</th>
                            <th style="font-weight: bold;">PRICE</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($sub_total = 0)
                        @php($total_tax = 0)
                        @php($total_dis_on_pro = 0)
                        @php($add_ons_cost = 0)
                        @foreach ($order->details as $detail)
                            @if ($detail->food)
                                <tr>
                                    <td style="font-weight: bold;">
                                        {{ $detail['quantity'] }}
                                    </td>
                                    <td class="text-break">
                                        <strong>{{ $detail->food['name'] }}</strong> <br>
                                        @if (count(json_decode($detail['variation'], true)) > 0)
                                            <strong><u>Variation : </u></strong>
                                            @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                <div class="font-size-sm text-body">
                                                    <span>{{ $key1 }} : </span>
                                                    <span
                                                        class="font-weight-bold">{{ $key1 == 'price' ? \App\CentralLogics\Helpers::format_currency($variation) : $variation }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="font-size-sm text-body">
                                                <span>{{ 'Price' }} : </span>
                                                <span
                                                    class="font-weight-bold">{{ \App\CentralLogics\Helpers::format_currency($detail->price) }}</span>
                                            </div>
                                        @endif

                                        @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                            @if ($key2 == 0)
                                                <strong><u>Addons : </u></strong>
                                            @endif
                                            <div class="font-size-sm text-body">
                                                <span class="text-break">{{ $addon['name'] }} : </span>
                                                <span class="font-weight-bold">
                                                    {{ $addon['quantity'] }} x
                                                    {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                </span>
                                            </div>
                                            @php($add_ons_cost += $addon['price'] * $addon['quantity'])
                                        @endforeach
                                    </td>
                                    <td style="width: 28%; font-weight: bold;">
                                        @php($amount = $detail['price'] * $detail['quantity'])
                                        {{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                    </td>
                                </tr>
                                @php($sub_total += $amount)
                                @php($total_tax += $detail['tax_amount'] * $detail['quantity'])
                            @elseif($detail->campaign)
                                <tr>
                                    <td style="font-weight: bold;">
                                        {{ $detail['quantity'] }}
                                    </td>
                                    <td class="text-break">
                                        <strong>{{ $detail->campaign['title'] }}</strong> <br>
                                        @if (count(json_decode($detail['variation'], true)) > 0)
                                            <strong><u>Variation : </u></strong>
                                            @foreach (json_decode($detail['variation'], true)[0] as $key1 => $variation)
                                                <div class="font-size-sm text-body">
                                                    <span>{{ $key1 }} : </span>
                                                    <span
                                                        class="font-weight-bold">{{ $key1 == 'price' ? \App\CentralLogics\Helpers::format_currency($variation) : $variation }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="font-size-sm text-body">
                                                <span>{{ 'Price' }} : </span>
                                                <span
                                                    class="font-weight-bold">{{ \App\CentralLogics\Helpers::format_currency($detail->price) }}</span>
                                            </div>
                                        @endif

                                        @foreach (json_decode($detail['add_ons'], true) as $key2 => $addon)
                                            @if ($key2 == 0)
                                                <strong><u>Addons : </u></strong>
                                            @endif
                                            <div class="font-size-sm text-body">
                                                <span class="text-break">{{ $addon['name'] }} : </span>
                                                <span class="font-weight-bold">
                                                    {{ $addon['quantity'] }} x
                                                    {{ \App\CentralLogics\Helpers::format_currency($addon['price']) }}
                                                </span>
                                            </div>
                                            @php($add_ons_cost += $addon['price'] * $addon['quantity'])
                                        @endforeach
                                    </td>
                                    <td style="width: 28%; font-weight: bold;">
                                        @php($amount = $detail['price'] * $detail['quantity'])
                                        {{ \App\CentralLogics\Helpers::format_currency($amount) }}
                                    </td>
                                </tr>
                                @php($sub_total += $amount)
                                @php($total_tax += $detail['tax_amount'] * $detail['quantity'])
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <span>---------------------------------------------------------------------------------</span>
                <div class="row justify-content-md-end mb-3" style="width: 97%">
                    <div class="col-md-7 col-lg-7">
                        <dl class="row text-right">
                            <dt class="col-6" style="font-weight: bold;">Items Price:</dt>
                            <dd class="col-6">{{ \App\CentralLogics\Helpers::format_currency($sub_total) }}</dd>
                            <dt class="col-6" style="font-weight: bold;">Addon Cost:</dt>
                            <dd class="col-6">
                                {{ \App\CentralLogics\Helpers::format_currency($add_ons_cost) }}
                                <hr>
                            </dd>
                            <dt class="col-6" style="font-weight: bold;">Subtotal:</dt>
                            <dd class="col-6">
                                {{ \App\CentralLogics\Helpers::format_currency($sub_total + $add_ons_cost) }}</dd>
                            <dt class="col-6" style="font-weight: bold;">{{ __('messages.discount') }}:</dt>
                            <dd class="col-6">
                                - {{ \App\CentralLogics\Helpers::format_currency($order['restaurant_discount_amount']) }}
                            </dd>
                            <dt class="col-6" style="font-weight: bold;">Coupon Discount:</dt>
                            <dd class="col-6">
                                - {{ \App\CentralLogics\Helpers::format_currency($order['coupon_discount_amount']) }}</dd>
                            <dt class="col-6" style="font-weight: bold;">{{ __('messages.vat/tax') }}:</dt>
                            <dd class="col-6">+
                                {{ \App\CentralLogics\Helpers::format_currency($order['total_tax_amount']) }}</dd>
                            <dt class="col-6" style="font-weight: bold;">Delivery Fee:</dt>
                            <dd class="col-6">
                                @php($del_c = $order['delivery_charge'])
                                {{ \App\CentralLogics\Helpers::format_currency($del_c) }}
                                <hr>
                            </dd>

                            <dt class="col-6" style="font-size: 20px; font-weight: bold;">Total:</dt>
                            <dd class="col-6" style="font-size: 20px">
                                {{ \App\CentralLogics\Helpers::format_currency($sub_total + $del_c + $order['total_tax_amount'] + $add_ons_cost - $order['coupon_discount_amount'] - $order['restaurant_discount_amount']) }}
                            </dd>
                        </dl>
                    </div>
                </div>
                <span>---------------------------------------------------------------------------------</span>
                <h5 class="text-center pt-3" style="font-weight: bold;">
                    """THANK YOU"""
                </h5>
                <span>---------------------------------------------------------------------------------</span>
            </div>
        </div>
    </div>

@endsection

@push('script')
    <script>
        function printDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    </script>
@endpush
