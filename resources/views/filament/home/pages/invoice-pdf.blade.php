

<div class="bg-gray-200 p-6">
    @php
    // dump($id, $this->payment);
    // dd(asset('logo.png'));
    @endphp
    <div class="btnaction">
        {{ $this->printAction }}
        {{ $this->paymentAction }}
        <x-filament-actions::modals />
    </div>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <!-- Header -->
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-center">
                <img src="{{ $invoice['logo'] ? asset('storage/'.$invoice['logo']) : asset('assets/logo.png')  }}" class="w-32" alt="logo">
                <h1 class="text-right text-2xl font-bold text-gray-700">Invoice</h1>

            </div>
            <hr class="border-green-500 border-2 h-px my-2">
            <div class="text-right text-gray-600">{{$invoice['prefix']}}{{ $invoice['numbering']}}</div>
            <div class="flex justify-between mt-4">
                <div>
                    <div class="font-bold">From :</div>
                    {{ $invoice['address'] }}<br>
                    {{ $invoice['poscode'] }}<br>
                    {{ $invoice['city'] }}<br>
                    {{ $invoice['state'] }}<br>
                </div>
                <div class="w-52"></div>
                <div class="text-right">
                    <div class="font-bold">To :</div>
                    {{ $invoice['to_address'] }}<br>
                    {{ $invoice['to_poscode'] }}<br>
                    {{ $invoice['to_city'] }}<br>
                    {{ $invoice['to_state'] }}<br>
                </div>
            </div>
            <div class="flex justify-between mt-4">
                <div>
                    <div class="font-bold">Invoice Date:</div>
                    <div> {{ date("j F, Y", strtotime($invoice['invoice_date']) ) }}</div>
                </div>
                <div class="text-right">
                    <div class="font-bold">Pay Before:</div>
                    <div>{{ date("j F, Y", strtotime($invoice['pay_before']) ) }}</div>
                </div>
            </div>
            <div class="mt-4">
                Status: <span class="inline-block px-3 py-1 text-sm bg-green-100 text-green-700 rounded-full"> {{ $invoice['invoice_status'] ? ucwords($invoice['invoice_status']) : 'Draft' }}</span>
            </div>
        </div>
        
        <!-- Quotation Details -->
        <div class="mb-4">
            <div class="font-bold mb-2">{{ $invoice['summary'] }}</div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">#</th>
                        <th class="py-2">Item / Description</th>
                        <th class="py-2">Price</th>
                        <th class="py-2">Quantity</th>
                        <th class="py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $key => $val)
                    <tr class="border-b">
                        <td class="py-2">{{$key + 1}}</td>
                        <td class="py-2">{{ $val?->title }} {{ $val?->product?->title }}</td>
                        <td class="py-2">{{ $val?->price }}</td>
                        <td class="py-2">{{ $val?->quantity }} {{ $val?->unit }}</td>
                        <td class="py-2">{{ $val?->total }}</td>
                    </tr>
                    @endforeach
                   
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        <div class="text-right space-y-2">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>{{ $invoice['sub_total'] }}</span>
            </div>
            <div class="flex justify-between">
                <span>Taxes:</span>
                <span>{{ $invoice['taxes'] }}</span>
            </div>
            <div class="flex justify-between">
                <span>Percentage tax:</span>
                <span>{{ $invoice['percentage_tax'] }}</span>
            </div>
            <div class="flex justify-between">
                <span>Delivery:</span>
                <span>{{ $invoice['delivery'] }}</span>
            </div>
            <div class="flex justify-between font-bold text-lg">
                <span>Final amount:</span>
                <span>{{ $invoice['final_amount'] }}</span>
            </div>
        </div>

        @if($invoice['payment'])
            <!-- payment -->
            <table class="w-full text-left border-collapse mt-4">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">#</th>
                        <th class="py-2">Payment Name</th>
                        <th class="py-2">Payment Date</th>
                        <th class="py-2">Reference</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice['payment'] as $key => $val)
                    <tr class="border-b">
                        <td class="py-2">{{$key + 1}}</td>
                        <td class="py-2">{{ $invoice['payment_method'][$val['payment_method_id']] }}</td>
                        <td class="py-2">{{ date("j F, Y", strtotime($val['payment_date']) ) }}</td>
                        <td class="py-2">{{ $val['reference'] }}</td>
                        <td class="py-2">{{ ucwords($val['status']) }}</td>
                        <td class="py-2">{{ $val['total'] }}</td>
                    </tr>
                    @endforeach
                
                </tbody>
            </table>

            <!-- Summary -->
            <div class="text-right space-y-2">
                <div class="flex justify-between font-bold text-lg">
                    <span>Total Completed Payment:</span>
                    <span>{{ $invoice['totalPayment'] }}</span>
                </div>
                <div class="flex justify-between font-bold text-lg">
                    <span>Balance:</span>
                    <span>{{ $invoice['balance'] }}</span>
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-600">
            {{ $invoice['terms_conditions']}}<br>
            {{ $invoice['footer']}}
        </div>
    </div>
</div>


<script>
    document.querySelector(".printme").addEventListener("click", function(e) {
        e.preventDefault();
        document.querySelector(".btnaction").style.display = 'none';
        document.querySelector(".myfooter").style.display = 'none';
        window.print();
        document.querySelector(".btnaction").style.display = 'block';
        document.querySelector(".myfooter").style.display = 'block';
    })
</script>
