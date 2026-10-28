@extends('layouts.app')

@section('content')
<div class="row h-100">
    <!-- Left Side-->
    <div class="col-md-8 d-flex flex-column h-100">
        <!-- Categories -->
        <div class="mb-3" id="category-filters">
            <button class="btn category-btn shadow-sm me-2 mb-2 active" data-category="all">All</button>
            @foreach ($categories as $category)
                <button class="btn category-btn shadow-sm me-2 mb-2" data-category="{{ $category->categoryID }}">
                    {{ $category->categoryname }}
                </button>
            @endforeach
        </div>

       
        <!-- Menu Grid Area -->
        <div class="flex-grow-1 overflow-auto pe-2 menu-scroll-area">
            <div class="row" id="menu-grid">
                @foreach ($menuItems as $item)
                    <div class="col-sm-4 col-md-3 mb-3 menu-card-wrapper" data-category="{{ $item->categoryID }}">
                        <div class="card h-100 shadow-sm item-card" style="cursor: pointer;" 
                             data-id="{{ $item->menuID }}" 
                             data-name="{{ $item->itemname }}" 
                             data-price="{{ $item->price }}">
                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                <h6 class="card-title fw-bold text-brown">{{ $item->itemname }}</h6>
                                <p class="card-text text-muted mb-0">₱{{ number_format($item->price, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
    </div>

    <!-- Right Side -->
    <div class="col-md-4 d-flex flex-column bg-white shadow-sm rounded p-3 h-100 border-start">
        <h4 class="fw-bold text-brown mb-3 border-bottom pb-2">Current Order</h4>
        <div class="flex-grow-1 overflow-auto">
            <table class="table table-borderless table-sm align-middle">
                <tbody id="cart-table-body"></tbody>
            </table>
        </div>
        <div class="mt-auto border-top pt-3">
            <div class="d-flex justify-content-between mb-3 px-2">
                <h5 class="fw-bold mb-0">Total:</h5>
                <h5 class="fw-bold text-coffee mb-0" id="total-amount-display">₱0.00</h5>
            </div>
            <button id="checkout-btn" class="btn btn-coffee bg-coffee text-white w-100 py-3 fw-bold fs-5 shadow-sm" disabled>
                CHECKOUT
            </button>
        </div>
    </div>
</div>
@endsection

<!-- Payment form -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-coffee text-white border-0">
                <h5 class="modal-title fw-bold" id="paymentModalLabel">Process Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                    <span class="fw-bold text-muted">Order ID:</span>
                    <span class="fw-bold text-dark">Pending...</span>
                </div>
                
                <div class="d-flex justify-content-between mb-4 border-bottom pb-2">
                    <span class="fw-bold fs-5">Total Amount:</span>
                    <span class="fw-bold fs-5 text-success" id="modal-total-display">₱0.00</span>
                </div>

           
                <div class="mb-3">
                    <label class="fw-bold mb-2">Payment Method</label>
                    <div class="d-flex gap-2">
                        <input type="radio" class="btn-check" name="paymentMethod" id="methodCash" value="Cash" autocomplete="off" checked>
                        <label class="btn btn-outline-dark w-50" for="methodCash">Cash</label>

                        <input type="radio" class="btn-check" name="paymentMethod" id="methodEWallet" value="E-Wallet" autocomplete="off">
                        <label class="btn btn-outline-dark w-50" for="methodEWallet">E-Wallet</label>
                    </div>
                </div>

         
                <div class="mb-3">
                    <label for="amountReceived" class="fw-bold mb-1">Amount Received</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">₱</span>
                        <input type="number" class="form-control form-control-lg" id="amountReceived" placeholder="0.00" min="0" step="0.01">
                    </div>
                </div>

                
                <div class="mb-4">
                    <label for="changeAmount" class="fw-bold mb-1">Change</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">₱</span>
                        <input type="text" class="form-control form-control-lg bg-light text-danger fw-bold" id="changeAmount" value="0.00" readonly>
                    </div>
                </div>

                <button id="complete-payment-btn" class="btn btn-coffee w-100 py-3 fw-bold fs-5 shadow-sm" disabled>
                    COMPLETE PAYMENT
                </button>
            </div>
        </div>
    </div>
</div>
