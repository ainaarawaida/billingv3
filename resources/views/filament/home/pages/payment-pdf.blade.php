

<div class="bg-gray-200 p-6">
    @php
    // dump($id, $this->payment);
    // dd($payment['invoices']);
    @endphp
    <div class="btnaction">
        {{ $this->printAction }}
        <x-filament-actions::modals />
    </div>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <!-- Header -->
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-center">
                <img src="{{ $payment['logo'] ? asset('storage/'.$payment['logo']) : asset('assets/logo.png')  }}" class="w-32" alt="logo">
                {{-- <h1 class="text-right text-2xl font-bold text-gray-700">Recurring Invoice</h1> --}}

            </div>
            <hr class="border-green-500 border-2 h-px my-2">
            {{-- <div class="text-right text-gray-600">{{$payment['recurring_invoice_prefix']}}{{ $payment['numbering']}}</div> --}}
            @if(isset($payment['address']))
            <div class="flex justify-between mt-4">
                <div>
                    <div class="font-bold">From :</div>
                    {{ $payment['address'] }}<br>
                    {{ $payment['poscode'] }}<br>
                    {{ $payment['city'] }}<br>
                    {{ $payment['state'] }}<br>
                </div>
                <div class="w-52"></div>
                <div class="text-right">
                    <div class="font-bold">To :</div>
                    {{ $payment['to_address'] }}<br>
                    {{ $payment['to_poscode'] }}<br>
                    {{ $payment['to_city'] }}<br>
                    {{ $payment['to_state'] }}<br>
                </div>
            </div>
            @endif

            <div class="flex justify-between mt-4">
                <div>
                    <div class="font-bold">Payment Date:</div>
                    <div> {{ date("j F, Y", strtotime($payment['payment_date']) ) }}</div>
                </div>
                <div>
                    <div class="font-bold">Payment Method:</div>
                    <div>{{ $this->payment['payment_method'][$payment['payment_method_id']] }}</div>
                </div>
               
            </div>
            <div class="mt-4">
                Status: <span class="inline-block px-3 py-1 text-sm bg-green-100 text-green-700 rounded-full"> {{ $payment['status'] ? ucwords($payment['status']) : 'Draft' }}</span>
            </div>
        </div>
        
        <!-- Quotation Details -->
        <div class="mb-4">
            <div class="font-bold mb-2">{{ $payment['reference'] }}</div>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">#</th>
                        <th class="py-2">Invoice Date</th>
                        <th class="py-2">Invoice Status</th>
                        <th class="py-2">Final Amount</th>
                        <th class="py-2">Balance</th>
                    </tr>
                </thead>
                <tbody>
                 
                    <tr class="border-b">
                        <td class="py-2">{{$payment['invoice_prefix']}}{{ $payment['invoices']['numbering'] }}</td>
                        <td class="py-2">{{ date("j F, Y", strtotime($payment['invoices']['invoice_date']) ) }}</td>
                        <td class="py-2">{{ ucwords($payment['invoices']['invoice_status']) }}</td>
                        <td class="py-2">{{ $payment['invoices']['final_amount'] }}</td>
                        <td class="py-2">{{ $payment['invoices']['balance'] }}</td>
                    </tr>
                  
                   
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        {{-- <div class="text-right space-y-2">
            <div class="flex justify-between font-bold text-lg">
                <span>Final amount:</span>
                <span>{{ $payment['final_amount'] }}</span>
            </div>
        </div> --}}

     
            <!-- payment -->
            {{-- <table class="w-full text-left border-collapse mt-4">
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
                    @foreach($payment['payments'] as $key => $val)
                    <tr class="border-b">
                        <td class="py-2">{{$key + 1}}</td>
                        <td class="py-2">{{ $payment['payment_method'][$val['payment_method_id']] }}</td>
                        <td class="py-2">{{ date("j F, Y", strtotime($val['payment_date']) ) }}</td>
                        <td class="py-2">{{ $val['reference'] }}</td>
                        <td class="py-2">{{ $val['status']) }}</td>
                        <td class="py-2">{{ $val['total'] }}</td>
                    </tr>
                    @endforeach
                
                </tbody>
            </table> --}}

            <!-- Summary -->
            <div class="text-right space-y-2">
                <div class="flex justify-between font-bold text-lg">
                    <span>Total Payment (RM):</span>
                    <span>{{ $payment['total'] }}</span>
                </div>
            </div>
            <div class="mt-4">
                Notes: {{ $payment['notes'] }}
            </div>
      

        <!-- Footer -->
        {{-- <div class="mt-8 text-center text-gray-600">
            {{ $payment['terms_conditions']}}<br>
            {{ $payment['footer']}}
        </div> --}}
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
