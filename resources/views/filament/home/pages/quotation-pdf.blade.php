
@php
    
// dd(asset('logo.png'));
@endphp
<div class="bg-gray-200 p-6">
    <div>
        {{ $this->printAction }}
    </div>

<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-lg">
    <!-- Header -->
    <div class="border-b pb-4 mb-4">
        <div class="flex justify-between items-center">
            <img src="{{ $quotation->logo ? asset('storage/'.$quotation->logo) : asset('assets/logo.png')  }}" class="w-32" alt="logo">
            <h1 class="text-right text-2xl font-bold text-gray-700">Quotation</h1>

        </div>
        <hr class="border-green-500 border-2 h-px my-2">
        <div class="text-right text-gray-600">{{$quotation->prefix}}{{ $quotation->numbering}}</div>
        <div class="flex justify-between mt-4">
            <div>
                <div class="font-bold">From :</div>
                {{ $quotation->address }}<br>
                {{ $quotation->poscode }}<br>
                {{ $quotation->city }}<br>
                {{ $quotation->state }}<br>
            </div>
            <div class="w-52"></div>
            <div class="text-right">
                <div class="font-bold">To :</div>
                {{ $quotation->to_address }}<br>
                {{ $quotation->to_poscode }}<br>
                {{ $quotation->to_city }}<br>
                {{ $quotation->to_state }}<br>
            </div>
        </div>
        <div class="flex justify-between mt-4">
            <div>
                <div class="font-bold">Issue Date:</div>
                <div> {{ date("j F, Y", strtotime($quotation->quotation_date) ) }}</div>
            </div>
            <div class="text-right">
                <div class="font-bold">Due Date:</div>
                @php
                 $validday = '+ ' . $quotation->valid_days . ' days';   
                @endphp
                <div>{{ date("j F, Y", strtotime($validday , strtotime($quotation->quotation_date)) ) }}</div>
            </div>
        </div>
        <div class="mt-4">
            <span class="inline-block px-3 py-1 text-sm bg-green-100 text-green-700 rounded-full"> {{ $quotation->quote_status ? ucwords($quotation->quote_status) : 'Draft' }}</span>
        </div>
    </div>
    
    <!-- Quotation Details -->
    <div class="mb-4">
        <div class="font-bold mb-2">{{ $quotation->summary }}</div>
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
            <span>{{ $quotation->sub_total }}</span>
        </div>
        <div class="flex justify-between">
            <span>Taxes:</span>
            <span>{{ $quotation->taxes }}</span>
        </div>
        <div class="flex justify-between">
            <span>Percentage tax:</span>
            <span>{{ $quotation->percentage_tax }}</span>
        </div>
        <div class="flex justify-between">
            <span>Delivery:</span>
            <span>{{ $quotation->delivery }}</span>
        </div>
        <div class="flex justify-between font-bold text-lg">
            <span>Final amount:</span>
            <span>{{ $quotation->final_amount }}</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center text-gray-600">
        {{ $quotation->terms_conditions}}<br>
        {{ $quotation->footer}}
    </div>
</div>
</div>


<script>
    document.querySelector(".printme").addEventListener("click", function(e) {
        e.preventDefault();
        e.target.style.display = 'none';
        document.querySelector(".myfooter").style.display = 'none';
        window.print();
        e.target.style.display = 'block';
        document.querySelector(".myfooter").style.display = 'block';
    })
</script>
